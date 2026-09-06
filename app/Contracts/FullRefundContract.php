<?php

namespace App\Contracts;

interface FullRefundContract
{
    public function refundType(): string;

    public function successfulRefundStatus(): string;

    public function orderStatusAfterFullRefund(): string;

    public function refundTotalAfterApplying(int $paidTotalMinor, int $currentRefundTotalMinor): int;

    public function paymentStatusAfterFullRefund(): string;

    public function keepsOriginalPayments(): bool;

    public function keepsCustomerVisibilitySafe(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
