<?php

namespace App\Support\Inventory;

use App\Contracts\InventoryMetricsProvider;

class InventoryDashboardMetrics implements InventoryMetricsProvider
{
    /**
     * Compute live database metrics ignoring soft-deleted records.
     */
    public function getMetrics(?string $locationId = null): InventoryMetricsDTO
    {
        $baseQuery = InventoryQueryBuilder::baseQuery($locationId);

        $totalSkus = (clone $baseQuery)->count();

        $inStockCount = InventoryQueryBuilder::applyStatus(clone $baseQuery, 'in_stock')->count();
        $lowStockCount = InventoryQueryBuilder::applyStatus(clone $baseQuery, 'low_stock')->count();
        $outOfStockCount = InventoryQueryBuilder::applyStatus(clone $baseQuery, 'out_of_stock')->count();

        $totalOnHandUnits = (int) (clone $baseQuery)->sum('on_hand_quantity');

        return new InventoryMetricsDTO(
            totalSkus: $totalSkus,
            inStockCount: $inStockCount,
            lowStockCount: $lowStockCount,
            outOfStockCount: $outOfStockCount,
            totalOnHandUnits: $totalOnHandUnits,
        );
    }
}
