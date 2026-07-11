<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminProductEditTest extends TestCase
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

        Permission::query()->updateOrCreate(['slug' => 'products.manage'], [
            'name' => 'Products Manage',
            'group' => 'products',
            'guard_name' => 'web',
            'description' => 'Manage products',
            'is_sensitive' => false,
        ]);

        Permission::query()->updateOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $roleInventory = Role::query()->updateOrCreate(['slug' => 'inventory_staff'], [
            'name' => 'Inventory Staff',
            'guard_name' => 'web',
            'description' => 'Inventory staff role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $roleInventory->permissions()->sync(
            Permission::query()->whereIn('slug', ['products.view', 'dashboard.access'])->pluck('id')->all()
        );

        $roleAdmin = Role::query()->updateOrCreate(['slug' => 'admin'], [
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $roleAdmin->permissions()->sync(
            Permission::query()->whereIn('slug', ['products.view', 'products.manage', 'dashboard.access'])->pluck('id')->all()
        );
    }

    public function test_guests_cannot_view_or_submit_product_updates(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.edit', $product))->assertStatus(302);
        $this->put(route('admin.products.update', $product), [])->assertStatus(302);
    }

    public function test_inventory_staff_cannot_edit_or_update_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        $product = Product::factory()->create();

        // Get edit view
        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertStatus(403);

        // Put update request
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'Updated Name',
                'slug' => 'updated-slug',
            ])
            ->assertStatus(403);
    }

    public function test_administrator_can_view_edit_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create(['name' => 'Original Mug']);

        $response = $this->actingAs($user)->get(route('admin.products.edit', $product));

        $response->assertStatus(200);
        $response->assertSee('Original Mug');
    }

    public function test_uniqueness_checks_fail_for_duplicate_slugs(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $productA = Product::factory()->create(['slug' => 'awesome-tshirt']);
        $productB = Product::factory()->create(['slug' => 'basic-hoodie']);

        $response = $this->actingAs($user)->from(route('admin.products.edit', $productB))
            ->put(route('admin.products.update', $productB), [
                'name' => 'Updated Name',
                'slug' => 'awesome-tshirt', // Duplicate
                'product_type' => Product::TYPE_SIMPLE,
                'customization_mode' => Product::CUSTOMIZATION_NONE,
                'fulfillment_type' => Product::FULFILLMENT_STOCKED,
                'status' => Product::STATUS_ACTIVE,
                'visibility' => Product::VISIBILITY_PUBLIC,
                'min_order_quantity' => 1,
                'base_price_minor' => 1000,
                'currency' => 'INR',
                'sort_order' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.products.edit', $productB));
        $response->assertSessionHasErrors('slug');
    }

    public function test_validation_fails_for_inactive_or_missing_categories(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();
        $inactiveCategory = ProductCategory::factory()->create(['status' => 'inactive']);

        $response = $this->actingAs($user)->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), [
                'name' => 'Sample Mug',
                'slug' => 'sample-mug',
                'primary_category_id' => $inactiveCategory->id, // Inactive
                'product_type' => Product::TYPE_SIMPLE,
                'customization_mode' => Product::CUSTOMIZATION_NONE,
                'fulfillment_type' => Product::FULFILLMENT_STOCKED,
                'status' => Product::STATUS_ACTIVE,
                'visibility' => Product::VISIBILITY_PUBLIC,
                'min_order_quantity' => 1,
                'base_price_minor' => 1000,
                'currency' => 'INR',
                'sort_order' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('primary_category_id');
    }

    public function test_slug_is_normalized_automatically(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), [
            'name' => 'Premium Shirt',
            'slug' => 'Premium T Shirt !!!', // Messy slug
            'product_type' => Product::TYPE_SIMPLE,
            'customization_mode' => Product::CUSTOMIZATION_NONE,
            'fulfillment_type' => Product::FULFILLMENT_STOCKED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'min_order_quantity' => 1,
            'base_price_minor' => 1000,
            'currency' => 'INR',
            'sort_order' => 1,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'slug' => 'premium-t-shirt',
        ]);
    }

    public function test_slug_collision_normalized_fails_uniqueness(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Product::factory()->create(['slug' => 'premium-t-shirt']);
        $productB = Product::factory()->create(['slug' => 'other-shirt']);

        $response = $this->actingAs($user)->from(route('admin.products.edit', $productB))
            ->put(route('admin.products.update', $productB), [
                'name' => 'Premium Shirt',
                'slug' => 'Premium T Shirt !!!', // Normalizes to premium-t-shirt (which conflicts)
                'product_type' => Product::TYPE_SIMPLE,
                'customization_mode' => Product::CUSTOMIZATION_NONE,
                'fulfillment_type' => Product::FULFILLMENT_STOCKED,
                'status' => Product::STATUS_ACTIVE,
                'visibility' => Product::VISIBILITY_PUBLIC,
                'min_order_quantity' => 1,
                'base_price_minor' => 1000,
                'currency' => 'INR',
                'sort_order' => 1,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('slug');
    }

    public function test_boolean_checkboxes_unchecked_resolve_to_false(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create([
            'direct_checkout_enabled' => true,
            'quote_enabled' => true,
        ]);

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), [
            'name' => 'Premium Shirt',
            'slug' => 'premium-shirt',
            'product_type' => Product::TYPE_SIMPLE,
            'customization_mode' => Product::CUSTOMIZATION_NONE,
            'fulfillment_type' => Product::FULFILLMENT_STOCKED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'min_order_quantity' => 1,
            'base_price_minor' => 1000,
            'currency' => 'INR',
            'sort_order' => 1,
            // Checkboxes omitted
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'direct_checkout_enabled' => false,
            'quote_enabled' => false,
        ]);
    }

    public function test_no_op_updates_succeed_but_dont_dispatch_audit_event(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create([
            'name' => 'Premium Mug',
            'slug' => 'premium-mug',
            'product_type' => Product::TYPE_SIMPLE,
            'customization_mode' => Product::CUSTOMIZATION_NONE,
            'fulfillment_type' => Product::FULFILLMENT_STOCKED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'min_order_quantity' => 1,
            'base_price_minor' => 1000,
            'currency' => 'INR',
            'sort_order' => 1,
            'direct_checkout_enabled' => false,
            'quote_enabled' => false,
            'short_description' => null,
            'description' => null,
            'primary_category_id' => null,
            'published_at' => null,
        ]);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), [
            'name' => 'Premium Mug',
            'slug' => 'premium-mug',
            'product_type' => Product::TYPE_SIMPLE,
            'customization_mode' => Product::CUSTOMIZATION_NONE,
            'fulfillment_type' => Product::FULFILLMENT_STOCKED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'min_order_quantity' => 1,
            'base_price_minor' => 1000,
            'currency' => 'INR',
            'sort_order' => 1,
        ]);

        $response->assertStatus(302);
        Event::assertNotDispatched(AuditEvent::class);
    }

    public function test_successful_updates_dispatch_audit_event_and_save_data(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create([
            'name' => 'Old Product Name',
            'slug' => 'old-product-slug',
            'base_price_minor' => 500,
        ]);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), [
            'name' => 'New Product Name',
            'slug' => 'new-product-slug',
            'product_type' => Product::TYPE_SIMPLE,
            'customization_mode' => Product::CUSTOMIZATION_NONE,
            'fulfillment_type' => Product::FULFILLMENT_STOCKED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'min_order_quantity' => 1,
            'base_price_minor' => 2500,
            'currency' => 'INR',
            'sort_order' => 2,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.products.edit', $product));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'New Product Name',
            'slug' => 'new-product-slug',
            'base_price_minor' => 2500,
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($product) {
            return $event->key === 'products.updated'
                && $event->payload['product_id'] === $product->id
                && $event->payload['slug'] === 'new-product-slug';
        });
    }
}
