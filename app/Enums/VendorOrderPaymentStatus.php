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

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PARTIALLY_PAID => 'bg-amber-50 text-amber-700 border-amber-200',
            self::UNPAID => 'bg-red-50 text-red-700 border-red-200',
            self::CANCELLED => 'bg-neutral-100 text-neutral-500 border-neutral-200',
        };
    }
}
