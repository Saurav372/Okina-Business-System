<?php

namespace App\Support\Products;

use App\Contracts\PublicCatalogContract;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use Illuminate\Support\Carbon;

readonly class PublicCatalogRules implements PublicCatalogContract
{
    public function categories(): array
    {
        return ProductCategory::query()
            ->publiclyVisible()
            ->withCount(['products' => fn ($query) => $query->publiclyVisible()])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ProductCategory $category): array => $this->categoryPayload($category))
            ->all();
    }

    public function category(string $slug): ?array
    {
        $category = ProductCategory::query()
            ->publiclyVisible()
            ->withCount(['products' => fn ($query) => $query->publiclyVisible()])
            ->where('slug', $slug)
            ->first();

        return $category === null ? null : $this->categoryPayload($category);
    }

    public function categoryProducts(string $slug): array
    {
        return Product::query()
            ->publiclyVisible()
            ->whereHas('category', fn ($query) => $query->publiclyVisible()->where('slug', $slug))
            ->with([
                'category',
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product): array => $this->productPayload($product))
            ->all();
    }

    public function products(): array
    {
        return Product::query()
            ->publiclyVisible()
            ->with([
                'category',
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $product): array => $this->productPayload($product))
            ->all();
    }

    public function product(string $slug): ?array
    {
        $product = Product::query()
            ->publiclyVisible()
            ->with([
                'category',
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->where('slug', $slug)
            ->first();

        return $product === null ? null : $this->productPayload($product);
    }

    public function guidance(): array
    {
        $catalog = new PublicCatalogCatalog;

        return [
            ...$catalog->endpoints(),
            'public_category_fields' => $this->publicCategoryFields(),
            'public_product_fields' => $this->publicProductFields(),
            'public_sku_fields' => $this->publicSkuFields(),
            'astro_usage' => array_merge($catalog->guidance(), [
                'Use the category list for navigation and collection pages.',
                'Use product detail responses for product pages and SKU selection.',
                'Do not depend on raw stock counts or internal database ids.',
            ]),
        ];
    }

    private function categoryPayload(ProductCategory $category): array
    {
        return [
            'slug' => $category->slug,
            'name' => $category->name,
            'description' => $category->description,
            'seo_title' => $category->seo_title,
            'seo_description' => $category->seo_description,
            'sort_order' => $category->sort_order,
            'published_at' => $this->formatTimestamp($category->published_at),
            'products_count' => $category->products_count,
        ];
    }

    private function productPayload(Product $product): array
    {
        return [
            'slug' => $product->slug,
            'name' => $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'product_type' => $product->product_type,
            'customization_mode' => $product->customization_mode,
            'fulfillment_type' => $product->fulfillment_type,
            'status' => $product->status,
            'visibility' => $product->visibility,
            'direct_checkout_enabled' => $product->direct_checkout_enabled,
            'quote_enabled' => $product->quote_enabled,
            'min_order_quantity' => $product->min_order_quantity,
            'max_order_quantity' => $product->max_order_quantity,
            'bulk_threshold_quantity' => $product->bulk_threshold_quantity,
            'base_price_minor' => $product->base_price_minor,
            'currency' => $product->currency,
            'seo_title' => $product->seo_title,
            'seo_description' => $product->seo_description,
            'sort_order' => $product->sort_order,
            'published_at' => $this->formatTimestamp($product->published_at),
            'category' => $product->category === null ? null : [
                'slug' => $product->category->slug,
                'name' => $product->category->name,
                'seo_title' => $product->category->seo_title,
            ],
            'variants' => $product->variants
                ->map(fn ($variant): array => [
                    'name' => $variant->name,
                    'code' => $variant->code,
                    'display_type' => $variant->display_type,
                    'values' => $variant->values,
                    'is_required' => $variant->is_required,
                    'sort_order' => $variant->sort_order,
                ])
                ->values()
                ->all(),
            'skus' => $product->skus
                ->map(fn (ProductSku $sku): array => $this->skuPayload($sku))
                ->values()
                ->all(),
        ];
    }

    private function skuPayload(ProductSku $sku): array
    {
        return [
            'sku_code' => $sku->sku_code,
            'variant_key' => $sku->variant_key,
            'option_values' => $sku->option_values,
            'name_suffix' => $sku->name_suffix,
            'status' => $sku->status,
            'direct_checkout_enabled' => $sku->direct_checkout_enabled,
            'quote_required' => $sku->quote_required,
            'track_stock' => $sku->track_stock,
            'allow_backorder' => $sku->allow_backorder,
            'price_minor' => $sku->price_minor,
            'compare_at_price_minor' => $sku->compare_at_price_minor,
            'weight_grams' => $sku->weight_grams,
            'dimensions_mm' => [
                'length' => $sku->length_mm,
                'width' => $sku->width_mm,
                'height' => $sku->height_mm,
            ],
            'sort_order' => $sku->sort_order,
            'availability' => $this->skuAvailability($sku),
        ];
    }

    private function skuAvailability(ProductSku $sku): array
    {
        $stockQuantity = (int) $sku->stock_quantity;
        $hasStock = ! $sku->track_stock || $stockQuantity > 0;
        $isLowStock = $sku->track_stock
            && $sku->low_stock_threshold !== null
            && $stockQuantity <= $sku->low_stock_threshold;

        return [
            'available_for_checkout' => $sku->direct_checkout_enabled && ($hasStock || $sku->allow_backorder),
            'requires_quote' => $sku->quote_required,
            'is_in_stock' => $hasStock,
            'is_low_stock' => $isLowStock,
        ];
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if (! ($value instanceof Carbon)) {
            return null;
        }

        return $value->toIso8601String();
    }

    private function publicCategoryFields(): array
    {
        return [
            'slug',
            'name',
            'description',
            'seo_title',
            'seo_description',
            'sort_order',
            'published_at',
            'products_count',
        ];
    }

    private function publicProductFields(): array
    {
        return [
            'slug',
            'name',
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
            'category',
            'variants',
            'skus',
        ];
    }

    private function publicSkuFields(): array
    {
        return [
            'sku_code',
            'variant_key',
            'option_values',
            'name_suffix',
            'status',
            'direct_checkout_enabled',
            'quote_required',
            'track_stock',
            'allow_backorder',
            'price_minor',
            'compare_at_price_minor',
            'weight_grams',
            'dimensions_mm',
            'sort_order',
            'availability',
        ];
    }
}
