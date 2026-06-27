<?php

namespace App\Observers;

use App\Models\InventoryItem;
use App\Models\ProductSku;

class ProductSkuObserver
{
    /**
     * Handle the ProductSku "created" event.
     */
    public function created(ProductSku $sku): void
    {
        $initialStock = $sku->stock_quantity ?? 0;

        InventoryItem::create([
            'product_sku_id' => $sku->id,
            'on_hand_quantity' => $initialStock,
            'reserved_quantity' => 0,
            'available_quantity' => $initialStock,
            'allow_negative_stock' => false,
        ]);
    }
}
