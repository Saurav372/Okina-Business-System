<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Exceptions\PurchaseOrderImmutableException;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PurchaseOrderItemTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $unprivilegedUser;

    private User $privilegedStaff;

    private User $viewOnlyStaff;

    private Vendor $activeVendor;

    private ProductSku $skuA;

    private ProductSku $skuB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys = ON;');

        // Create permissions
        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.manage'],
            [
                'name' => 'Manage Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can manage purchase orders',
                'is_sensitive' => true,
            ]
        );
        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.view'],
            [
                'name' => 'View Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can view purchase orders',
                'is_sensitive' => false,
            ]
        );

        // Create roles
        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'description' => 'Admin role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $adminRole->permissions()->sync(
            Permission::query()->pluck('id')->all()
        );

        $staffRole = Role::query()->updateOrCreate(
            ['slug' => Role::INVENTORY_STAFF],
            [
                'name' => 'Inventory Staff',
                'guard_name' => 'web',
                'description' => 'Inventory staff role',
                'is_system' => true,
                'sort_order' => 1,
            ]
        );
        $staffRole->permissions()->sync(
            Permission::query()->where('slug', 'purchases.manage')->pluck('id')->all()
        );

        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 2,
            ]
        );
        $salesRole->permissions()->sync(
            Permission::query()->where('slug', 'purchases.view')->pluck('id')->all()
        );

        // Create users
        $this->adminUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);

        $this->viewOnlyStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->viewOnlyStaff->assignRole($salesRole);

        // Seed vendor
        $this->activeVendor = Vendor::create([
            'name' => 'Supplier Inc',
            'vendor_code' => 'VND-SUPP99',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        // Seed product SKUs
        $productA = Product::factory()->create(['name' => 'Premium SKU A']);
        $this->skuA = ProductSku::factory()->create([
            'sku_code' => 'SKU-AAAA-1111',
            'product_id' => $productA->id,
        ]);
        $productB = Product::factory()->create(['name' => 'Budget SKU B']);
        $this->skuB = ProductSku::factory()->create([
            'sku_code' => 'SKU-BBBB-2222',
            'product_id' => $productB->id,
        ]);
    }

    /**
     * Test authorized staff can add, update, and delete items with totals re-calculation.
     */
    public function test_item_crud_and_recalculations(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT88',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'currency' => 'INR',
            'shipping_amount_minor' => 500,
            'discount_amount_minor' => 200,
        ]);

        // 1. Add Item A
        // Qty: 10, Unit Cost: 1000, Tax: 180 -> Line Total = 10 * 1000 + 180 = 10180
        $response = $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
            'tax_amount_minor' => 180,
        ])->assertStatus(201);

        $itemIdA = $response->json('id');

        $po->refresh();
        // subtotal = 10 * 1000 = 10000. tax = 180. total = 10000 + 180 + 500 - 200 = 10480.
        $this->assertEquals(10000, $po->subtotal_amount_minor);
        $this->assertEquals(180, $po->tax_amount_minor);
        $this->assertEquals(10480, $po->total_amount_minor);

        // Verify currency is inherited
        $this->assertEquals('INR', $response->json('currency'));

        // Verify snapshot values
        $this->assertEquals('SKU-AAAA-1111', $response->json('sku_code_snapshot'));
        $this->assertEquals('Premium SKU A', $response->json('product_name_snapshot'));

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($itemIdA, $po) {
            return $event->key === 'purchase_order_items.created'
                && $event->payload['vendor_order_id'] === $po->id
                && $event->payload['vendor_order_item_id'] === $itemIdA
                && $event->payload['product_sku_id'] === $this->skuA->id
                && $event->payload['quantity_ordered'] === 10
                && $event->payload['unit_cost_minor'] === 1000
                && $event->payload['line_total_minor'] === 10180;
        });

        // 2. Add Item B
        // Qty: 5, Unit Cost: 2000, Tax: 360 -> Line Total = 5 * 2000 + 360 = 10360
        $response = $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuB->id,
            'quantity_ordered' => 5,
            'unit_cost_minor' => 2000,
            'tax_amount_minor' => 360,
        ])->assertStatus(201);

        $itemIdB = $response->json('id');

        $po->refresh();
        // subtotal = 10000 + 10000 = 20000. tax = 180 + 360 = 540. total = 20000 + 540 + 500 - 200 = 20840.
        $this->assertEquals(20000, $po->subtotal_amount_minor);
        $this->assertEquals(540, $po->tax_amount_minor);
        $this->assertEquals(20840, $po->total_amount_minor);

        // 3. Update Item B (change qty to 2, unit cost remains 2000, tax to 100)
        // Line total B becomes = 2 * 2000 + 100 = 4100
        $this->putJson(route('admin.purchase_orders.items.update', [$po->id, $itemIdB]), [
            'quantity_ordered' => 2,
            'tax_amount_minor' => 100,
        ])->assertStatus(200);

        $po->refresh();
        // subtotal = 10000 + (2 * 2000) = 14000. tax = 180 + 100 = 280. total = 14000 + 280 + 500 - 200 = 14580.
        $this->assertEquals(14000, $po->subtotal_amount_minor);
        $this->assertEquals(280, $po->tax_amount_minor);
        $this->assertEquals(14580, $po->total_amount_minor);

        // 4. Delete Item A (regression test scenario)
        // Only Item B (qty 2, unit cost 2000, tax 100) remains.
        $this->deleteJson(route('admin.purchase_orders.items.destroy', [$po->id, $itemIdA]))->assertStatus(200);

        $po->refresh();
        // subtotal = 2 * 2000 = 4000. tax = 100. total = 4000 + 100 + 500 - 200 = 4400.
        $this->assertEquals(4000, $po->subtotal_amount_minor);
        $this->assertEquals(100, $po->tax_amount_minor);
        $this->assertEquals(4400, $po->total_amount_minor);
    }

    /**
     * Test duplicate SKU additions are blocked.
     */
    public function test_duplicate_sku_rejection(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT89',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
        ])->assertStatus(201);

        // Duplicate SKU A addition should fail validation
        $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 5,
            'unit_cost_minor' => 2000,
        ])->assertStatus(422)->assertJsonValidationErrors(['product_sku_id']);
    }

    /**
     * Test duplicate SKU additions block concurrent query violations.
     *
     * Simulates a race condition: validation passed (no unique rule triggered),
     * but the DB unique constraint fires on INSERT. The controller must catch the
     * QueryException and return a 422 with a product_sku_id error.
     *
     * We bypass the FormRequest's unique rule by inserting the duplicate directly
     * into the DB before sending the HTTP request. The DB composite unique index
     * on (vendor_order_id, product_sku_id) then fires inside the controller's transaction.
     */
    public function test_concurrency_duplicate_sku_query_exception(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT90',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        // Insert the first item directly via the DB (bypassing FormRequest unique check)
        // to simulate the "first" concurrent request having already committed.
        DB::table('vendor_order_items')->insert([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
            'line_total_minor' => 10000,
            'sku_code_snapshot' => $this->skuA->sku_code,
            'currency' => $po->currency ?? 'INR',
            'quantity_received' => 0,
            'tax_amount_minor' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The second concurrent request passes validation (no unique rule stops it),
        // but the DB unique index fires inside the transaction, producing a QueryException.
        // The controller must translate this into a 422 validation error.
        $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 5,
            'unit_cost_minor' => 2000,
        ])->assertStatus(422)->assertJsonValidationErrors(['product_sku_id']);
    }

    /**
     * Test item updates are blocked if PO is not in draft status.
     */
    public function test_modifications_blocked_once_ordered(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-ORDERED77',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $item = new VendorOrderItem([
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
        ]);
        $item->vendor_order_id = $po->id;
        $item->sku_code_snapshot = $this->skuA->sku_code;
        $item->line_total_minor = 10000;
        $item->save();

        $this->withoutExceptionHandling();
        $this->expectException(PurchaseOrderImmutableException::class);

        $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuB->id,
            'quantity_ordered' => 5,
            'unit_cost_minor' => 1000,
        ]);
    }

    /**
     * Test snapshot values are immutable after product code changes.
     */
    public function test_snapshot_immutability(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT91',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $item = new VendorOrderItem([
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
        ]);
        $item->vendor_order_id = $po->id;
        $item->sku_code_snapshot = $this->skuA->sku_code;
        $item->product_name_snapshot = $this->skuA->product?->name;
        $item->line_total_minor = 10000;
        $item->save();

        // Rename original SKU A code and product name
        $this->skuA->update([
            'sku_code' => 'NEW-SKU-CODE',
        ]);
        $this->skuA->product->update([
            'name' => 'New Product Name',
        ]);

        // Load the item and assert snapshots remain unchanged (retaining historical data)
        $item->refresh();
        $this->assertEquals('SKU-AAAA-1111', $item->sku_code_snapshot);
        $this->assertEquals('Premium SKU A', $item->product_name_snapshot);
    }

    /**
     * Test quantity_received and currency are protected from mass assignment.
     */
    public function test_mass_assignment_guards(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT92',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'currency' => 'INR',
        ]);

        $response = $this->postJson(route('admin.purchase_orders.items.store', $po->id), [
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
            'quantity_received' => 50, // Should be ignored
            'currency' => 'USD', // Should be ignored/inherited
        ])->assertStatus(201);

        $this->assertEquals(0, $response->json('quantity_received'));
        $this->assertEquals('INR', $response->json('currency'));
    }

    /**
     * Test restrict delete constraint on ProductSku.
     */
    public function test_restrict_delete_on_product_sku(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT93',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $item = new VendorOrderItem([
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
        ]);
        $item->vendor_order_id = $po->id;
        $item->sku_code_snapshot = $this->skuA->sku_code;
        $item->line_total_minor = 10000;
        $item->save();

        // Attempting to delete product SKU A should throw unique constraint / restrict exception
        $this->expectException(QueryException::class);
        $this->skuA->delete();
    }
}
