<?php

namespace App\Support\Storefront;

final class MoneyFormatter
{
    public function format(?int $amountMinor, string $currency = 'INR'): string
    {
        if ($amountMinor === null) {
            return 'Price on request';
        }

        $currency = strtoupper(trim($currency));
        $amountMinor = max(0, $amountMinor);
        $amount = $amountMinor / 100;
        $decimals = $amountMinor % 100 === 0 ? 0 : 2;
        $formatted = number_format($amount, $decimals, '.', ',');

        return $currency === 'INR' ? '₹'.$formatted : $currency.' '.$formatted;
    }
}
