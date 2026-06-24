<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Support\Payments\PaymentStateRecalculationRules;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminOrderActionController extends Controller
{
    public function updateStatus(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                'pending_payment', 'confirmed', 'in_production', 'ready_to_ship', 'shipped', 'delivered', 'cancelled', 'refunded',
            ])],
            'design_status' => ['required', 'string', Rule::in(['under_review', 'issue_found', 'approved'])],
            'design_issue_message' => ['nullable', 'string'],
            'production_status' => ['required', 'string', Rule::in(['not_started', 'in_production', 'completed'])],
            'shipping_status' => ['required', 'string', Rule::in(['not_shipped', 'shipped', 'delivered'])],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        $currentStatus = OrderStatus::tryFrom($order->status);
        $targetStatus = OrderStatus::tryFrom($validated['status']);

        if ($currentStatus && $targetStatus && ! $currentStatus->canTransitionTo($targetStatus)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid order status transition.',
            ]);
        }

        // Auto-update timestamps based on status transitions
        if ($validated['status'] === 'confirmed' && $order->confirmed_at === null) {
            $order->confirmed_at = now();
        }
        if ($validated['status'] === 'cancelled' && $order->cancelled_at === null) {
            $order->cancelled_at = now();
        }
        if ($validated['production_status'] === 'completed' && $order->ready_to_ship_at === null) {
            $order->ready_to_ship_at = now();
        }
        if ($validated['shipping_status'] === 'shipped' && $order->shipped_at === null) {
            $order->shipped_at = now();
        }
        if ($validated['shipping_status'] === 'delivered' && $order->delivered_at === null) {
            $order->delivered_at = now();
        }

        $order->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    public function updateShipping(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'courier_name' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_url' => ['nullable', 'string', 'max:1000'],
            'estimated_delivery_at' => ['nullable', 'date'],
        ]);

        $order->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order shipping details updated successfully.',
            ]);
        }

        return redirect()->back()->with('success', 'Order shipping details updated successfully.');
    }

    public function recordPayment(Request $request, Order $order)
    {
        Gate::authorize('recordPayment', $order);

        $validated = $request->validate([
            'amount_minor' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', Rule::in(Payment::METHODS)],
            'payment_type' => ['nullable', 'string', Rule::in(Payment::TYPES)],
            'paid_at' => ['nullable', 'date'],
            'provider_reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            throw ValidationException::withMessages([
                'order' => ['Payments cannot be recorded on cancelled or refunded orders.'],
            ]);
        }

        $idempotencyKey = $validated['idempotency_key'] ?? null;
        if ($idempotencyKey !== null) {
            $existingPayment = Payment::query()
                ->where('order_id', $order->id)
                ->where('metadata->idempotency_key', $idempotencyKey)
                ->first();

            if ($existingPayment !== null) {
                $paymentStatus = app(PaymentStateRecalculationRules::class)->calculate(
                    $order->total_amount_minor,
                    Payment::where('order_id', $order->id)->where('status', 'succeeded')->sum('amount_minor'),
                    Refund::where('order_id', $order->id)->where('status', 'succeeded')->sum('amount_minor')
                );

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment recorded successfully (idempotent).',
                        'payment' => [
                            'id' => $existingPayment->id,
                            'provider' => $existingPayment->provider,
                            'payment_type' => $existingPayment->payment_type,
                            'status' => $existingPayment->status,
                            'amount_minor' => $existingPayment->amount_minor,
                            'currency' => $existingPayment->currency,
                            'receipt_number' => $existingPayment->receipt_number,
                            'paid_at' => $existingPayment->paid_at?->toIso8601String(),
                        ],
                        'order' => [
                            'public_id' => $order->public_id,
                            'payment_status' => $paymentStatus,
                        ],
                    ], 200);
                }

                return redirect()->back()->with('success', 'Payment recorded successfully (idempotent).');
            }
        }

        $actor = $request->user();

        [$payment, $paymentStatus] = DB::transaction(function () use ($order, $validated, $actor) {
            // Pessimistic lock order to prevent race conditions
            Order::where('id', $order->id)->lockForUpdate()->first();

            $paidTotal = (int) Payment::query()
                ->where('order_id', $order->id)
                ->where('status', 'succeeded')
                ->sum('amount_minor');

            $refundTotal = (int) Refund::query()
                ->where('order_id', $order->id)
                ->where('status', 'succeeded')
                ->sum('amount_minor');

            $remainingBalance = $order->total_amount_minor - $paidTotal + $refundTotal;

            if ($validated['amount_minor'] > $remainingBalance) {
                throw ValidationException::withMessages([
                    'amount_minor' => ['Payment amount exceeds the remaining balance due of '.$remainingBalance.' minor units.'],
                ]);
            }

            do {
                $receiptNumber = 'RC-'.time().'-'.Str::upper(Str::random(6));
            } while (Payment::where('receipt_number', $receiptNumber)->exists());

            $paymentType = $validated['payment_type'] ?? Payment::TYPE_ADVANCE;

            $metadata = [];
            if (isset($validated['idempotency_key'])) {
                $metadata['idempotency_key'] = $validated['idempotency_key'];
            }

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_attempt_id' => null,
                'payment_schedule_id' => null,
                'payment_type' => $paymentType,
                'provider' => 'manual',
                'method' => $validated['method'],
                'status' => 'succeeded',
                'amount_minor' => $validated['amount_minor'],
                'currency' => $order->currency ?? 'INR',
                'provider_payment_id' => 'MAN-'.Str::upper(Str::random(16)),
                'provider_order_id' => $order->public_id,
                'provider_reference' => $validated['provider_reference'] ?? null,
                'receipt_number' => $receiptNumber,
                'paid_at' => ($validated['paid_at'] ?? null) ? Carbon::parse($validated['paid_at']) : now(),
                'recorded_by_user_id' => $actor?->id,
                'verified_by_user_id' => null,
                'notes' => $validated['notes'] ?? null,
                'metadata' => $metadata,
            ]);

            $newPaidTotal = $paidTotal + $payment->amount_minor;
            $paymentStatus = app(PaymentStateRecalculationRules::class)->calculate(
                $order->total_amount_minor,
                $newPaidTotal,
                $refundTotal
            );

            return [$payment, $paymentStatus];
        });

        // Emit audit event
        event(new AuditEvent('payments.payment_recorded', $actor, [
            'order_public_id' => $order->public_id,
            'payment_public_id' => $payment->id,
            'amount_minor' => $payment->amount_minor,
            'currency' => $payment->currency,
            'payment_type' => $payment->payment_type,
            'method' => $payment->method,
            'recorded_by_user_id' => $actor?->id,
            'payment_status' => $paymentStatus,
            'provider' => $payment->provider,
            'attempt_public_id' => null,
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully.',
                'payment' => [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'payment_type' => $payment->payment_type,
                    'status' => $payment->status,
                    'amount_minor' => $payment->amount_minor,
                    'currency' => $payment->currency,
                    'receipt_number' => $payment->receipt_number,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ],
                'order' => [
                    'public_id' => $order->public_id,
                    'payment_status' => $paymentStatus,
                ],
            ], 201);
        }

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}
