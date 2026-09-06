<?php

namespace App\Support\Payments;

use App\Contracts\FullRefundContract;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;

readonly class FullRefundRules implements FullRefundContract
{
    public function refundType(): string
    {
        return 'full';
    }

    public function successfulRefundStatus(): string
    {
        return 'succeeded';
    }

    public function orderStatusAfterFullRefund(): string
    {
        return OrderStatus::Refunded->value();
    }

    public function refundTotalAfterApplying(int $paidTotalMinor, int $currentRefundTotalMinor): int
    {
        $paidTotalMinor = max(0, $paidTotalMinor);
        $currentRefundTotalMinor = max(0, $currentRefundTotalMinor);

        return max($currentRefundTotalMinor, $paidTotalMinor);
    }

    public function paymentStatusAfterFullRefund(): string
    {
        return PaymentStatus::Refunded->value();
    }

    public function keepsOriginalPayments(): bool
    {
        return true;
    }

    public function keepsCustomerVisibilitySafe(): bool
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
            'order_status_after_full_refund' => $this->orderStatusAfterFullRefund(),
            'payment_status_after_full_refund' => $this->paymentStatusAfterFullRefund(),
            'keeps_original_payments' => $this->keepsOriginalPayments(),
            'keeps_customer_visibility_safe' => $this->keepsCustomerVisibilitySafe(),
            'refund_total_formula' => 'refund_total = max(current_refund_total, paid_total)',
        ];
    }
}
