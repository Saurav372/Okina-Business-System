<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminProductVariantTest extends TestCase
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

    public function test_guests_cannot_crud_product_variants(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->post(route('admin.products.variants.store', $product), [])->assertStatus(302);
        $this->put(route('admin.products.variants.update', [$product, $variant]), [])->assertStatus(302);
        $this->delete(route('admin.products.variants.destroy', [$product, $variant]))->assertStatus(302);
    }

    public function test_inventory_staff_cannot_crud_product_variants(): void
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_staff');

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [])
            ->assertStatus(403);

        $this->actingAs($user)
            ->put(route('admin.products.variants.update', [$product, $variant]), [])
            ->assertStatus(403);

        $this->actingAs($user)
            ->delete(route('admin.products.variants.destroy', [$product, $variant]))
            ->assertStatus(403);
    }

    public function test_validation_fails_for_duplicate_variant_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();
        ProductVariant::factory()->create(['product_id' => $product->id, 'code' => 'size']);

        $response = $this->actingAs($user)->post(route('admin.products.variants.store', $product), [
            'name' => 'Custom Size',
            'code' => 'Size', // normalizes to slug size, which conflicts
            'display_type' => 'select',
            'values_csv' => 'S, M, L',
            'sort_order' => 10,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('code');
    }

    public function test_validation_fails_for_empty_values_csv(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.products.variants.store', $product), [
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'select',
            'values_csv' => ' , ', // empty items after trimming
            'sort_order' => 10,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('values_csv');
    }

    public function test_case_insensitive_duplicate_values_are_deduplicated(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.products.variants.store', $product), [
            'name' => 'Color',
            'code' => 'color',
            'display_type' => 'swatch',
            'values_csv' => 'Red, red, RED, Blue', // case-insensitive duplicates
            'sort_order' => 10,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.products.edit', [$product, 'tab' => 'variants']));

        $variant = ProductVariant::where('product_id', $product->id)->where('code', 'color')->firstOrFail();

        $this->assertCount(2, $variant->values);
        $this->assertSame('Red', $variant->values[0]['label']);
        $this->assertSame('Blue', $variant->values[1]['label']);
    }

    public function test_variant_product_mismatch_returns_404_via_scoped_bindings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $variantB = ProductVariant::factory()->create(['product_id' => $productB->id]);

        // Attempting to update Product A's variant using Product B's variant instance ID
        $this->actingAs($user)->put(route('admin.products.variants.update', [$productA, $variantB]), [
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'select',
            'values_csv' => 'S, M, L',
            'sort_order' => 10,
        ])->assertStatus(404);

        // Attempting to delete Product A's variant using Product B's variant instance ID
        $this->actingAs($user)->delete(route('admin.products.variants.destroy', [$productA, $variantB]))
            ->assertStatus(404);
    }

    public function test_category_assignment_can_be_updated_successfully(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = ProductCategory::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['primary_category_id' => null]);

        $response = $this->actingAs($user)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'product_type' => $product->product_type,
            'customization_mode' => $product->customization_mode,
            'fulfillment_type' => $product->fulfillment_type,
            'status' => $product->status,
            'visibility' => $product->visibility,
            'min_order_quantity' => $product->min_order_quantity,
            'base_price_minor' => $product->base_price_minor,
            'currency' => $product->currency,
            'sort_order' => $product->sort_order,
            'primary_category_id' => $category->id,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'primary_category_id' => $category->id,
        ]);
    }

    public function test_successful_variant_creation_dispatches_audit_event(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)->post(route('admin.products.variants.store', $product), [
            'name' => 'Material',
            'code' => 'material',
            'display_type' => 'button',
            'values_csv' => 'Cotton, Polyester',
            'sort_order' => 15,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.products.edit', [$product, 'tab' => 'variants']));

        $variant = ProductVariant::where('product_id', $product->id)->where('code', 'material')->firstOrFail();
        $this->assertSame('Material', $variant->name);
        $this->assertCount(2, $variant->values);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($product, $variant) {
            return $event->key === 'products.variant_created'
                && $event->payload['product_id'] === $product->id
                && $event->payload['variant_id'] === $variant->id
                && $event->payload['code'] === 'material';
        });
    }

    public function test_no_op_variant_updates_do_not_dispatch_audit_event(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'select',
            'values' => [
                ['code' => 's', 'label' => 'S', 'sort_order' => 10, 'is_active' => true, 'metadata' => []],
                ['code' => 'm', 'label' => 'M', 'sort_order' => 20, 'is_active' => true, 'metadata' => []],
            ],
            'is_required' => true,
            'sort_order' => 10,
        ]);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)->put(route('admin.products.variants.update', [$product, $variant]), [
            'name' => 'Size',
            'code' => 'size',
            'display_type' => 'select',
            'values_csv' => 'S, M',
            'is_required' => '1',
            'sort_order' => 10,
        ]);

        $response->assertStatus(302);
        Event::assertNotDispatched(AuditEvent::class);
    }

    public function test_successful_variant_deletion_dispatches_audit_event(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Capacity',
            'code' => 'capacity',
        ]);

        Event::fake([AuditEvent::class]);

        $response = $this->actingAs($user)->delete(route('admin.products.variants.destroy', [$product, $variant]));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.products.edit', [$product, 'tab' => 'variants']));

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($product, $variant) {
            return $event->key === 'products.variant_deleted'
                && $event->payload['product_id'] === $product->id
                && $event->payload['variant_id'] === $variant->id
                && $event->payload['code'] === 'capacity';
        });
    }
}
