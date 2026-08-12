<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StorefrontDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $collections = [
                'custom-t-shirts' => ['Custom T-Shirts', 'Everyday cotton tees for teams, events, brands, and personal ideas.'],
                'team-layers' => ['Team Layers', 'Hoodies and sweatshirts for clubs, crews, and cooler days.'],
                'workwear' => ['Workwear', 'Polished custom staples for staff, field teams, and customer-facing crews.'],
                'event-kits' => ['Event Kits', 'Coordinated pieces for college festivals, communities, and one-off gatherings.'],
            ];

            foreach ($collections as $index => $collection) {
                ProductCategory::query()->updateOrCreate(
                    ['slug' => $index],
                    ['name' => $collection[0], 'description' => $collection[1], 'status' => 'active', 'sort_order' => array_search($index, array_keys($collections), true) * 10, 'published_at' => now()->subDay()]
                );
            }

            $products = [
                ['custom-t-shirts', 'Signature Custom Tee', 'signature-custom-tee', 'A soft everyday tee with a clean print surface for logos, event graphics, and team identities.', 69900, 40],
                ['custom-t-shirts', 'Relaxed Heavyweight Tee', 'relaxed-heavyweight-tee', 'A structured oversized tee for creator drops, college groups, and bold front or back artwork.', 89900, 30],
                ['team-layers', 'All-Season Team Hoodie', 'all-season-team-hoodie', 'A comfortable pullover layer for teams, clubs, trips, and staff kits.', 159900, 20],
                ['workwear', 'Studio Polo', 'studio-polo', 'A neat custom polo for hospitality, retail, office, and event teams.', 119900, 25],
            ];

            foreach ($products as $sort => [$categorySlug, $name, $slug, $description, $price, $bulkAt]) {
                $category = ProductCategory::query()->where('slug', $categorySlug)->firstOrFail();
                $product = Product::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'primary_category_id' => $category->id,
                        'name' => $name,
                        'short_description' => $description,
                        'description' => $description.' Each order keeps its size, colour, print position, method, artwork, and notes together for review before production.',
                        'product_type' => Product::TYPE_VARIABLE,
                        'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
                        'fulfillment_type' => Product::FULFILLMENT_MADE_TO_ORDER,
                        'status' => Product::STATUS_ACTIVE,
                        'visibility' => Product::VISIBILITY_PUBLIC,
                        'direct_checkout_enabled' => true,
                        'quote_enabled' => true,
                        'min_order_quantity' => 1,
                        'max_order_quantity' => 500,
                        'bulk_threshold_quantity' => $bulkAt,
                        'base_price_minor' => $price,
                        'currency' => 'INR',
                        'seo_title' => $name.' | Custom Apparel',
                        'seo_description' => $description,
                        'sort_order' => ($sort + 1) * 10,
                        'published_at' => now()->subDay(),
                    ]
                );

                ProductVariant::query()->updateOrCreate(
                    ['product_id' => $product->id, 'code' => 'color'],
                    ['name' => 'Colour', 'display_type' => 'color', 'is_required' => true, 'sort_order' => 10, 'values' => [
                        ['code' => 'ink', 'label' => 'Ink Black', 'sort_order' => 10, 'is_active' => true],
                        ['code' => 'paper', 'label' => 'Paper White', 'sort_order' => 20, 'is_active' => true],
                    ]]
                );
                ProductVariant::query()->updateOrCreate(
                    ['product_id' => $product->id, 'code' => 'size'],
                    ['name' => 'Size', 'display_type' => 'button', 'is_required' => true, 'sort_order' => 20, 'values' => [
                        ['code' => 's', 'label' => 'S', 'sort_order' => 10, 'is_active' => true],
                        ['code' => 'm', 'label' => 'M', 'sort_order' => 20, 'is_active' => true],
                        ['code' => 'l', 'label' => 'L', 'sort_order' => 30, 'is_active' => true],
                    ]]
                );

                foreach (['ink' => 'Ink Black', 'paper' => 'Paper White'] as $colorCode => $colorLabel) {
                    foreach (['s' => 'S', 'm' => 'M', 'l' => 'L'] as $sizeCode => $sizeLabel) {
                        $skuPrice = $price + ($sizeCode === 'l' ? 5000 : 0);
                        ProductSku::withTrashed()->updateOrCreate(
                            ['sku_code' => strtoupper(str_replace('-', '', $slug)).'-'.strtoupper($colorCode).'-'.strtoupper($sizeCode)],
                            [
                                'product_id' => $product->id,
                                'variant_key' => "color:{$colorCode}|size:{$sizeCode}",
                                'option_values' => [['code' => $colorCode, 'label' => $colorLabel], ['code' => $sizeCode, 'label' => $sizeLabel]],
                                'name_suffix' => $colorLabel.' · '.$sizeLabel,
                                'status' => 'active',
                                'direct_checkout_enabled' => true,
                                'quote_required' => false,
                                'track_stock' => false,
                                'stock_quantity' => 0,
                                'allow_backorder' => true,
                                'price_minor' => $skuPrice,
                                'compare_at_price_minor' => null,
                                'sort_order' => ($colorCode === 'ink' ? 0 : 10) + array_search($sizeCode, ['s', 'm', 'l'], true),
                                'deleted_at' => null,
                            ]
                        );
                    }
                }
            }
        });
    }
}
