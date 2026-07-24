<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\Support\Facades\Gate;

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

        if ($request->wantsJson()) {
            return new RefundResource($refund);
        }

        return redirect()->route('admin.refunds.show', $refund)
            ->with('success', "Refund request [#{$refund->id}] of ₹".number_format($refund->amount_minor / 100, 2).' created in REQUESTED status.');
    }

    public function approve(Request $request, Refund $refund)
    {
        Gate::authorize('approve', $refund);

        $approved = $this->refundService->approveRefund(
            refund: $refund,
            actor: $request->user()
        );

        if ($request->wantsJson()) {
            return new RefundResource($approved);
        }

        return redirect()->back()
            ->with('success', "Refund [#{$approved->id}] successfully APPROVED for processing.");
    }

    public function process(Request $request, Refund $refund)
    {
        Gate::authorize('process', $refund);

        $providerRefundId = $request->input('provider_refund_id');

        $processed = $this->refundService->processRefund(
            refund: $refund,
            providerRefundId: $providerRefundId,
            actor: $request->user()
        );

        if ($request->wantsJson()) {
            return new RefundResource($processed);
        }

        return redirect()->back()
            ->with('success', "Refund [#{$processed->id}] successfully PROCESSED and marked SUCCEEDED.");
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

        $cancelled = $this->refundService->cancelRefund(
            refund: $refund,
            actor: $request->user()
        );

        if ($request->wantsJson()) {
            return new RefundResource($cancelled);
        }

        return redirect()->back()
            ->with('success', "Refund [#{$cancelled->id}] has been CANCELLED.");
    }
}
