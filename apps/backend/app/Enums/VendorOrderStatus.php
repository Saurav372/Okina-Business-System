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
}
