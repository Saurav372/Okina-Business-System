<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Size', 'Color', 'Capacity', 'Material']);
        $code = Str::slug($name).'-'.fake()->unique()->bothify('??##');

        return [
            'product_id' => Product::factory(),
            'name' => $name,
            'code' => $code,
            'display_type' => fake()->randomElement(['select', 'swatch', 'button', 'radio']),
            'values' => [
                [
                    'code' => 'default',
                    'label' => 'Default',
                    'sort_order' => 10,
                    'is_active' => true,
                    'metadata' => [],
                ],
            ],
            'is_required' => true,
            'sort_order' => 0,
        ];
    }
}
