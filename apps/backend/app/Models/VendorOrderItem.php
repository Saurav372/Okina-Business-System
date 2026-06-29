<?php

namespace App\Models;

use App\Exceptions\InvalidPurchaseOrderExpectedDateException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_sku_id',
    'quantity_ordered',
    'unit_cost_minor',
    'tax_amount_minor',
    'expected_at',
    'notes',
])]
class VendorOrderItem extends Model
{
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
