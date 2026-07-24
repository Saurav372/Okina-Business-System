<?php

namespace App\Support\Inventory;

readonly class StockAdjustmentResultDTO
{
    public function __construct(
        public string $skuCode,
        public int $previousOnHand,
        public int $newOnHand,
        public int $deltaOnHand,
        public int $previousReserved,
        public int $newReserved,
        public int $deltaReserved,
        public int $movementId,
    ) {}

    public function getFormattedDelta(): string
    {
        return $this->deltaOnHand >= 0 ? "+{$this->deltaOnHand}" : "{$this->deltaOnHand}";
    }

    public function getSummaryText(): string
    {
        return "Stock adjusted for SKU {$this->skuCode}: On Hand {$this->previousOnHand} → {$this->newOnHand} ({$this->getFormattedDelta()})";
    }
}
