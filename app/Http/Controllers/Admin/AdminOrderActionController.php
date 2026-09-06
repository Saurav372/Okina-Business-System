<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Events\AuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\SalesOrderService;
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

        $salesOrderService = app(SalesOrderService::class);
        $targetStatus = OrderStatus::from($validated['status']);
        $salesOrderService->transitionStatus($order, $targetStatus, $validated, $request->user());

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
                    Refund::where('order_id', $order->id)->where('status', 'succeeded')->sum('amount_minor'),
                    $order->getExpectedAdvanceAmount()
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
                $refundTotal,
                $order->getExpectedAdvanceAmount()
            );

            return [$payment, $paymentStatus];
        });

        // Emit audit event
        event(new AuditEvent('payments.payment_recorded', $actor, [
            'order_public_id' => $order->public_id,
            'payment_public_id' => $payment->id,
            'record_status' => $payment->status,
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

    public function cancel(Request $request, Order $order)
    {
        Gate::authorize('cancel', $order);

        // Normalize boolean string values ('true', 'false', '1', '0') before validation
        $booleans = ['material_consumed', 'customization_applied', 'scrap_incurred', 'physical_interception_confirmed', 'create_refund_request'];
        $normalized = [];
        foreach ($booleans as $field) {
            if ($request->has($field)) {
                $normalized[$field] = filter_var($request->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }
        if (!empty($normalized)) {
            $request->merge($normalized);
        }

        $validated = $request->validate([
            'reason_code' => ['required', 'string', 'in:customer_request,artwork_issue,lead_time_delay,pricing_error,duplicate_order,other'],
            'reason_note' => ['nullable', 'string', 'max:1000', Rule::requiredIf($request->input('reason_code') === 'other')],
            'material_consumed' => ['nullable', 'boolean'],
            'customization_applied' => ['nullable', 'boolean'],
            'scrap_incurred' => ['nullable', 'boolean'],
            'affected_quantity' => ['nullable', 'integer', 'min:0'],
            'scrap_quantity' => ['nullable', 'integer', 'min:0'],
            'scrap_amount' => ['nullable', 'numeric', 'min:0'],
            'production_impact_notes' => ['nullable', 'string', 'max:2000', Rule::requiredIf($order->status === OrderStatus::InProduction->value)],
            'physical_interception_confirmed' => ['nullable', 'boolean', Rule::requiredIf($order->status === OrderStatus::ReadyToShip->value)],
            'create_refund_request' => ['nullable', 'boolean'],
            'refund_amount' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        // Server-side conversion of Rupee currency input to integer minor units
        $scrapAmountMinor = null;
        if ($request->filled('scrap_amount')) {
            $scrapAmountMinor = (int) round(((float) $request->input('scrap_amount')) * 100);
        }

        $refundAmountMinor = null;
        if ($request->filled('refund_amount')) {
            $refundAmountMinor = (int) round(((float) $request->input('refund_amount')) * 100);
        }

        $cancellationService = app(\App\Services\OrderCancellationService::class);

        $cancellation = $cancellationService->cancel(
            order: $order,
            actor: $request->user(),
            reasonCode: $validated['reason_code'],
            reasonNote: $validated['reason_note'] ?? null,
            materialConsumed: $request->boolean('material_consumed'),
            customizationApplied: $request->boolean('customization_applied'),
            scrapIncurred: $request->boolean('scrap_incurred'),
            affectedQuantity: isset($validated['affected_quantity']) ? (int) $validated['affected_quantity'] : null,
            scrapQuantity: isset($validated['scrap_quantity']) ? (int) $validated['scrap_quantity'] : null,
            scrapAmountMinor: $scrapAmountMinor,
            productionImpactNotes: $validated['production_impact_notes'] ?? null,
            physicalInterceptionConfirmed: $request->boolean('physical_interception_confirmed'),
            createRefundRequest: $request->boolean('create_refund_request'),
            requestedRefundAmountMinor: $refundAmountMinor,
        );

        $message = "Order {$order->public_id} has been cancelled successfully.";
        if ($request->boolean('create_refund_request') && $refundAmountMinor > 0) {
            $formattedRefund = number_format($refundAmountMinor / 100, 2);
            $message .= " Internal refund request of ₹{$formattedRefund} has been submitted for finance approval.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'cancellation' => $cancellation,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
