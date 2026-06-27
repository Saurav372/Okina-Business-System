<?php

namespace App\Models;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_sku_id',
        'inventory_item_id',
        'order_id',
        'order_item_id',
        'vendor_order_id',
        'vendor_order_item_id',
        'purchase_stock_in_id',
        'movement_type',
        'direction',
        'quantity',
        'before_on_hand_quantity',
        'after_on_hand_quantity',
        'before_reserved_quantity',
        'after_reserved_quantity',
        'before_available_quantity',
        'after_available_quantity',
        'reason_code',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'created_by_user_id',
        'occurred_at',
        'notes',
    ];

    protected $casts = [
        'product_sku_id' => 'integer',
        'inventory_item_id' => 'integer',
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'vendor_order_id' => 'integer',
        'vendor_order_item_id' => 'integer',
        'purchase_stock_in_id' => 'integer',
        'movement_type' => InventoryMovementType::class,
        'direction' => InventoryDirection::class,
        'quantity' => 'integer',
        'before_on_hand_quantity' => 'integer',
        'after_on_hand_quantity' => 'integer',
        'before_reserved_quantity' => 'integer',
        'after_reserved_quantity' => 'integer',
        'before_available_quantity' => 'integer',
        'after_available_quantity' => 'integer',
        'reason_code' => InventoryMovementReason::class,
        'reference_id' => 'integer',
        'created_by_user_id' => 'integer',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Enforce append-only immutability
        static::updating(function (InventoryMovement $movement) {
            throw new LogicException('Inventory movements are append-only and cannot be updated.');
        });

        static::deleting(function (InventoryMovement $movement) {
            throw new LogicException('Inventory movements are append-only and cannot be deleted.');
        });
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
