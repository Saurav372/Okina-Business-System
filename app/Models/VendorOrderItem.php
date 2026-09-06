<?php

namespace App\Models;

use App\Exceptions\InvalidPurchaseOrderExpectedDateException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vendor_order_id',
    'product_sku_id',
    'sku_code_snapshot',
    'quantity_ordered',
    'quantity_received',
    'unit_cost_minor',
    'tax_amount_minor',
    'line_total_minor',
    'expected_at',
    'notes',
])]
class VendorOrderItem extends Model
{
    protected static function booted(): void
    {
        static::saving(function (VendorOrderItem $item) {
            if (empty($item->sku_code_snapshot) && $item->product_sku_id) {
                $sku = ProductSku::find($item->product_sku_id);
                $item->sku_code_snapshot = $sku?->sku_code ?? 'SKU-SNAPSHOT';
            }
            if ($item->line_total_minor === null || $item->isDirty(['quantity_ordered', 'unit_cost_minor', 'tax_amount_minor'])) {
                $item->line_total_minor = $item->calculateLineTotal();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'vendor_order_id' => 'integer',
            'product_sku_id' => 'integer',
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
            'unit_cost_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'line_total_minor' => 'integer',
            'expected_at' => 'datetime',
        ];
    }

    /**
     * Pure calculation of line total amount.
     */
    public function calculateLineTotal(): int
    {
        $qty = $this->quantity_ordered ?? 0;
        $cost = $this->unit_cost_minor ?? 0;
        $tax = $this->tax_amount_minor ?? 0;

        return ($qty * $cost) + $tax;
    }

    /**
     * Remaining quantity yet to be received for this line item.
     * Single source of truth: ordered − received (min 0).
     */
    public function remainingQuantity(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    /**
     * Whether this line item has been fully received.
     */
    public function isFullyReceived(): bool
    {
        return $this->remainingQuantity() === 0;
    }

    /**
     * Explicit domain method to change expected_at date.
     */
    public function changeExpectedAt(?Carbon $expectedAt, VendorOrder $purchaseOrder): void
    {
        if ($expectedAt && $purchaseOrder->ordered_at && $expectedAt->lt($purchaseOrder->ordered_at)) {
            throw new InvalidPurchaseOrderExpectedDateException(
                'Expected delivery date cannot be prior to parent ordered date.'
            );
        }

        $this->expected_at = $expectedAt;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class, 'vendor_order_id');
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }
}
