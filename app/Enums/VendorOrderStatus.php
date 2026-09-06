<?php

namespace App\Enums;

enum VendorOrderStatus: string
{
    case DRAFT = 'draft';
    case ORDERED = 'ordered';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
    case CLOSED = 'closed';

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
            self::DRAFT => 'Draft',
            self::ORDERED => 'Ordered',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::RECEIVED => 'Received',
            self::CANCELLED => 'Cancelled',
            self::CLOSED => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::RECEIVED, self::CLOSED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::ORDERED, self::PARTIALLY_RECEIVED => 'bg-amber-50 text-amber-700 border-amber-200',
            self::CANCELLED => 'bg-red-50 text-red-700 border-red-200',
            self::DRAFT => 'bg-neutral-100 text-neutral-600 border-neutral-200',
        };
    }
}
