<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotation_id',
    'product_sku_id',
    'product_id_snapshot',
    'product_name_snapshot',
    'sku_code_snapshot',
    'item_name',
    'selected_options_snapshot',
    'customization_snapshot',
    'quantity',
    'unit_price_minor',
    'discount_amount_minor',
    'tax_amount_minor',
    'line_subtotal_minor',
    'line_total_minor',
    'currency',
    'sort_order',
    'customer_note',
    'internal_notes',
])]
class QuotationItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'selected_options_snapshot' => 'array',
            'customization_snapshot' => 'array',
            'quantity' => 'integer',
            'unit_price_minor' => 'integer',
            'discount_amount_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'line_subtotal_minor' => 'integer',
            'line_total_minor' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    // --------------------------------------------------------------- relations

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id');
    }
}
