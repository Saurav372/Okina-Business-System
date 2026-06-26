<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'amount_minor' => $this->faker->numberBetween(100, 1000000), // 1.00 to 10,000.00 INR
            'currency' => 'INR',
            'notes' => $this->faker->sentence(),
            'recorded_by_user_id' => User::factory(),
            'reference' => 'REF-'.Str::upper(Str::random(8)),
            'status' => Expense::STATUS_DRAFT,
            'occurred_at' => $this->faker->dateTimeBetween('-30 days'),
        ];
    }
}
