<?php

namespace App\Support\Inventory\Transfers;

use App\Enums\WarehouseTransferStatus;

class WarehouseTransferMetrics
{
    public int $totalTransfersCount = 0;

    public int $activeInTransitCount = 0;

    public int $totalTransferredUnits = 0;

    public int $completedTransfersCount = 0;

    public function __construct(WarehouseTransferFilters $filters)
    {
        $baseQuery = WarehouseTransferQueryBuilder::buildQuery($filters);

        $this->totalTransfersCount = (clone $baseQuery)->count();

        $this->activeInTransitCount = (clone $baseQuery)
            ->where('status', WarehouseTransferStatus::IN_TRANSIT->value)
            ->count();

        $this->completedTransfersCount = (clone $baseQuery)
            ->where('status', WarehouseTransferStatus::COMPLETED->value)
            ->count();

        $this->totalTransferredUnits = (int) (clone $baseQuery)
            ->whereIn('status', [WarehouseTransferStatus::IN_TRANSIT->value, WarehouseTransferStatus::COMPLETED->value])
            ->sum('quantity');
    }
}
