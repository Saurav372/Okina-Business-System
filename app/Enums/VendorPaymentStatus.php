<?php

namespace App\Enums;

enum VendorPaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case VOIDED = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::VOIDED => 'Voided',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PENDING => 'bg-amber-50 text-amber-700 border-amber-200',
            self::CANCELLED => 'bg-red-50 text-red-700 border-red-200',
            self::VOIDED => 'bg-neutral-100 text-neutral-600 border-neutral-200',
        };
    }
}
