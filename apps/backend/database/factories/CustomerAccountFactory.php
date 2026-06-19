<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerAccount>
 */
class CustomerAccountFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'customer_id' => Customer::factory(),
            'email' => $email,
            'normalized_email' => CustomerAccount::normalizeEmail($email),
            'password' => static::$password ??= Hash::make('password'),
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'failed_login_attempts' => 0,
            'remember_token' => Str::random(10),
        ];
    }

    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerAccount::STATUS_PENDING_VERIFICATION,
            'email_verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerAccount::STATUS_SUSPENDED,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CustomerAccount::STATUS_DISABLED,
            'disabled_at' => now(),
        ]);
    }
}
