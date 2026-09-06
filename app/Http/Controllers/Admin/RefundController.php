<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundLedgerIndexRequest;
use App\Http\Requests\Admin\StoreRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundService;
use App\Support\Finance\RefundCatalog;
use App\Support\Finance\RefundFilters;
use App\Support\Finance\RefundMetrics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function __construct(
        protected RefundCatalog $catalog,
        protected RefundService $refundService
    ) {}

    public function index(RefundLedgerIndexRequest $request)
    {
        Gate::authorize('viewAny', Refund::class);

        $filters = new RefundFilters($request->all());
        $metrics = new RefundMetrics($filters);
        $refunds = $this->catalog->getPaginatedRefunds($filters, $request->integer('per_page', 25));

        if ($request->wantsJson()) {
            return RefundResource::collection($refunds)->additional([
                'meta' => [
                    'total_amount_minor' => $metrics->totalRefundedVolumeMinor,
                ],
            ]);
        }

        $succeededPayments = Payment::where('status', Payment::STATUS_SUCCEEDED)
            ->with('order.customer')
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.refunds.index', [
            'filters' => $filters,
            'metrics' => $metrics,
            'refunds' => $refunds,
            'succeededPayments' => $succeededPayments,
        ]);
    }

    public function show(Request $request, Refund $refund)
    {
        Gate::authorize('view', $refund);

        $refund->load(['order.customer', 'payment', 'requester', 'approver', 'processor']);

        if ($request->wantsJson()) {
            return new RefundResource($refund);
        }

        return view('admin.refunds.show', [
            'refund' => $refund,
        ]);
    }

    public function store(StoreRefundRequest $request)
    {
        Gate::authorize('create', Refund::class);

        $payment = Payment::findOrFail($request->integer('payment_id'));

        $refund = $this->refundService->requestRefund(
            payment: $payment,
            amountMinor: $request->integer('amount_minor'),
            reasonCode: $request->string('reason_code'),
            reasonNote: $request->input('reason_note'),
            actor: $request->user()
        );

        $refund->load(['order', 'payment']);

        if ($request->wantsJson()) {
            return (new RefundResource($refund))
                ->response()
                ->setStatusCode(201)
                ->header('Location', route('admin.refunds.show', $refund->id));
        }

        return redirect()->route('admin.refunds.show', $refund)
            ->with('success', "Refund request [#{$refund->id}] of ₹".number_format($refund->amount_minor / 100, 2).' created in REQUESTED status.');
    }

    public function approve(Request $request, Refund $refund)
    {
        Gate::authorize('approve', $refund);

        $actor = $request->user();

        $lockedRefund = DB::transaction(function () use ($refund, $actor) {
            $lockedRefund = Refund::query()
                ->lockForUpdate()
                ->findOrFail($refund->getKey());

            $lockedRefund->loadMissing(['order', 'payment']);

            $oldStatus = $lockedRefund->status;

            try {
                $lockedRefund->approve($actor);
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'refund' => [$e->getMessage()],
                ]);
            }

            $lockedRefund->save();

            $auditPayload = [
                'refund_public_id' => $lockedRefund->id,
                'payment_public_id' => $lockedRefund->payment_id,
                'order_public_id' => $lockedRefund->order?->public_id,
                'old_status' => $oldStatus,
                'new_status' => Refund::STATUS_APPROVED,
                'status' => Refund::STATUS_APPROVED,
                'approved_by_user_id' => $actor?->id,
                'actor_type' => 'user',
                'actor_id' => $actor?->id,
                'occurred_at' => now()->toIso8601String(),
            ];

            event(new AuditEvent('refunds.refund_approved', $actor, $auditPayload));

            return $lockedRefund;
        });

        if ($request->wantsJson()) {
            return new RefundResource($lockedRefund);
        }

        return redirect()->back()
            ->with('success', "Refund [#{$lockedRefund->id}] successfully APPROVED for processing.");
    }

    public function process(Request $request, Refund $refund)
    {
        Gate::authorize('process', $refund);

        $actor = $request->user();
        $providerRefundId = $request->input('provider_refund_id');

        $lockedRefund = DB::transaction(function () use ($refund, $actor, $providerRefundId) {
            $lockedRefund = Refund::query()
                ->lockForUpdate()
                ->findOrFail($refund->getKey());

            $lockedRefund->loadMissing(['order', 'payment']);

            $oldStatus = $lockedRefund->status;

            try {
                $lockedRefund->markProcessing($actor, now(), $providerRefundId);
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'refund' => [$e->getMessage()],
                ]);
            }

            $lockedRefund->save();

            $auditPayload = [
                'refund_public_id' => $lockedRefund->id,
                'payment_public_id' => $lockedRefund->payment_id,
                'order_public_id' => $lockedRefund->order?->public_id,
                'old_status' => $oldStatus,
                'new_status' => Refund::STATUS_PROCESSING,
                'actor_type' => 'user',
                'actor_id' => $actor?->id,
                'occurred_at' => now()->toIso8601String(),
            ];

            event(new AuditEvent('refunds.refund_processing_started', $actor, $auditPayload));

            return $lockedRefund;
        });

        if ($request->wantsJson()) {
            return new RefundResource($lockedRefund);
        }

        return redirect()->back()
            ->with('success', "Refund [#{$lockedRefund->id}] successfully marked for PROCESSING.");
    }

    public function retry(Request $request, Refund $refund): RedirectResponse
    {
        Gate::authorize('retry', $refund);

        $retried = $this->refundService->retryRefund(
            refund: $refund,
            actor: $request->user()
        );

        return redirect()->back()
            ->with('success', "Refund [#{$retried->id}] payout successfully retried and SUCCEEDED.");
    }

    public function cancel(Request $request, Refund $refund)
    {
        Gate::authorize('cancel', $refund);

        $actor = $request->user();

        $lockedRefund = DB::transaction(function () use ($refund, $actor) {
            $lockedRefund = Refund::query()
                ->lockForUpdate()
                ->findOrFail($refund->getKey());

            $lockedRefund->loadMissing(['order', 'payment']);

            $oldStatus = $lockedRefund->status;

            try {
                $lockedRefund->cancel();
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'refund' => [$e->getMessage()],
                ]);
            }

            $lockedRefund->save();

            $auditPayload = [
                'refund_public_id' => $lockedRefund->id,
                'payment_public_id' => $lockedRefund->payment_id,
                'order_public_id' => $lockedRefund->order?->public_id,
                'old_status' => $oldStatus,
                'new_status' => Refund::STATUS_CANCELLED,
                'actor_type' => 'user',
                'actor_id' => $actor?->id,
                'occurred_at' => now()->toIso8601String(),
            ];

            event(new AuditEvent('refunds.refund_cancelled', $actor, $auditPayload));

            return $lockedRefund;
        });

        if ($request->wantsJson()) {
            return new RefundResource($lockedRefund);
        }

        return redirect()->back()
            ->with('success', "Refund [#{$lockedRefund->id}] has been CANCELLED.");
    }
}
