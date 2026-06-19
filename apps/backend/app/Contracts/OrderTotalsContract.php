<?php

namespace App\Contracts;

interface OrderTotalsContract
{
    public function subtotalAmountMinor(): int;

    public function discountAmountMinor(): int;

    public function shippingAmountMinor(): int;

    public function taxAmountMinor(): int;

    public function totalAmountMinor(): int;

    public function paidAmountMinor(): int;

    public function refundAmountMinor(): int;

    public function netPaidAmountMinor(): int;

    public function balanceDueMinor(): int;

    public function outstandingAmountMinor(): int;

    public function isBalanced(): bool;

    public function isOverpaid(): bool;

    public function hasRefundActivity(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
