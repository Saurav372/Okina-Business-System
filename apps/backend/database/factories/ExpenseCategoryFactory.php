<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        $code = Str::upper(preg_replace('/[^A-Z0-9]+/', '_', Str::upper($name)));

        return [
            'name' => ucwords($name),
            'code' => $code,
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
