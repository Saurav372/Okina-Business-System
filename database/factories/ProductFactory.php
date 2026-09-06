<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'primary_category_id' => ProductCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'product_type' => Product::TYPE_VARIABLE,
            'customization_mode' => Product::CUSTOMIZATION_OPTIONAL,
            'fulfillment_type' => Product::FULFILLMENT_STOCKED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'direct_checkout_enabled' => true,
            'quote_enabled' => true,
            'min_order_quantity' => 1,
            'max_order_quantity' => null,
            'bulk_threshold_quantity' => null,
            'base_price_minor' => fake()->numberBetween(1000, 10000),
            'currency' => 'INR',
            'seo_title' => $name,
            'seo_description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 100),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_DRAFT,
            'visibility' => Product::VISIBILITY_PRIVATE,
            'direct_checkout_enabled' => false,
        ]);
    }

    public function bulkOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_BULK_ONLY,
            'quote_enabled' => true,
            'direct_checkout_enabled' => false,
        ]);
    }
}
