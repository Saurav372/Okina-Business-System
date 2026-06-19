<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $slug = fake()->unique()->slug();

        return [
            'name' => fake()->words(2, true),
            'slug' => str($slug)->snake()->toString(),
            'guard_name' => 'web',
            'description' => fake()->sentence(),
            'is_system' => false,
            'sort_order' => 0,
        ];
    }
}
