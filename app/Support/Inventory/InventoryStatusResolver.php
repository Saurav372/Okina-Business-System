<?php

namespace App\Support\Inventory;

use App\Enums\InventoryStatus;

class InventoryStatusResolver
{
    /**
     * Resolve InventoryStatus enum using single-source-of-truth precedence rules:
     * Negative (available < 0 or on_hand < 0) -> Out of Stock (available <= 0) -> Low Stock (available <= threshold) -> In Stock.
     */
    public static function resolve(int $availableQuantity, int $onHandQuantity, ?int $threshold = null): InventoryStatus
    {
        if ($availableQuantity < 0 || $onHandQuantity < 0) {
            return InventoryStatus::NEGATIVE;
        }

        if ($availableQuantity <= 0) {
            return InventoryStatus::OUT_OF_STOCK;
        }

        if ($threshold !== null && $availableQuantity <= $threshold) {
            return InventoryStatus::LOW_STOCK;
        }

        return InventoryStatus::IN_STOCK;
    }
}
