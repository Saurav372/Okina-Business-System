<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'primary_category_id',
    'name',
    'slug',
    'short_description',
    'description',
    'product_type',
    'customization_mode',
    'fulfillment_type',
    'status',
    'visibility',
    'direct_checkout_enabled',
    'quote_enabled',
    'min_order_quantity',
    'max_order_quantity',
    'bulk_threshold_quantity',
    'base_price_minor',
    'currency',
    'seo_title',
    'seo_description',
    'sort_order',
    'published_at',
])]
#[Hidden(['deleted_at'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_SIMPLE = 'simple';

    public const TYPE_VARIABLE = 'variable';

    public const TYPE_BUNDLE = 'bundle';

    public const CUSTOMIZATION_NONE = 'none';

    public const CUSTOMIZATION_OPTIONAL = 'optional';

    public const CUSTOMIZATION_REQUIRED = 'required';

    public const FULFILLMENT_STOCKED = 'stocked';

    public const FULFILLMENT_MADE_TO_ORDER = 'made_to_order';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_OUT_OF_STOCK = 'out_of_stock';

    public const STATUS_BULK_ONLY = 'bulk_only';

    public const STATUS_DISCONTINUED = 'discontinued';

    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PRIVATE = 'private';

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->slug ??= Str::slug($product->name);
            $product->product_type ??= self::TYPE_SIMPLE;
            $product->customization_mode ??= self::CUSTOMIZATION_NONE;
            $product->fulfillment_type ??= self::FULFILLMENT_STOCKED;
            $product->status ??= self::STATUS_DRAFT;
            $product->visibility ??= self::VISIBILITY_PRIVATE;
            $product->direct_checkout_enabled ??= false;
            $product->quote_enabled ??= true;
            $product->min_order_quantity ??= 1;
            $product->currency ??= 'INR';
        });
    }

    protected function casts(): array
    {
        return [
            'direct_checkout_enabled' => 'boolean',
            'quote_enabled' => 'boolean',
            'min_order_quantity' => 'integer',
            'max_order_quantity' => 'integer',
            'bulk_threshold_quantity' => 'integer',
            'base_price_minor' => 'integer',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('visibility', self::VISIBILITY_PUBLIC)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_OUT_OF_STOCK, self::STATUS_BULK_ONLY])
            ->whereNull('deleted_at')
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'primary_category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC
            && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_OUT_OF_STOCK, self::STATUS_BULK_ONLY], true)
            && $this->deleted_at === null
            && ($this->published_at === null || $this->published_at->isPast());
    }
}
