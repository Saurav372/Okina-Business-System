<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\ProductSku;
use Illuminate\Support\Facades\DB;

class InventoryBalanceService
{
    /**
     * Set the stock balance for a Product SKU atomically.
     */
    public function setBalance(ProductSku $sku, int $onHand, int $reserved): void
    {
        DB::transaction(function () use ($sku, $onHand, $reserved) {
            // Find and lock the inventory item row for update
            /** @var InventoryItem $inventoryItem */
            $inventoryItem = InventoryItem::query()
                ->where('product_sku_id', $sku->id)
                ->lockForUpdate()
                ->firstOrCreate([
                    'product_sku_id' => $sku->id,
                ]);

            // Mutate balance (validates invariants internally)
            $inventoryItem->setBalance($onHand, $reserved);
            $inventoryItem->save();

            // Synchronize the parent SKU's cached stock quantity
            $sku->stock_quantity = $inventoryItem->available_quantity;
            $sku->save();
        });
    }
}
