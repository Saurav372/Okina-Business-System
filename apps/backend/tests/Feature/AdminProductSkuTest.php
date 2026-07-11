<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminProductSkuTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $staffUser;

    protected Product $product;

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
            'name' => 'Administrator',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $roleAdmin->permissions()->sync(
            Permission::query()->whereIn('slug', ['products.view', 'products.manage', 'dashboard.access'])->pluck('id')->all()
        );

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->sync([$roleAdmin->id]);

        $this->staffUser = User::factory()->create();
        $this->staffUser->roles()->sync([$roleInventory->id]);

        $category = ProductCategory::factory()->create(['status' => 'active']);
        $this->product = Product::factory()->create([
            'primary_category_id' => $category->id,
            'status' => 'active',
            'base_price_minor' => 149900,
            'slug' => 'premium-polo-t-shirt',
        ]);
    }

    /**
     * Test guests and inventory staff are blocked from SKU management.
     */
    public function test_guest_and_staff_are_unauthorized_for_sku_management(): void
    {
        $this->post(route('admin.products.skus.generate', $this->product))
            ->assertRedirect(route('login'));

        $this->actingAs($this->staffUser)
            ->post(route('admin.products.skus.generate', $this->product))
            ->assertStatus(403);
    }

    /**
     * Test products with no variant options generate exactly one default SKU.
     */
    public function test_default_sku_generation_for_product_without_variants(): void
    {
        $this->actingAs($this->adminUser)
            ->post(route('admin.products.skus.generate', $this->product))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_skus', [
            'product_id' => $this->product->id,
            'variant_key' => 'default',
            'sku_code' => 'PREMIUM-POLO-T-SHIRT',
        ]);

        $sku = ProductSku::where('variant_key', 'default')->first();
        $this->assertNotNull($sku);
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $sku->id,
        ]);
    }

    /**
     * Test generation of Cartesian matrix options and creation of InventoryItems.
     */
    public function test_cartesian_matrix_sku_generation_with_inventory_creation(): void
    {
        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'name' => 'Color',
            'code' => 'color',
            'values' => [
                ['code' => 'red', 'label' => 'Red', 'sort_order' => 1],
                ['code' => 'blue', 'label' => 'Blue', 'sort_order' => 2],
            ],
        ]);

        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 's', 'label' => 'Small', 'sort_order' => 1],
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 2],
            ],
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.products.skus.generate', $this->product))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(4, $this->product->skus()->count());

        $expectedKeys = [
            'color:red|size:s',
            'color:red|size:m',
            'color:blue|size:s',
            'color:blue|size:m',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertDatabaseHas('product_skus', [
                'product_id' => $this->product->id,
                'variant_key' => $key,
            ]);

            $sku = ProductSku::where('variant_key', $key)->first();
            $this->assertNotNull($sku->inventoryItem);
        }
    }

    /**
     * Test unique SKU code collision suffix generator.
     */
    public function test_sku_code_collision_handling(): void
    {
        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'name' => 'Color',
            'code' => 'color',
            'values' => [
                ['code' => 'red', 'label' => 'Red', 'sort_order' => 1],
                ['code' => 'blue', 'label' => 'Blue', 'sort_order' => 2],
            ],
        ]);

        // Pre-create SKU code to cause collision
        ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'PREMIUM-POLO-T-SHIRT-RED',
            'variant_key' => 'manually-created',
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.products.skus.generate', $this->product))
            ->assertRedirect();

        // One of color:red combination will conflict with pre-created PREMIUM-POLO-T-SHIRT-RED,
        // so it must append suffix: PREMIUM-POLO-T-SHIRT-RED-1
        $this->assertDatabaseHas('product_skus', [
            'sku_code' => 'PREMIUM-POLO-T-SHIRT-RED-1',
            'variant_key' => 'color:red',
        ]);
    }

    /**
     * Test concurrent matrix generation safety.
     */
    public function test_concurrent_generation_safety(): void
    {
        ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 's', 'label' => 'Small', 'sort_order' => 1],
            ],
        ]);

        $this->actingAs($this->adminUser);

        // Generate once
        $this->post(route('admin.products.skus.generate', $this->product))->assertRedirect();
        $this->assertEquals(1, $this->product->skus()->count());

        // Generate twice
        $this->post(route('admin.products.skus.generate', $this->product))->assertRedirect();
        $this->assertEquals(1, $this->product->skus()->count());
    }

    /**
     * Test SKU validation rules.
     */
    public function test_update_sku_validations(): void
    {
        $sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'TEST-SKU-1',
        ]);

        $anotherSku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'TEST-SKU-2',
        ]);

        $this->actingAs($this->adminUser);

        // Duplicate code validation
        $this->put(route('admin.products.skus.update', [$this->product, $sku]), [
            'sku_code' => 'TEST-SKU-2',
            'status' => 'active',
            'price_minor' => 1000,
            'sort_order' => 0,
        ])->assertSessionHasErrors('sku_code');

        // Conditional track_stock quantity validation
        $this->put(route('admin.products.skus.update', [$this->product, $sku]), [
            'sku_code' => 'TEST-SKU-1',
            'status' => 'active',
            'price_minor' => 1000,
            'sort_order' => 0,
            'track_stock' => '1',
            'stock_quantity' => '',
        ])->assertSessionHasErrors('stock_quantity');
    }

    /**
     * Test cross-product route scoping protection.
     */
    public function test_cross_product_scoping_throws_404(): void
    {
        $anotherProduct = Product::factory()->create();
        $skuOfAnotherProduct = ProductSku::factory()->create([
            'product_id' => $anotherProduct->id,
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.skus.update', [$this->product, $skuOfAnotherProduct]), [
                'sku_code' => 'RANDOM-CODE',
                'status' => 'active',
                'price_minor' => 1000,
                'sort_order' => 0,
            ])
            ->assertStatus(404);
    }

    /**
     * Test no-op optimization when updating identical values.
     */
    public function test_noop_sku_update_does_not_trigger_audit_events(): void
    {
        Event::fake([AuditEvent::class]);

        $sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'NOOP-SKU',
            'price_minor' => 129900,
            'track_stock' => true,
            'stock_quantity' => 10,
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.skus.update', [$this->product, $sku]), [
                'sku_code' => 'NOOP-SKU',
                'status' => 'active',
                'price_minor' => 129900,
                'track_stock' => '1',
                'stock_quantity' => 10,
                'sort_order' => 0,
                'direct_checkout_enabled' => '1',
                'quote_required' => '0',
                'allow_backorder' => '0',
            ])
            ->assertRedirect();

        Event::assertNotDispatched(AuditEvent::class);
    }

    /**
     * Test successful update dispatches products.sku_updated audit log.
     */
    public function test_successful_sku_update_dispatches_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'UPDATE-SKU',
            'price_minor' => 1000,
        ]);

        $this->actingAs($this->adminUser)
            ->put(route('admin.products.skus.update', [$this->product, $sku]), [
                'sku_code' => 'UPDATE-SKU',
                'status' => 'active',
                'price_minor' => 5000,
                'sort_order' => 10,
            ])
            ->assertRedirect();

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($sku) {
            return $event->key === 'products.sku_updated'
                && $event->payload['subject_id'] === $sku->id
                && $event->payload['new_values']['price_minor'] === 5000;
        });
    }

    /**
     * Test successful delete dispatches products.sku_deleted audit log.
     */
    public function test_successful_sku_delete_dispatches_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'DELETE-SKU',
        ]);

        $this->actingAs($this->adminUser)
            ->delete(route('admin.products.skus.destroy', [$this->product, $sku]))
            ->assertRedirect();

        $this->assertSoftDeleted('product_skus', [
            'id' => $sku->id,
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'products.sku_deleted'
                && $event->payload['sku_code'] === 'DELETE-SKU';
        });
    }
}
