<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group_name' => fake()->randomElement(Setting::GROUPS),
            'key' => fake()->unique()->slug(2, '_'),
            'value' => fake()->sentence(),
            'value_type' => Setting::TYPE_STRING,
            'description' => fake()->sentence(),
            'is_secret' => false,
        ];
    }
}
