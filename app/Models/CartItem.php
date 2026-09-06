<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'cart_id',
    'product_id',
    'sku_id',
    'quantity',
    'product_name_snapshot',
    'product_slug_snapshot',
    'sku_code_snapshot',
    'customization_fingerprint',
    'customization_snapshot',
])]
class CartItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'customization_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CartItem $item): void {
            $item->public_id ??= 'CRTITEM-'.Str::upper(Str::random(12));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
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
