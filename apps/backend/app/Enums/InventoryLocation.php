<?php

namespace App\Enums;

enum InventoryLocation: string
{
    case MAIN_WAREHOUSE = 'main_warehouse';
    case STORE = 'store';
    case TRANSIT = 'transit';

    public function label(): string
    {
        return match ($this) {
            self::MAIN_WAREHOUSE => 'Main Warehouse',
            self::STORE => 'Retail Store',
            self::TRANSIT => 'In Transit',
        };
    }
}
