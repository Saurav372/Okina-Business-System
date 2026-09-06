<?php

namespace App\Enums;

enum InventoryStatus: string
{
    case NEGATIVE = 'negative';
    case OUT_OF_STOCK = 'out_of_stock';
    case LOW_STOCK = 'low_stock';
    case IN_STOCK = 'in_stock';

    public function label(): string
    {
        return match ($this) {
            self::NEGATIVE => 'Negative Stock',
            self::OUT_OF_STOCK => 'Out of Stock',
            self::LOW_STOCK => 'Low Stock',
            self::IN_STOCK => 'In Stock',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::NEGATIVE, self::OUT_OF_STOCK => 'bg-red-50 text-red-700 border-red-200/60',
            self::LOW_STOCK => 'bg-amber-50 text-amber-700 border-amber-200/60',
            self::IN_STOCK => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        };
    }
}
