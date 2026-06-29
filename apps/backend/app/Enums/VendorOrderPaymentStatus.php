<?php

namespace App\Enums;

enum VendorOrderPaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    /**
     * Get all values for the enum.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
