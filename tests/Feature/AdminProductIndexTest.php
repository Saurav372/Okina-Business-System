<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        Permission::query()->updateOrCreate(['slug' => 'products.view'], [
            'name' => 'Products View',
            'group' => 'products',
            'guard_name' => 'web',
            'description' => 'View products',
            'is_sensitive' => false,
        ]);

        Permission::query()->updateOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $role = Role::query()->updateOrCreate(['slug' => 'inventory_staff'], [
            'name' => 'Inventory Staff',
            'guard_name' => 'web',
            'description' => 'Inventory staff role',
            'is_system' => true,
            'sort_order' => 0,
        ]);

        $role->permissions()->sync(
            Permission::query()->whereIn('slug', ['products.view', 'dashboard.access'])->pluck('id')->all()
        );
    }

    public function test_unauthorized_users_are_denied_access(): void
    {
        // Guest
        $this->get(route('admin.products.index'))->assertStatus(302);

        // User without dashboard access or products view permission
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser)
            ->get(route('admin.products.index'))
            ->assertStatus(302); // EnsureDashboardAccess redirects to login since they have no dashboard role
    }

    public function test_authorized_user_can_view_product_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        Product::factory()->create(['name' => 'Acoustic Guitar']);

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Acoustic Guitar');
    }

    public function test_can_search_products_by_name(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        Product::factory()->create(['name' => 'Red Mug']);
        Product::factory()->create(['name' => 'Blue Pen']);

        $response = $this->actingAs($user)->get(route('admin.products.index', ['search' => 'Mug']));

        $response->assertStatus(200);
        $response->assertSee('Red Mug');
        $response->assertDontSee('Blue Pen');
    }

    public function test_can_filter_products_by_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        Product::factory()->create(['name' => 'Active Item', 'status' => Product::STATUS_ACTIVE]);
        Product::factory()->create(['name' => 'Draft Item', 'status' => Product::STATUS_DRAFT]);

        $response = $this->actingAs($user)->get(route('admin.products.index', ['status' => Product::STATUS_ACTIVE]));

        $response->assertStatus(200);
        $response->assertSee('Active Item');
        $response->assertDontSee('Draft Item');
    }

    public function test_can_sort_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        Product::factory()->create(['name' => 'Product B', 'created_at' => now()->subDay()]);
        Product::factory()->create(['name' => 'Product A', 'created_at' => now()]);

        // Name Ascending
        $response = $this->actingAs($user)->get(route('admin.products.index', ['sort' => 'name', 'direction' => 'asc']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Product A', 'Product B']); // Note: Depending on display order layout
    }

    public function test_empty_state_renders_when_no_products_match(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        $response = $this->actingAs($user)->get(route('admin.products.index', ['search' => 'NonExistentProduct']));

        $response->assertStatus(200);
        $response->assertSee('No Products Found');
    }
}
