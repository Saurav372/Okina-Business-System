<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementReason;
use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PurchaseOrderStockReceivingTest extends TestCase
{
    use RefreshDatabase;

    private User $privilegedStaff;

    private User $unprivilegedUser;

    private Vendor $activeVendor;

    private ProductSku $skuA;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

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
        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);

        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        // Create vendor
        $this->activeVendor = Vendor::create([
            'name' => 'Supplier Inc',
            'vendor_code' => 'VND-SUPP99',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        // Create Product SKU
        $product = Product::factory()->create(['name' => 'SKU Product']);
        $this->skuA = ProductSku::factory()->create([
            'sku_code' => 'SKU-AAAA-1111',
            'product_id' => $product->id,
        ]);

        // Update auto-created inventory item balance
        $inventoryItem = InventoryItem::where('product_sku_id', $this->skuA->id)->firstOrFail();
        $inventoryItem->update([
            'on_hand_quantity' => 10,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
        ]);
    }

    /**
     * Test gated access: only purchases.manage permission allows stock receiving.
     */
    public function test_authorization_gating(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-AUTH11',
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

        // Guest gets 401
        $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 5,
        ])->assertStatus(401);

        // Unprivileged staff gets 403
        $this->actingAs($this->unprivilegedUser)
            ->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
                'quantity' => 5,
            ])->assertStatus(403);

        // Privileged staff gets 200
        $this->actingAs($this->privilegedStaff)
            ->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
                'quantity' => 5,
            ])->assertStatus(200);
    }

    /**
     * Test route-model safety validation.
     */
    public function test_route_model_relationship_safety(): void
    {
        $this->actingAs($this->privilegedStaff);

        $poA = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-AAAAAA',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $poB = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-BBBBBB',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $itemA = new VendorOrderItem([
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
        ]);
        $itemA->vendor_order_id = $poA->id;
        $itemA->sku_code_snapshot = $this->skuA->sku_code;
        $itemA->line_total_minor = 10000;
        $itemA->save();

        $itemB = new VendorOrderItem([
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 10,
            'unit_cost_minor' => 1000,
        ]);
        $itemB->vendor_order_id = $poB->id;
        $itemB->sku_code_snapshot = $this->skuA->sku_code;
        $itemB->line_total_minor = 10000;
        $itemB->save();

        // Mismatched PO and Item route call should fail with 404
        $this->postJson(route('admin.purchase_orders.items.receive', [$poA->id, $itemB->id]), [
            'quantity' => 5,
        ])->assertStatus(404);
    }

    /**
     * Test valid stock receiving flow (partially vs fully receiving).
     */
    public function test_valid_receiving_and_movement_generation(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-VALIDRCV',
            'status' => VendorOrderStatus::ORDERED->value,
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

        // Initial balance: 10
        $initialBalance = $this->skuA->inventoryItem->on_hand_quantity;
        $this->assertEquals(10, $initialBalance);

        // 1. Receive 4 items (Partial receive)
        $response = $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 4,
        ])->assertStatus(200);

        // Assert response structure
        $response->assertJsonStructure([
            'item' => ['id', 'quantity_received'],
            'purchase_order' => ['id', 'status', 'received_at'],
        ]);

        $response->assertJsonPath('item.quantity_received', 4)
            ->assertJsonPath('purchase_order.status', 'partially_received')
            ->assertJsonPath('purchase_order.received_at', null);

        // Assert balance incremented: 10 + 4 = 14
        $this->skuA->inventoryItem->refresh();
        $this->assertEquals(14, $this->skuA->inventoryItem->on_hand_quantity);

        // Verify InventoryMovement
        $movement = InventoryMovement::where('vendor_order_item_id', $item->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals($po->id, $movement->vendor_order_id);
        $this->assertEquals(4, $movement->quantity);
        $this->assertEquals(InventoryMovementReason::PURCHASE_RECEIPT->value, $movement->reason_code->value);

        // Verify exact AuditEvent payload
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($po, $item) {
            if ($event->key !== 'purchase_orders.items.received') {
                return false;
            }

            return $event->payload['vendor_order_id'] === $po->id
                && $event->payload['vendor_order_item_id'] === $item->id
                && $event->payload['received_quantity'] === 4
                && $event->payload['total_quantity_received'] === 4
                && $event->payload['remaining_quantity'] === 6;
        });

        // 2. Receive remaining 6 items (Full receive)
        Event::fake([AuditEvent::class]);
        $now = Carbon::now()->microsecond(0);
        Carbon::setTestNow($now);

        $response = $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 6,
        ])->assertStatus(200);

        $response->assertJsonPath('item.quantity_received', 10)
            ->assertJsonPath('purchase_order.status', 'received')
            ->assertJsonPath('purchase_order.received_at', $now->jsonSerialize());

        $this->skuA->inventoryItem->refresh();
        $this->assertEquals(20, $this->skuA->inventoryItem->on_hand_quantity);

        Carbon::setTestNow();
    }

    /**
     * Test partial receiving multiple times and zero remaining quantity.
     */
    public function test_partial_receiving_twice_and_zero_remaining_regression(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-PARTIAL-X',
            'status' => VendorOrderStatus::ORDERED->value,
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

        // Initial balance is 10.
        // Receive 6
        $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 6,
        ])->assertStatus(200);

        // Receive 4 (now fully received)
        $now = Carbon::now()->microsecond(0);
        Carbon::setTestNow($now);

        $response = $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 4,
        ])->assertStatus(200);

        $response->assertJsonPath('purchase_order.status', 'received')
            ->assertJsonPath('purchase_order.received_at', $now->jsonSerialize());

        // Assert exactly 2 InventoryMovements created
        $this->assertEquals(2, InventoryMovement::where('vendor_order_item_id', $item->id)->count());

        // Receive 1 extra (should fail with 422 since remaining is 0)
        Event::fake([AuditEvent::class]);
        $response = $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 1,
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);

        // Assert database properties remain unchanged
        $po->refresh();
        $item->refresh();
        $this->skuA->inventoryItem->refresh();

        $this->assertEquals(VendorOrderStatus::RECEIVED, $po->status);
        $this->assertEquals($now->toDateTimeString(), $po->received_at->toDateTimeString());
        $this->assertEquals(10, $item->quantity_received);
        $this->assertEquals(20, $this->skuA->inventoryItem->on_hand_quantity);

        // Assert exactly 2 movements still exist (no 3rd created)
        $this->assertEquals(2, InventoryMovement::where('vendor_order_item_id', $item->id)->count());

        // Assert no audit event dispatched for failed request
        Event::assertNotDispatched(AuditEvent::class);

        Carbon::setTestNow();
    }

    /**
     * Test multi-item PO status advancement.
     */
    public function test_multi_item_po_status_advancement(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-MULTI',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        // Create 3 items (bypassing mass assignment guards)
        $item1 = new VendorOrderItem([
            'product_sku_id' => $this->skuA->id,
            'quantity_ordered' => 5,
            'unit_cost_minor' => 1000,
        ]);
        $item1->vendor_order_id = $po->id;
        $item1->sku_code_snapshot = $this->skuA->sku_code;
        $item1->line_total_minor = 5000;
        $item1->save();

        $productB = Product::factory()->create(['name' => 'Product B']);
        $skuB = ProductSku::factory()->create([
            'sku_code' => 'SKU-BBBB-2222',
            'product_id' => $productB->id,
        ]);
        $inventoryItemB = InventoryItem::where('product_sku_id', $skuB->id)->firstOrFail();
        $inventoryItemB->update([
            'on_hand_quantity' => 5,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $item2 = new VendorOrderItem([
            'product_sku_id' => $skuB->id,
            'quantity_ordered' => 5,
            'unit_cost_minor' => 1000,
        ]);
        $item2->vendor_order_id = $po->id;
        $item2->sku_code_snapshot = $skuB->sku_code;
        $item2->line_total_minor = 5000;
        $item2->save();

        // Receive first item -> partially_received
        $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item1->id]), [
            'quantity' => 5,
        ])->assertStatus(200)->assertJsonPath('purchase_order.status', 'partially_received');

        // Receive second item -> received (since all items fully received)
        $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item2->id]), [
            'quantity' => 5,
        ])->assertStatus(200)->assertJsonPath('purchase_order.status', 'received');
    }

    /**
     * Test that receiving stock on draft or cancelled POs fails with 422.
     */
    public function test_receiving_fails_on_non_receivable_po_status(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DRAFT-RCV',
            'status' => VendorOrderStatus::DRAFT->value,
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

        $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
            'quantity' => 5,
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test transaction rollback on inventory failure.
     */
    public function test_rollback_on_inventory_exception(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-ROLLBACK-RCV',
            'status' => VendorOrderStatus::ORDERED->value,
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

        // Force exception inside transaction by registering saving listener on InventoryMovement
        InventoryMovement::saving(function ($model) {
            throw new \RuntimeException('Forced inventory movement failure.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->postJson(route('admin.purchase_orders.items.receive', [$po->id, $item->id]), [
                'quantity' => 5,
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Forced inventory movement failure.', $e->getMessage());
        }

        // Clean up saving listener
        InventoryMovement::flushEventListeners();

        // Assert database values remain unchanged
        $po->refresh();
        $item->refresh();
        $this->skuA->inventoryItem->refresh();

        $this->assertEquals(VendorOrderStatus::ORDERED, $po->status);
        $this->assertEquals(0, $item->quantity_received);
        $this->assertEquals(10, $this->skuA->inventoryItem->on_hand_quantity);

        // Verify no audit event was dispatched
        Event::assertNotDispatched(AuditEvent::class);
    }
}
