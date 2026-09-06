<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductSku>
 */
class ProductSkuFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory();

        return [
            'product_id' => $product,
            'sku_code' => 'SKU-'.strtoupper(Str::random(10)),
            'variant_key' => 'variant-'.fake()->unique()->bothify('??##'),
            'option_values' => [],
            'name_suffix' => null,
            'barcode' => null,
            'status' => 'active',
            'direct_checkout_enabled' => true,
            'quote_required' => false,
            'track_stock' => true,
            'stock_quantity' => 0,
            'low_stock_threshold' => null,
            'allow_backorder' => false,
            'price_minor' => fake()->numberBetween(1000, 10000),
            'compare_at_price_minor' => null,
            'weight_grams' => null,
            'length_mm' => null,
            'width_mm' => null,
            'height_mm' => null,
            'sort_order' => 0,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'out_of_stock',
            'stock_quantity' => 0,
            'direct_checkout_enabled' => false,
        ]);
    }
}
