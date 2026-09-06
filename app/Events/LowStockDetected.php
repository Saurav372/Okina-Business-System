<?php

namespace App\Events;

use App\Models\InventoryMovement;
use App\Models\ProductSku;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ProductSku $sku,
        public readonly int $availableQuantity,
        public readonly int $threshold,
        public readonly InventoryMovement $movement
    ) {}
}
