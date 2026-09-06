<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'order_id',
    'public_id',
    'product_id',
    'sku_id',
    'quantity',
    'product_name_snapshot',
    'product_slug_snapshot',
    'sku_code_snapshot',
    'customization_fingerprint',
    'customization_snapshot',
    'unit_price_minor',
    'line_subtotal_minor',
    'line_total_minor',
    'currency',
    'price_source',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'customization_snapshot' => 'array',
            'unit_price_minor' => 'integer',
            'line_subtotal_minor' => 'integer',
            'line_total_minor' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OrderItem $item): void {
            $item->public_id ??= 'ORDITEM-'.Str::upper(Str::random(12));
            $item->currency ??= 'INR';
            $item->price_source ??= 'unpriced';
            $item->unit_price_minor ??= 0;
            $item->line_subtotal_minor ??= 0;
            $item->line_total_minor ??= 0;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}
