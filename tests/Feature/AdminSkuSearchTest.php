<?php

namespace Tests\Feature;

use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSkuSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_search_skus(): void
    {
        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ],
        );

        $user = User::factory()->create();
        $user->assignRole($dashboardRole);

        $sku = ProductSku::factory()->create(['sku_code' => 'TEST-SKU-123']);

        $this->actingAs($user)
            ->getJson(route('admin.skus.search', ['q' => 'TEST']))
            ->assertStatus(200)
            ->assertJsonFragment(['sku_code' => 'TEST-SKU-123']);
    }
}
