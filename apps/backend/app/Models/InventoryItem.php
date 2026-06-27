<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * Class InventoryItem
 *
 * Invariants:
 * - reserved_quantity >= 0
 * - on_hand_quantity >= 0 (unless allow_negative_stock is true)
 * - available_quantity = on_hand_quantity - reserved_quantity
 */
class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_sku_id',
        'on_hand_quantity',
        'reserved_quantity',
        'low_stock_threshold',
        'allow_negative_stock',
        'last_movement_at',
    ];

    protected $casts = [
        'product_sku_id' => 'integer',
        'on_hand_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'allow_negative_stock' => 'boolean',
        'last_movement_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryItem $item) {
            $item->recalculateAvailable();
        });
    }

    /**
     * Get the SKU associated with this inventory item.
     */
    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }

    /**
     * Recalculate and set the derived available quantity.
     */
    public function recalculateAvailable(): void
    {
        $this->validateInvariants($this->on_hand_quantity, $this->reserved_quantity);
        $this->available_quantity = $this->on_hand_quantity - $this->reserved_quantity;
    }

    /**
     * Helper to set balance safely.
     */
    public function setBalance(int $onHand, int $reserved): void
    {
        $this->validateInvariants($onHand, $reserved);
        $this->on_hand_quantity = $onHand;
        $this->reserved_quantity = $reserved;
        $this->recalculateAvailable();
    }

    /**
     * Validate business invariants before saving.
     */
    public function validateInvariants(int $onHand, int $reserved): void
    {
        if ($reserved < 0) {
            throw new InvalidArgumentException('Reserved quantity cannot be negative.');
        }

        if ($onHand < 0 && ! $this->allow_negative_stock) {
            throw new InvalidArgumentException('On hand quantity cannot be negative unless negative stock is allowed.');
        }
    }
}
