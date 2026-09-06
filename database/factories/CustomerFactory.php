<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'public_id' => 'CUS-'.strtoupper(Str::random(10)),
            'customer_type' => 'individual',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => $firstName.' '.$lastName,
            'company_name' => null,
            'name' => $firstName.' '.$lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('9#########'),
            'whatsapp_phone' => null,
            'status' => 'active',
            'source' => 'website',
            'accepts_marketing' => false,
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'last_login_at' => null,
        ];
    }
}
