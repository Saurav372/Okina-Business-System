<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementType;
use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Models\InventoryItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use App\Services\PurchaseReceivingService;
use App\Support\Purchases\PurchaseOrderCodeGenerator;
use App\Support\Purchases\PurchaseOrderFilters;
use App\Support\Purchases\PurchaseOrderMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminPurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $inventoryStaffUser;

    protected User $unauthorizedUser;

    protected Vendor $vendor;

    protected Product $product;

    protected ProductSku $sku;

    protected InventoryItem $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        $permView = Permission::query()->firstOrCreate(['slug' => 'inventory.view'], [
            'name' => 'Inventory View',
            'group' => 'inventory',
            'guard_name' => 'web',
            'description' => 'View inventory',
            'is_sensitive' => false,
        ]);

        $permManage = Permission::query()->firstOrCreate(['slug' => 'inventory.manage'], [
            'name' => 'Inventory Manage',
            'group' => 'inventory',
            'guard_name' => 'web',
            'description' => 'Manage inventory',
            'is_sensitive' => false,
        ]);

        $permDashboard = Permission::query()->firstOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $adminRole = Role::query()->firstOrCreate(['slug' => Role::ADMIN], [
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$permView->id, $permManage->id, $permDashboard->id]);

        $inventoryRole = Role::query()->firstOrCreate(['slug' => Role::INVENTORY_STAFF], [
            'name' => 'Inventory Staff',
            'guard_name' => 'web',
        ]);
        $inventoryRole->permissions()->syncWithoutDetaching([$permView->id, $permManage->id, $permDashboard->id]);

        $unauthRole = Role::query()->firstOrCreate(['slug' => 'guest_role'], [
            'name' => 'Guest Role',
            'guard_name' => 'web',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);

        $this->inventoryStaffUser = User::factory()->create();
        $this->inventoryStaffUser->roles()->attach($inventoryRole);

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->roles()->attach($unauthRole);

        $this->vendor = Vendor::create([
            'status' => VendorStatus::ACTIVE,
            'name' => 'Apex Textiles Supplier',
            'vendor_code' => 'VND-APEX-01',
        ]);

        $category = ProductCategory::factory()->create(['name' => 'Textiles', 'slug' => 'textiles']);

        $this->product = Product::factory()->create([
            'primary_category_id' => $category->id,
            'name' => 'Heavyweight Cotton Canvas Roll',
            'slug' => 'heavyweight-cotton-canvas-roll',
        ]);

        $this->sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'CANVAS-RAW-400GSM',
            'stock_quantity' => 100,
        ]);

        $this->inventoryItem = InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $this->sku->id],
            [
                'on_hand_quantity' => 100,
                'reserved_quantity' => 0,
                'available_quantity' => 100,
            ]
        );
    }

    protected function createTestPo(array $attributes = []): VendorOrder
    {
        return VendorOrder::create(array_merge([
            'public_id' => PurchaseOrderCodeGenerator::generate(),
            'vendor_id' => $this->vendor->id,
            'created_by_user_id' => $this->adminUser->id,
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'currency' => 'INR',
            'subtotal_amount_minor' => 100000,
            'tax_amount_minor' => 0,
            'shipping_amount_minor' => 0,
            'discount_amount_minor' => 0,
            'total_amount_minor' => 100000,
        ], $attributes));
    }

    public function test_admin_and_inventory_staff_can_view_purchase_orders(): void
    {
        $po = $this->createTestPo(['public_id' => 'PO-TEST-101']);

        $response = $this->actingAs($this->adminUser)->get(route('admin.purchases.index'));
        $response->assertOk();
        $response->assertSee('PO-TEST-101');

        $staffResponse = $this->actingAs($this->inventoryStaffUser)->get(route('admin.purchases.index'));
        $staffResponse->assertOk();
    }

    public function test_unauthorized_users_cannot_access_purchase_orders(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('admin.purchases.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_receiving_purchase_order_increments_inventory_stock(): void
    {
        $po = $this->createTestPo([
            'public_id' => 'PO-TEST-102',
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 50,
            'quantity_received' => 0,
            'unit_cost_minor' => 2000,
        ]);

        $payload = [
            'idempotency_key' => 'rcpt_key_test_102',
            'items' => [
                [
                    'vendor_order_item_id' => $lineItem->id,
                    'quantity_received' => 50,
                ],
            ],
            'notes' => 'Received 50 rolls of canvas',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.receive', $po), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->inventoryItem->refresh();
        $this->assertEquals(150, $this->inventoryItem->on_hand_quantity);

        $lineItem->refresh();
        $this->assertEquals(50, $lineItem->quantity_received);

        $po->refresh();
        $this->assertEquals(VendorOrderStatus::RECEIVED, $po->status);
    }

    public function test_receiving_creates_inventory_movements_linked_to_vendor_order(): void
    {
        $po = $this->createTestPo([
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 30,
            'quantity_received' => 0,
            'unit_cost_minor' => 1000,
        ]);

        $receivingService = app(PurchaseReceivingService::class);
        $receivingService->receive(
            order: $po,
            items: [
                ['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 30],
            ],
            idempotencyKey: 'key_link_test_1',
            actor: $this->adminUser,
        );

        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $this->sku->id,
            'vendor_order_id' => $po->id,
            'vendor_order_item_id' => $lineItem->id,
            'quantity' => 30,
            'movement_type' => InventoryMovementType::STOCK_IN->value,
        ]);
    }

    public function test_receiving_cannot_exceed_remaining_quantity(): void
    {
        $po = $this->createTestPo([
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 20,
            'quantity_received' => 15,
            'unit_cost_minor' => 1000,
        ]);

        $receivingService = app(PurchaseReceivingService::class);

        $this->expectException(ValidationException::class);
        $receivingService->receive(
            order: $po,
            items: [
                ['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 10], // 10 exceeds remaining 5!
            ],
            idempotencyKey: 'key_exceed_test',
            actor: $this->adminUser,
        );
    }

    public function test_duplicate_receiving_request_is_rejected(): void
    {
        $po = $this->createTestPo([
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 50,
            'quantity_received' => 0,
            'unit_cost_minor' => 1000,
        ]);

        $receivingService = app(PurchaseReceivingService::class);

        // First receipt
        $receivingService->receive(
            order: $po,
            items: [['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 20]],
            idempotencyKey: 'idempotent_key_100',
            actor: $this->adminUser
        );

        $this->inventoryItem->refresh();
        $this->assertEquals(120, $this->inventoryItem->on_hand_quantity);

        // Duplicate call with exact same idempotency key
        $receivingService->receive(
            order: $po,
            items: [['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 20]],
            idempotencyKey: 'idempotent_key_100',
            actor: $this->adminUser
        );

        // Stock quantity should NOT be incremented again!
        $this->inventoryItem->refresh();
        $this->assertEquals(120, $this->inventoryItem->on_hand_quantity);
    }

    public function test_receiving_multiple_partial_deliveries_reaches_received_status(): void
    {
        $po = $this->createTestPo([
            'status' => VendorOrderStatus::ORDERED->value,
        ]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 100,
            'quantity_received' => 0,
            'unit_cost_minor' => 1000,
        ]);

        $receivingService = app(PurchaseReceivingService::class);

        // Partial Receipt 1: 40 units
        $receivingService->receive(
            order: $po,
            items: [['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 40]],
            idempotencyKey: 'batch_p1',
            actor: $this->adminUser
        );

        $po->refresh();
        $this->assertEquals(VendorOrderStatus::PARTIALLY_RECEIVED, $po->status);

        // Partial Receipt 2: 60 units (completes order)
        $receivingService->receive(
            order: $po,
            items: [['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 60]],
            idempotencyKey: 'batch_p2',
            actor: $this->adminUser
        );

        $po->refresh();
        $this->assertEquals(VendorOrderStatus::RECEIVED, $po->status);
    }

    public function test_cancelled_purchase_order_cannot_be_received(): void
    {
        $po = $this->createTestPo([
            'status' => VendorOrderStatus::CANCELLED->value,
        ]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 50,
            'quantity_received' => 0,
            'unit_cost_minor' => 1000,
        ]);

        $receivingService = app(PurchaseReceivingService::class);

        $this->expectException(InvalidPurchaseOrderStatusTransitionException::class);
        $receivingService->receive(
            order: $po,
            items: [['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 10]],
            idempotencyKey: 'cancelled_key_test',
            actor: $this->adminUser
        );
    }

    public function test_purchase_order_kpi_metrics_calculation(): void
    {
        $this->createTestPo([
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'total_amount_minor' => 50000,
        ]);

        $this->createTestPo([
            'status' => VendorOrderStatus::RECEIVED->value,
            'payment_status' => VendorOrderPaymentStatus::PAID->value,
            'total_amount_minor' => 30000,
        ]);

        $metrics = new PurchaseOrderMetrics(new PurchaseOrderFilters);

        $this->assertEquals(2, $metrics->totalOrdersCount);
        $this->assertEquals(1, $metrics->activePendingCount);
        $this->assertEquals(80000, $metrics->totalPurchaseValueMinor);
        $this->assertEquals(50000, $metrics->unpaidLiabilityMinor);
    }
    // -------------------------------------------------------------------------
    // Regression Tests (U5.1.3 field-name & form-key fix guards)
    // -------------------------------------------------------------------------

    public function test_purchase_order_index_displays_ordered_at_date(): void
    {
        $po = $this->createTestPo([
            'public_id' => 'PO-REG-DATE-001',
            'status' => VendorOrderStatus::ORDERED->value,
            'ordered_at' => '2026-06-15 10:00:00',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.index'));

        $response->assertOk();
        $response->assertSee('2026-06-15');    // ordered_at rendered correctly
        $response->assertDontSee('N/A');        // old fallback for wrong field name
    }

    public function test_purchase_order_show_displays_quantity_ordered_and_unit_cost(): void
    {
        $po = $this->createTestPo(['status' => VendorOrderStatus::ORDERED->value]);

        VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 75,
            'quantity_received' => 0,
            'unit_cost_minor' => 35000, // ₹350.00
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.show', $po->public_id));

        $response->assertOk();
        $response->assertSee('75');       // quantity_ordered shown
        $response->assertSee('350.00');   // unit_cost_minor / 100 shown as ₹350.00
    }

    public function test_purchase_order_show_receive_form_uses_items_array_key(): void
    {
        $po = $this->createTestPo(['status' => VendorOrderStatus::ORDERED->value]);

        VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost_minor' => 1000,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.purchases.show', $po->public_id));

        $response->assertOk();
        // Form must use items[0][vendor_order_item_id] NOT line_items[0][...]
        $response->assertSee('items[0][vendor_order_item_id]', false);
        $response->assertDontSee('line_items[', false);
    }

    public function test_receive_request_requires_idempotency_key(): void
    {
        $po = $this->createTestPo(['status' => VendorOrderStatus::ORDERED->value]);

        $lineItem = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_cost_minor' => 1000,
        ]);

        // Submit without idempotency_key — must fail validation
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchases.receive', $po->public_id), [
                'items' => [
                    ['vendor_order_item_id' => $lineItem->id, 'quantity_received' => 5],
                ],
                // idempotency_key intentionally omitted
            ]);

        $response->assertSessionHasErrors('idempotency_key', null, 'receiving');
    }

    public function test_vendor_order_item_remaining_quantity_and_fully_received_helpers(): void
    {
        $po = $this->createTestPo(['status' => VendorOrderStatus::ORDERED->value]);

        // Second SKU needed: vendor_order_items has a unique(vendor_order_id, product_sku_id) constraint
        $sku2 = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'CANVAS-RAW-600GSM',
        ]);

        $partial = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $this->sku->id,
            'quantity_ordered' => 50,
            'quantity_received' => 20,
            'unit_cost_minor' => 1000,
        ]);

        $full = VendorOrderItem::create([
            'vendor_order_id' => $po->id,
            'product_sku_id' => $sku2->id,
            'quantity_ordered' => 30,
            'quantity_received' => 30,
            'unit_cost_minor' => 1000,
        ]);

        // remainingQuantity()
        $this->assertEquals(30, $partial->remainingQuantity()); // 50 - 20
        $this->assertEquals(0, $full->remainingQuantity());     // 30 - 30

        // isFullyReceived()
        $this->assertFalse($partial->isFullyReceived());
        $this->assertTrue($full->isFullyReceived());
    }
}
