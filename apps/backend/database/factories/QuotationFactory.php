<?php

namespace Database\Factories;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quotation_type' => Quotation::TYPE_MANUAL,
            'status' => Quotation::STATUS_DRAFT,
            'customer_snapshot' => [
                'contact_name' => fake()->name(),
                'email' => fake()->email(),
                'phone' => fake()->phoneNumber(),
            ],
            'subtotal_amount_minor' => 1000,
            'discount_amount_minor' => 0,
            'shipping_amount_minor' => 0,
            'tax_amount_minor' => 0,
            'total_amount_minor' => 1000,
            'currency' => 'INR',
            'current_revision_number' => 1,
        ];
    }
}
