<?php

namespace App\Contracts;

interface PartialRefundContract
{
    public function refundType(): string;

    public function successfulRefundStatus(): string;

    public function refundTotalAfterApplying(int $currentRefundTotalMinor, int $partialRefundAmountMinor): int;

    public function paymentStatusAfterPartialRefund(int $paidTotalMinor, int $refundTotalMinor): string;

    public function refundsPreserveOriginalPayments(): bool;

    public function refundsStaySeparateFromCancellationEffects(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
