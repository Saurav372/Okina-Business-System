<?php

namespace App\Support\Payments;

use App\Contracts\PartialRefundContract;
use App\Enums\PaymentStatus;

readonly class PartialRefundRules implements PartialRefundContract
{
    public function refundType(): string
    {
        return 'partial';
    }

    public function successfulRefundStatus(): string
    {
        return 'succeeded';
    }

    public function refundTotalAfterApplying(int $currentRefundTotalMinor, int $partialRefundAmountMinor): int
    {
        return max(0, $currentRefundTotalMinor + max(0, $partialRefundAmountMinor));
    }

    public function paymentStatusAfterPartialRefund(int $paidTotalMinor, int $refundTotalMinor): string
    {
        $paidTotalMinor = max(0, $paidTotalMinor);
        $refundTotalMinor = max(0, $refundTotalMinor);

        if ($refundTotalMinor === 0) {
            return PaymentStatus::Paid->value();
        }

        $netPaidMinor = max(0, $paidTotalMinor - $refundTotalMinor);

        if ($netPaidMinor > 0) {
            return PaymentStatus::PartiallyRefunded->value();
        }

        return PaymentStatus::Refunded->value();
    }

    public function refundsPreserveOriginalPayments(): bool
    {
        return true;
    }

    public function refundsStaySeparateFromCancellationEffects(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'refund_type' => $this->refundType(),
            'successful_refund_status' => $this->successfulRefundStatus(),
            'refunds_preserve_original_payments' => $this->refundsPreserveOriginalPayments(),
            'refunds_stay_separate_from_cancellation_effects' => $this->refundsStaySeparateFromCancellationEffects(),
            'refund_total_formula' => 'refund_total = current_refund_total + partial_refund_amount',
            'payment_status_formula' => 'refund_total = 0 -> paid; refund_total > 0 and net_paid > 0 -> partially_refunded; refund_total > 0 and net_paid = 0 -> refunded',
        ];
    }
}
