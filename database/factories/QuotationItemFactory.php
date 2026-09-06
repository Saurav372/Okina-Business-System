<?php

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationItem>
 */
class QuotationItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_id' => Quotation::factory(),
            'product_sku_id' => null,
            'product_id_snapshot' => null,
            'product_name_snapshot' => fake()->words(3, true),
            'sku_code_snapshot' => null,
            'item_name' => fake()->words(3, true),
            'selected_options_snapshot' => null,
            'customization_snapshot' => null,
            'quantity' => fake()->numberBetween(1, 50),
            'unit_price_minor' => fake()->numberBetween(500, 10000),
            'discount_amount_minor' => 0,
            'tax_amount_minor' => 0,
            'line_subtotal_minor' => 5000,
            'line_total_minor' => 5000,
            'currency' => 'INR',
            'sort_order' => 0,
            'customer_note' => null,
            'internal_notes' => null,
        ];
    }
}
