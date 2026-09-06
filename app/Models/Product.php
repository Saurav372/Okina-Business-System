<?php

namespace App\Models;

use App\Contracts\SeoableContract;
use App\Support\Seo\Presenters\ProductSeoPresenter;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
class Product extends Model implements SeoableContract
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected ?ProductSeoPresenter $seoPresenter = null;

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

    /**
     * All product media ordered by sort_order, then id for stable ordering.
     */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The single cover image (first cover in sort order, then id).
     */
    public function coverMedia(): HasOne
    {
        return $this->hasOne(ProductMedia::class)
            ->where('role', ProductMedia::ROLE_COVER)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Associated ProductSeo model record.
     */
    public function seo(): HasOne
    {
        return $this->hasOne(ProductSeo::class);
    }

    /**
     * Cached ProductSeoPresenter getter.
     */
    public function seoPresenter(): ProductSeoPresenter
    {
        return $this->seoPresenter ??= new ProductSeoPresenter($this);
    }

    public function refresh()
    {
        $this->seoPresenter = null;

        return parent::refresh();
    }

    // --- SeoableContract Implementation ---

    public function getSeo(): ?ProductSeo
    {
        return $this->seo;
    }

    public function getSeoTitleFallback(): string
    {
        return $this->name;
    }

    public function getSeoDescriptionFallback(): ?string
    {
        if (! empty($this->short_description)) {
            return $this->short_description;
        }

        if (! empty($this->description)) {
            return Str::limit(strip_tags($this->description), 160);
        }

        return null;
    }

    public function getSeoCanonicalUrlFallback(): string
    {
        return url('/products/'.($this->slug ?? Str::slug($this->name)));
    }

    public function getSeoImageFallback(): ?StoredFile
    {
        // 1. Cover media file
        if ($this->relationLoaded('coverMedia') && $this->coverMedia && $this->coverMedia->file) {
            return $this->coverMedia->file;
        }

        if (! $this->relationLoaded('coverMedia')) {
            $cover = $this->coverMedia()->with('file')->first();
            if ($cover && $cover->file) {
                return $cover->file;
            }
        }

        // 2. Primary / first media file
        if ($this->relationLoaded('media') && $this->media->isNotEmpty()) {
            $firstMedia = $this->media->first();
            if ($firstMedia && $firstMedia->file) {
                return $firstMedia->file;
            }
        }

        return null;
    }

    public function getSeoBreadcrumbs(): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'Products', 'url' => url('/products')],
        ];

        if ($this->relationLoaded('category') && $this->category) {
            $breadcrumbs[] = [
                'name' => $this->category->name,
                'url' => url('/categories/'.$this->category->slug),
            ];
        }

        $breadcrumbs[] = [
            'name' => $this->name,
            'url' => $this->getSeoCanonicalUrlFallback(),
        ];

        return $breadcrumbs;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC
            && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_OUT_OF_STOCK, self::STATUS_BULK_ONLY], true)
            && $this->deleted_at === null
            && ($this->published_at === null || $this->published_at->isPast());
    }
}
