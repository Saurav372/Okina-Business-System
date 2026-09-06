<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'address_type' => CustomerAddress::TYPE_SHIPPING,
            'label' => 'Home',
            'contact_name' => fake()->name(),
            'phone' => fake()->numerify('9#########'),
            'company_name' => null,
            'gstin' => null,
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'landmark' => fake()->optional()->streetName(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'IN',
            'is_default_shipping' => true,
            'is_default_billing' => false,
            'delivery_notes' => null,
        ];
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'address_type' => CustomerAddress::TYPE_BILLING,
            'is_default_shipping' => false,
            'is_default_billing' => true,
        ]);
    }

    public function both(): static
    {
        return $this->state(fn (array $attributes) => [
            'address_type' => CustomerAddress::TYPE_BOTH,
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);
    }
}
