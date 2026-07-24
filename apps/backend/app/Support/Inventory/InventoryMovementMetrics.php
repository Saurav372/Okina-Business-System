<?php

namespace App\Support\Inventory;

use App\Enums\InventoryDirection;

class InventoryMovementMetrics
{
    public int $totalMovements = 0;

    public int $totalInboundUnits = 0;

    public int $totalOutboundUnits = 0;

    public int $netStockDelta = 0;

    public function __construct(InventoryMovementFilters $filters)
    {
        $baseQuery = InventoryMovementQueryBuilder::buildQuery($filters);

        $this->totalMovements = (clone $baseQuery)->count();

        // Calculate Inbound units (Direction IN or positive ADJUST)
        $inboundQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('direction', InventoryDirection::IN->value)
                ->orWhere(function ($sub) {
                    $sub->where('direction', InventoryDirection::ADJUST->value)
                        ->whereRaw('after_on_hand_quantity > before_on_hand_quantity');
                });
        });
        $this->totalInboundUnits = (int) $inboundQuery->sum('quantity');

        // Calculate Outbound units (Direction OUT or negative ADJUST)
        $outboundQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('direction', InventoryDirection::OUT->value)
                ->orWhere(function ($sub) {
                    $sub->where('direction', InventoryDirection::ADJUST->value)
                        ->whereRaw('after_on_hand_quantity < before_on_hand_quantity');
                });
        });
        $this->totalOutboundUnits = (int) $outboundQuery->sum('quantity');

        $this->netStockDelta = $this->totalInboundUnits - $this->totalOutboundUnits;
    }
}
