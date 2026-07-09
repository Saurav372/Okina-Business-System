<?php

namespace App\Contracts;

interface PaymentStateRecalculationContract
{
    public function sourceOfTruth(): string;

    public function unpaidStatus(): string;

    public function partiallyPaidStatus(): string;

    public function paidStatus(): string;

    public function partiallyRefundedStatus(): string;

    public function refundedStatus(): string;

    public function calculate(int $orderTotalMinor, int $paidTotalMinor, int $refundTotalMinor, int $expectedAdvanceMinor = 0): string;

    public function netPaid(int $paidTotalMinor, int $refundTotalMinor): int;

    public function refundStateTakesPriorityOverPaidState(): bool;

    public function toArray(): array;
}
