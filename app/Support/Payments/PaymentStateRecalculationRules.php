<?php

namespace App\Support\Payments;

use App\Contracts\PaymentStateRecalculationContract;
use App\Enums\PaymentStatus;

readonly class PaymentStateRecalculationRules implements PaymentStateRecalculationContract
{
    public function sourceOfTruth(): string
    {
        return 'payments and refunds';
    }

    public function unpaidStatus(): string
    {
        return PaymentStatus::Unpaid->value();
    }

    public function partiallyPaidStatus(): string
    {
        return PaymentStatus::PartiallyPaid->value();
    }

    public function paidStatus(): string
    {
        return PaymentStatus::Paid->value();
    }

    public function partiallyRefundedStatus(): string
    {
        return PaymentStatus::PartiallyRefunded->value();
    }

    public function refundedStatus(): string
    {
        return PaymentStatus::Refunded->value();
    }

    public function calculate(int $orderTotalMinor, int $paidTotalMinor, int $refundTotalMinor, int $expectedAdvanceMinor = 0): string
    {
        $orderTotalMinor = max(0, $orderTotalMinor);
        $paidTotalMinor = max(0, $paidTotalMinor);
        $refundTotalMinor = max(0, $refundTotalMinor);
        $expectedAdvanceMinor = max(0, $expectedAdvanceMinor);

        $netPaidMinor = $this->netPaid($paidTotalMinor, $refundTotalMinor);

        if ($refundTotalMinor > 0) {
            return $netPaidMinor > 0
                ? PaymentStatus::PartiallyRefunded->value()
                : PaymentStatus::Refunded->value();
        }

        if ($paidTotalMinor === 0) {
            return PaymentStatus::Unpaid->value();
        }

        if ($paidTotalMinor >= $orderTotalMinor) {
            return PaymentStatus::Paid->value();
        }

        if ($expectedAdvanceMinor > 0 && $paidTotalMinor >= $expectedAdvanceMinor) {
            return PaymentStatus::AdvancePaid->value();
        }

        return PaymentStatus::PartiallyPaid->value();
    }

    public function netPaid(int $paidTotalMinor, int $refundTotalMinor): int
    {
        return max(0, max(0, $paidTotalMinor) - max(0, $refundTotalMinor));
    }

    public function refundStateTakesPriorityOverPaidState(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_of_truth' => $this->sourceOfTruth(),
            'unpaid_status' => $this->unpaidStatus(),
            'partially_paid_status' => $this->partiallyPaidStatus(),
            'paid_status' => $this->paidStatus(),
            'partially_refunded_status' => $this->partiallyRefundedStatus(),
            'refunded_status' => $this->refundedStatus(),
            'refund_state_takes_priority_over_paid_state' => $this->refundStateTakesPriorityOverPaidState(),
            'net_paid_formula' => 'net_paid = max(0, paid_total - refund_total)',
        ];
    }
}
