<?php

namespace App\Support\Orders;

final class OrderTotalsCalculator
{
    /**
     * @param  array<int, int>  $lineTotalsMinor
     */
    public function fromLineTotals(
        array $lineTotalsMinor,
        int $discountAmountMinor = 0,
        int $shippingAmountMinor = 0,
        int $taxAmountMinor = 0,
        int $paidAmountMinor = 0,
        int $refundAmountMinor = 0,
    ): OrderTotals {
        return OrderTotals::fromLineTotals(
            lineTotals: $lineTotalsMinor,
            discountAmountMinor: $discountAmountMinor,
            shippingAmountMinor: $shippingAmountMinor,
            taxAmountMinor: $taxAmountMinor,
            paidAmountMinor: $paidAmountMinor,
            refundAmountMinor: $refundAmountMinor,
        );
    }

    public function fromAmounts(
        int $subtotalAmountMinor,
        int $discountAmountMinor = 0,
        int $shippingAmountMinor = 0,
        int $taxAmountMinor = 0,
        int $paidAmountMinor = 0,
        int $refundAmountMinor = 0,
    ): OrderTotals {
        return OrderTotals::fromAmounts(
            subtotalAmountMinor: $subtotalAmountMinor,
            discountAmountMinor: $discountAmountMinor,
            shippingAmountMinor: $shippingAmountMinor,
            taxAmountMinor: $taxAmountMinor,
            paidAmountMinor: $paidAmountMinor,
            refundAmountMinor: $refundAmountMinor,
        );
    }
}
