<?php

namespace App\Services;

use App\Enums\VendorOrderStatus;
use App\Enums\VendorPaymentStatus;
use App\Events\AuditEvent;
use App\Exceptions\PurchaseOrderNotPayableException;
use App\Exceptions\PurchaseOrderPaymentLimitExceededException;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorPaymentService
{
    /**
     * Record a vendor payment settlement against a purchase order.
     *
     * @param  array{amount_minor: int, payment_method: mixed, reference?: string|null, paid_at?: string|null, notes?: string|null}  $data
     * @return array{purchase_order: VendorOrder, payment: VendorPayment}
     */
    public function recordPayment(
        VendorOrder $order,
        array $data,
        ?User $actor = null
    ): array {
        $actor = $actor ?: Auth::user();

        return DB::transaction(function () use ($order, $data, $actor) {
            /** @var VendorOrder $lockedPo */
            $lockedPo = VendorOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();

            // Check if PO is in editable (draft) or cancelled status
            if ($lockedPo->isEditable() || $lockedPo->status === VendorOrderStatus::CANCELLED) {
                throw new PurchaseOrderNotPayableException(
                    "Cannot record payment on a purchase order in [{$lockedPo->status->value}] status."
                );
            }

            // Lock existing payments to compute current total paid
            $existingPaymentsSum = (int) VendorPayment::where('vendor_order_id', $lockedPo->id)
                ->where('status', VendorPaymentStatus::PAID->value)
                ->lockForUpdate()
                ->sum('amount_minor');

            $amountMinor = (int) ($data['amount_minor'] ?? 0);
            $remainingPayable = $lockedPo->total_amount_minor - $existingPaymentsSum;

            if ($amountMinor > $remainingPayable) {
                throw new PurchaseOrderPaymentLimitExceededException(
                    'The payment amount [₹'.number_format($amountMinor / 100, 2).'] exceeds the remaining payable balance [₹'.number_format($remainingPayable / 100, 2).'].'
                );
            }

            $previousPaymentStatus = $lockedPo->payment_status;

            /** @var VendorPayment $payment */
            $payment = VendorPayment::create([
                'vendor_order_id' => $lockedPo->id,
                'recorded_by_user_id' => $actor?->id,
                'status' => VendorPaymentStatus::PAID,
                'payment_method' => $data['payment_method'],
                'amount_minor' => $amountMinor,
                'currency' => $lockedPo->currency ?? 'INR',
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $newTotalPaid = $existingPaymentsSum + $payment->amount_minor;

            // Recalculate parent PO payment status (UNPAID -> PARTIALLY_PAID -> PAID)
            $lockedPo->recalculatePaymentStatus($newTotalPaid);
            $lockedPo->save();

            $payment->refresh();
            $lockedPo->refresh();

            $newPaidSum = $newTotalPaid;
            $newRemainingBalance = $lockedPo->total_amount_minor - $newPaidSum;
            $prevStatusValue = $previousPaymentStatus ? $previousPaymentStatus->value : 'unpaid';
            $currStatusValue = $lockedPo->payment_status ? $lockedPo->payment_status->value : 'unpaid';

            DB::afterCommit(function () use ($lockedPo, $payment, $newPaidSum, $newRemainingBalance, $prevStatusValue, $currStatusValue, $actor) {
                event(new AuditEvent('vendor_payment.recorded', $actor, [
                    'vendor_order_id' => $lockedPo->id,
                    'vendor_payment_id' => $payment->id,
                    'public_id' => $lockedPo->public_id,
                    'payment_amount_minor' => $payment->amount_minor,
                    'total_paid_minor' => $newPaidSum,
                    'remaining_balance_minor' => $newRemainingBalance,
                    'previous_payment_status' => $prevStatusValue,
                    'payment_status' => $currStatusValue,
                    'currency' => $payment->currency,
                    'payment_method' => $payment->payment_method->value,
                    'reference' => $payment->reference,
                    'actor_id' => $actor?->id,
                ]));
            });

            return [
                'purchase_order' => $lockedPo,
                'payment' => $payment,
            ];
        });
    }
}
