<?php

namespace App\Models;

use Database\Factories\ProductSkuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

#[Fillable([
    'product_id',
    'sku_code',
    'variant_key',
    'option_values',
    'name_suffix',
    'barcode',
    'status',
    'direct_checkout_enabled',
    'quote_required',
    'track_stock',
    'stock_quantity',
    'low_stock_threshold',
    'allow_backorder',
    'price_minor',
    'compare_at_price_minor',
    'weight_grams',
    'length_mm',
    'width_mm',
    'height_mm',
    'sort_order',
])]
#[Hidden(['deleted_at'])]
class ProductSku extends Model
{
    /** @use HasFactory<ProductSkuFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'option_values' => 'array',
            'direct_checkout_enabled' => 'boolean',
            'quote_required' => 'boolean',
            'track_stock' => 'boolean',
            'allow_backorder' => 'boolean',
            'price_minor' => 'integer',
            'compare_at_price_minor' => 'integer',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'weight_grams' => 'integer',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProductSku $sku): void {
            $sku->variant_key ??= 'default';
            $sku->sku_code ??= 'SKU-'.strtoupper(Str::random(10));
            $sku->status ??= 'active';
            $sku->direct_checkout_enabled ??= true;
            $sku->quote_required ??= false;
            $sku->track_stock ??= true;
            $sku->stock_quantity ??= 0;
            $sku->allow_backorder ??= false;
        });

        static::deleting(function (ProductSku $sku): void {
            if ($sku->vendorOrderItems()->exists()) {
                // Throw a QueryException to emulate DB restrict behavior
                $connectionName = $sku->getConnection()->getName();
                $sql = '';
                $bindings = [];
                $previous = new \Exception('Cannot delete ProductSku with attached VendorOrderItems');
                throw new QueryException($connectionName, $sql, $bindings, $previous);
            }
        });
    }

    public function vendorOrderItems(): HasMany
    {
        return $this->hasMany(VendorOrderItem::class, 'product_sku_id');
    }

    // Existing product relationship retained
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem(): HasOne
    {
        return $this->hasOne(InventoryItem::class, 'product_sku_id');
    }
}
