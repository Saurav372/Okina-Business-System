<?php

namespace App\Support\Inventory;

readonly class InventoryMetricsDTO
{
    public function __construct(
        public int $totalSkus,
        public int $inStockCount,
        public int $lowStockCount,
        public int $outOfStockCount,
        public int $totalOnHandUnits,
    ) {}

    public function toArray(): array
    {
        return [
            'total_skus' => $this->totalSkus,
            'in_stock_count' => $this->inStockCount,
            'low_stock_count' => $this->lowStockCount,
            'out_of_stock_count' => $this->outOfStockCount,
            'total_on_hand_units' => $this->totalOnHandUnits,
        ];
    }
}
