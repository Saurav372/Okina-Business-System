<?php

namespace Database\Factories;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $sku = ProductSku::factory();

        return [
            'product_sku_id' => $sku,
            'inventory_item_id' => function (array $attributes) {
                return InventoryItem::query()->firstOrCreate(
                    ['product_sku_id' => $attributes['product_sku_id']],
                    [
                        'on_hand_quantity' => 100,
                        'reserved_quantity' => 0,
                        'available_quantity' => 100,
                    ]
                )->id;
            },
            'movement_type' => InventoryMovementType::STOCK_IN,
            'direction' => InventoryDirection::IN,
            'quantity' => 10,
            'before_on_hand_quantity' => 90,
            'after_on_hand_quantity' => 100,
            'before_reserved_quantity' => 0,
            'after_reserved_quantity' => 0,
            'before_available_quantity' => 90,
            'after_available_quantity' => 100,
            'reason_code' => InventoryMovementReason::MANUAL_ADJUSTMENT,
            'occurred_at' => now(),
            'notes' => 'Factory movement',
        ];
    }
}
