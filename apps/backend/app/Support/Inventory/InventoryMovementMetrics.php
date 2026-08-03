<?php

namespace App\Support\Inventory;

use App\Enums\InventoryDirection;

class InventoryMovementMetrics
{
    public int $totalMovements = 0;

    public int $totalInboundUnits = 0;

    public int $totalOutboundUnits = 0;

    public int $netStockDelta = 0;

    public int $totalMovementsCount = 0;

    public int $totalStockInQuantity = 0;

    public int $totalStockOutQuantity = 0;

    public int $netQuantityDelta = 0;

    public function __construct(InventoryMovementFilters $filters)
    {
        $baseQuery = InventoryMovementQueryBuilder::buildQuery($filters);

        $this->totalMovements = (clone $baseQuery)->count();
        $this->totalMovementsCount = $this->totalMovements;

        // Calculate Inbound units (Direction IN or positive ADJUST)
        $inboundQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('direction', InventoryDirection::IN->value)
                ->orWhere(function ($sub) {
                    $sub->where('direction', InventoryDirection::ADJUST->value)
                        ->whereRaw('after_on_hand_quantity > before_on_hand_quantity');
                });
        });
        $this->totalInboundUnits = (int) $inboundQuery->sum('quantity');
        $this->totalStockInQuantity = $this->totalInboundUnits;

        // Calculate Outbound units (Direction OUT or negative ADJUST)
        $outboundQuery = (clone $baseQuery)->where(function ($q) {
            $q->where('direction', InventoryDirection::OUT->value)
                ->orWhere(function ($sub) {
                    $sub->where('direction', InventoryDirection::ADJUST->value)
                        ->whereRaw('after_on_hand_quantity < before_on_hand_quantity');
                });
        });
        $this->totalOutboundUnits = (int) $outboundQuery->sum('quantity');
        $this->totalStockOutQuantity = $this->totalOutboundUnits;

        $this->netStockDelta = $this->totalInboundUnits - $this->totalOutboundUnits;
        $this->netQuantityDelta = $this->netStockDelta;
    }

    public function __get(string $name): mixed
    {
        return match ($name) {
            'totalMovementsCount' => $this->totalMovements,
            'totalStockInQuantity' => $this->totalInboundUnits,
            'totalStockOutQuantity' => $this->totalOutboundUnits,
            'netQuantityDelta' => $this->netStockDelta,
            default => null,
        };
    }
}
