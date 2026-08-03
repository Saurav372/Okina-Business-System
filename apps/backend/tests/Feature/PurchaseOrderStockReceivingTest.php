<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\PurchaseReceipt;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
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
            ['slug' => 'purchases.receive'],
            [
                'name' => 'Receive Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can receive purchase order stock',
                'is_sensitive' => false,
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
            Permission::query()->whereIn('slug', ['purchases.manage', 'purchases.receive'])->pluck('id')->all()
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
     * Test gated access: only purchases.receive or purchases.manage permission allows stock receiving.
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
        $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-AUTH-1',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 5]],
        ])->assertStatus(401);

        // Unprivileged staff gets 403
        $this->actingAs($this->unprivilegedUser)
            ->postJson(route('admin.purchases.receive', $po->public_id), [
                'idempotency_key' => 'KEY-AUTH-2',
                'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 5]],
            ])->assertStatus(403);

        // Privileged staff gets 201
        $this->actingAs($this->privilegedStaff)
            ->postJson(route('admin.purchases.receive', $po->public_id), [
                'idempotency_key' => 'KEY-AUTH-3',
                'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 5]],
            ])->assertStatus(201);
    }

    /**
     * Test valid stock receiving flow (partially vs fully receiving, PurchaseReceipt batch creation).
     */
    public function test_valid_receiving_and_purchase_receipt_creation(): void
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
        $response = $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-PARTIAL-1',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 4]],
            'notes' => 'Batch 1 partial receipt',
        ])->assertStatus(201);

        $response->assertJsonStructure([
            'message',
            'data' => ['vendor_order_id', 'public_id', 'receipt_id', 'receipt_number', 'received_count', 'status', 'replayed'],
        ]);

        $response->assertJsonPath('data.received_count', 4)
            ->assertJsonPath('data.status', 'partially_received')
            ->assertJsonPath('data.replayed', false);

        // Assert PurchaseReceipt and line created
        $receipt = PurchaseReceipt::where('vendor_order_id', $po->id)->first();
        $this->assertNotNull($receipt);
        $this->assertMatchesRegularExpression('/^PR-\d{6}-[A-Z0-9]{6}$/', $receipt->receipt_number);
        $this->assertEquals(1, $receipt->lines()->count());
        $this->assertEquals(4, $receipt->lines()->first()->quantity_received);

        // Assert balance incremented: 10 + 4 = 14
        $this->skuA->inventoryItem->refresh();
        $this->assertEquals(14, $this->skuA->inventoryItem->on_hand_quantity);

        // Synchronized ProductSku stock_quantity
        $this->skuA->refresh();
        $this->assertEquals(14, $this->skuA->stock_quantity);

        // Verify InventoryMovement linked to PurchaseReceipt
        $movement = InventoryMovement::where('vendor_order_item_id', $item->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals($po->id, $movement->vendor_order_id);
        $this->assertEquals(4, $movement->quantity);
        $this->assertEquals('PurchaseReceipt', $movement->reference_type);
        $this->assertEquals($receipt->id, $movement->reference_id);

        // 2. Test Idempotent Replay with Same Key & Same Payload -> Returns 200 OK
        $replayResponse = $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-PARTIAL-1',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 4]],
            'notes' => 'Batch 1 partial receipt',
        ])->assertStatus(200);

        $replayResponse->assertJsonPath('data.replayed', true);

        // Assert no extra receipt or inventory movement was created
        $this->assertEquals(1, PurchaseReceipt::where('vendor_order_id', $po->id)->count());
        $this->assertEquals(1, InventoryMovement::where('vendor_order_item_id', $item->id)->count());

        // 3. Test Mismatched Key Payload -> Returns 409 Conflict
        $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-PARTIAL-1',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 5]], // Changed quantity
            'notes' => 'Batch 1 partial receipt',
        ])->assertStatus(409);

        // 4. Receive remaining 6 items (Full receive)
        $response = $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-FULL-2',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 6]],
        ])->assertStatus(201);

        $response->assertJsonPath('data.status', 'received');

        $po->refresh();
        $this->assertEquals(VendorOrderStatus::RECEIVED, $po->status);
        $this->assertNotNull($po->received_at);

        $this->skuA->inventoryItem->refresh();
        $this->assertEquals(20, $this->skuA->inventoryItem->on_hand_quantity);
    }

    /**
     * Test over-receiving quantity rejection.
     */
    public function test_over_receiving_quantity_rejection(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-OVER-RCV',
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

        // Attempt receiving 15 items when only 10 were ordered
        $response = $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-OVER-1',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 15]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);
    }

    /**
     * Test receiving fails on draft or cancelled PO status.
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

        $response = $this->postJson(route('admin.purchases.receive', $po->public_id), [
            'idempotency_key' => 'KEY-DRAFT-1',
            'items' => [['vendor_order_item_id' => $item->id, 'quantity_received' => 5]],
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['items']);
    }
}
