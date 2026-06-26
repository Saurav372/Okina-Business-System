<?php

namespace App\Http\Controllers\Admin;

use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundLedgerIndexRequest;
use App\Http\Requests\Admin\StoreRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    private array $auditPayload = [];

    public function index(RefundLedgerIndexRequest $request)
    {
        Gate::authorize('viewAny', Refund::class);

        $query = Refund::query()->with(['order', 'payment']);

        $validated = $request->validated();

        if (! empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }
        if (! empty($validated['provider'])) {
            $query->where('provider', $validated['provider']);
        }
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['refund_type'])) {
            $query->where('refund_type', $validated['refund_type']);
        }

        $totalQuery = clone $query;

        $perPage = $validated['per_page'] ?? 20;
        $refunds = $query->latest('id')->paginate($perPage);

        return RefundResource::collection($refunds)->additional([
            'meta' => [
                'total_amount_minor' => (int) $totalQuery->sum('amount_minor'),
            ],
        ]);
    }

    public function show(Refund $refund)
    {
        Gate::authorize('view', $refund);

        $refund->load(['order', 'payment']);

        return new RefundResource($refund);
    }

    public function store(StoreRefundRequest $request)
    {
        Gate::authorize('create', Refund::class);

        $validated = $request->validated();
        $actor = $request->user();

        $refund = DB::transaction(function () use ($validated, $actor) {
            $payment = Payment::where('id', $validated['payment_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $order = Order::where('public_id', $validated['order_public_id'])->firstOrFail();

            $existingRefundsSum = (int) $payment->refunds()
                ->reservesBalance()
                ->sum('amount_minor');

            $remainingBefore = $payment->amount_minor - $existingRefundsSum;
            $requestedAmount = (int) $validated['amount_minor'];

            if ($requestedAmount > $remainingBefore) {
                throw ValidationException::withMessages([
                    'amount_minor' => ['The requested refund amount exceeds the remaining refundable balance of ' . $remainingBefore . ' minor units.'],
                ]);
            }

            if ($validated['refund_type'] === Refund::TYPE_FULL && $requestedAmount !== $remainingBefore) {
                throw ValidationException::withMessages([
                    'amount_minor' => ['A full refund must equal the remaining refundable balance of ' . $remainingBefore . ' minor units.'],
                ]);
            }

            $refund = Refund::create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
                'refund_type' => $validated['refund_type'],
                'status' => Refund::STATUS_REQUESTED,
                'amount_minor' => $requestedAmount,
                'currency' => $payment->currency ?? 'INR',
                'reason_code' => $validated['reason_code'] ?? null,
                'reason_note' => $validated['reason_note'] ?? null,
                'requested_by_user_id' => $actor?->id,
                'requested_at' => now(),
            ]);

            $remainingAfter = $remainingBefore - $requestedAmount;

            $this->auditPayload = [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'order_public_id' => $order->public_id,
                'amount_minor' => $refund->amount_minor,
                'refund_type' => $refund->refund_type,
                'requested_by_user_id' => $actor?->id,
                'remaining_refundable_amount_before_request' => $remainingBefore,
                'remaining_after_request' => $remainingAfter,
            ];

            return $refund;
        });

        event(new AuditEvent('refunds.refund_requested', $actor, $this->auditPayload));

        $refund->load(['order', 'payment']);

        return (new RefundResource($refund))
            ->response($request)
            ->setStatusCode(201)
            ->header('Location', route('admin.refunds.show', $refund));
    }
}
