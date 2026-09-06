<?php

namespace App\Support\Dashboard;

final class DashboardNumbers
{
    public static function money(float $amount, int $decimals = 0): string
    {
        $parts = explode('.', number_format(abs($amount), $decimals, '.', ''));
        $integer = $parts[0];
        if (strlen($integer) > 3) {
            $integer = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($integer, 0, -3)).','.substr($integer, -3);
        }

        return ($amount < 0 ? '−' : '').'₹'.$integer.(isset($parts[1]) ? '.'.$parts[1] : '');
    }
}
