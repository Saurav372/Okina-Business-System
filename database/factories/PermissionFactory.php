<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $group = fake()->randomElement([
            'users',
            'roles',
            'customers',
            'products',
            'orders',
            'quotations',
            'payments',
            'refunds',
            'inventory',
            'vendors',
            'files',
            'crm',
            'production',
            'shipping',
            'finance',
            'reports',
            'audit',
            'settings',
        ]);

        $slug = $group.'.'.fake()->word();

        return [
            'name' => fake()->words(3, true),
            'slug' => str($slug)->snake()->toString(),
            'group' => $group,
            'guard_name' => 'web',
            'description' => fake()->sentence(),
            'is_sensitive' => false,
        ];
    }
}
