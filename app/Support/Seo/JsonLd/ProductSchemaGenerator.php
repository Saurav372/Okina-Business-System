<?php

namespace App\Support\Seo\JsonLd;

use App\Contracts\SeoableContract;
use App\Models\Product;
use App\Support\Seo\Presenters\ProductSeoPresenter;

class ProductSchemaGenerator
{
    /**
     * Generate Schema.org Product structured data array.
     */
    public function generate(SeoableContract $subject, ProductSeoPresenter $presenter): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $presenter->metaTitle(),
            'description' => $presenter->metaDescription() ?? '',
            'url' => $presenter->canonical(),
            'brand' => [
                '@type' => 'Brand',
                'name' => config('app.name', 'Okina Craft'),
            ],
        ];

        // Image
        $image = $presenter->ogImage();
        if ($image) {
            $schema['image'] = [$image['url']];
        }

        // Product specific details when subject is a Product model
        if ($subject instanceof Product) {
            if ($subject->relationLoaded('category') && $subject->category) {
                $schema['category'] = $subject->category->name;
            }

            // Offers
            $price = number_format(($subject->base_price_minor ?? 0) / 100, 2, '.', '');
            $currency = $subject->currency ?? config('app.currency', 'INR');
            $availability = $this->determineAvailability($subject);

            $offer = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => $currency,
                'availability' => $availability,
                'url' => $presenter->canonical(),
            ];

            // Primary SKU code if present
            if ($subject->relationLoaded('skus') && $subject->skus->isNotEmpty()) {
                $primarySku = $subject->skus->first();
                if ($primarySku && ! empty($primarySku->sku_code)) {
                    $schema['sku'] = $primarySku->sku_code;
                }
            }

            $schema['offers'] = $offer;
        }

        return $schema;
    }

    protected function determineAvailability(Product $product): string
    {
        if ($product->status === Product::STATUS_DISCONTINUED) {
            return 'https://schema.org/Discontinued';
        }

        if ($product->relationLoaded('skus') && $product->skus->isNotEmpty()) {
            $totalStock = $product->skus->sum('stock_quantity');
            $hasStockTracking = $product->skus->contains('track_stock', true);

            if ($hasStockTracking && $totalStock <= 0) {
                return 'https://schema.org/OutOfStock';
            }
        } elseif ($product->status === Product::STATUS_OUT_OF_STOCK) {
            return 'https://schema.org/OutOfStock';
        }

        return 'https://schema.org/InStock';
    }
}
