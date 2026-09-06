<?php

namespace Tests\Feature;

use App\Enums\InventoryLocation;
use App\Enums\InventoryMovementReason;
use App\Enums\WarehouseTransferStatus;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use App\Models\WarehouseTransfer;
use App\Services\WarehouseTransferService;
use App\Support\Inventory\Transfers\WarehouseTransferFilters;
use App\Support\Inventory\Transfers\WarehouseTransferMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminWarehouseTransferTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $inventoryStaffUser;

    protected User $unauthorizedUser;

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

        $category = ProductCategory::factory()->create(['name' => 'Hardware', 'slug' => 'hardware']);

        $this->product = Product::factory()->create([
            'primary_category_id' => $category->id,
            'name' => 'Industrial Precision Bearing Unit',
            'slug' => 'industrial-precision-bearing-unit',
        ]);

        $this->sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'BEARING-6204-2RS',
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

    public function test_admin_and_inventory_staff_can_view_transfers(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-001',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 20,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.transfers.index'));
        $response->assertOk();
        $response->assertSee('TRF-TEST-001');

        $staffResponse = $this->actingAs($this->inventoryStaffUser)->get(route('admin.inventory.transfers.index'));
        $staffResponse->assertOk();
    }

    public function test_unauthorized_users_cannot_access_transfers(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('admin.inventory.transfers.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_create_transfer_record(): void
    {
        $payload = [
            'product_sku_id' => $this->sku->id,
            'source_location' => 'main_warehouse',
            'destination_location' => 'store',
            'quantity' => 15,
            'notes' => 'Transfer 15 units to retail store',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.transfers.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('warehouse_transfers', [
            'product_sku_id' => $this->sku->id,
            'quantity' => 15,
            'status' => WarehouseTransferStatus::DRAFT->value,
        ]);
    }

    public function test_shipping_transfer_deducts_source_stock_and_logs_movement(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-002',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 25,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);
        $shipped = $service->shipTransfer($transfer, 'ship_key_002', $this->adminUser);

        $this->assertEquals(WarehouseTransferStatus::IN_TRANSIT, $shipped->status);
        $this->inventoryItem->refresh();
        $this->assertEquals(75, $this->inventoryItem->on_hand_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $this->sku->id,
            'quantity' => 25,
            'reason_code' => InventoryMovementReason::STOCK_TRANSFER_OUT->value,
        ]);
    }

    public function test_receiving_transfer_adds_destination_stock_and_completes_transfer(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-003',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 20,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);
        $shipped = $service->shipTransfer($transfer, 'ship_key_003', $this->adminUser);
        $completed = $service->receiveTransfer($shipped, 'receive_key_003', $this->adminUser);

        $this->assertEquals(WarehouseTransferStatus::COMPLETED, $completed->status);
        $this->inventoryItem->refresh();
        $this->assertEquals(100, $this->inventoryItem->on_hand_quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $this->sku->id,
            'quantity' => 20,
            'reason_code' => InventoryMovementReason::STOCK_TRANSFER_IN->value,
        ]);
    }

    public function test_validation_blocks_identical_source_and_destination_locations(): void
    {
        $service = app(WarehouseTransferService::class);

        $this->expectException(ValidationException::class);
        $service->initiateTransfer(
            sku: $this->sku,
            sourceLocation: InventoryLocation::MAIN_WAREHOUSE,
            destinationLocation: InventoryLocation::MAIN_WAREHOUSE, // Identical!
            quantity: 10,
            actor: $this->adminUser
        );
    }

    public function test_validation_blocks_transfers_exceeding_source_stock(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-EXCEED',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 500, // 500 exceeds source stock 100!
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);

        $this->expectException(ValidationException::class);
        $service->shipTransfer($transfer, 'ship_exceed_key', $this->adminUser);
    }

    public function test_completed_transfer_cannot_be_cancelled(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-CANCEL-BLOCK',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 10,
            'status' => WarehouseTransferStatus::COMPLETED,
        ]);

        $service = app(WarehouseTransferService::class);

        $this->expectException(ValidationException::class);
        $service->cancelTransfer($transfer, $this->adminUser);
    }

    public function test_transfer_cannot_ship_twice(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-SHIP-TWICE',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 10,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);
        $shipped = $service->shipTransfer($transfer, 'key_s1', $this->adminUser);

        // Attempting to ship an IN_TRANSIT transfer should throw exception
        $this->expectException(ValidationException::class);
        $service->shipTransfer($shipped, 'key_s2', $this->adminUser);
    }

    public function test_transfer_cannot_receive_before_shipping(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-RECEIVE-EARLY',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 10,
            'status' => WarehouseTransferStatus::DRAFT, // DRAFT, not IN_TRANSIT!
        ]);

        $service = app(WarehouseTransferService::class);

        $this->expectException(ValidationException::class);
        $service->receiveTransfer($transfer, 'key_r_early', $this->adminUser);
    }

    public function test_transfer_preserves_total_inventory_across_locations(): void
    {
        $initialStock = $this->inventoryItem->on_hand_quantity; // 100

        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-CONSERV',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 30,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);

        // Ship: stock - 30 = 70
        $shipped = $service->shipTransfer($transfer, 'key_c1', $this->adminUser);
        $this->inventoryItem->refresh();

        // Receive: stock + 30 = 100
        $completed = $service->receiveTransfer($shipped, 'key_c2', $this->adminUser);
        $this->inventoryItem->refresh();

        $this->assertEquals($initialStock, $this->inventoryItem->on_hand_quantity);
    }

    public function test_transfer_movements_share_same_transfer_reference(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-REF-999',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 15,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);
        $shipped = $service->shipTransfer($transfer, 'key_ref_s', $this->adminUser);
        $completed = $service->receiveTransfer($shipped, 'key_ref_r', $this->adminUser);

        $outbound = InventoryMovement::where('reference_type', 'WarehouseTransfer')->where('reference_id', $transfer->id)->where('movement_type', 'stock_out')->first();
        $inbound = InventoryMovement::where('reference_type', 'WarehouseTransfer')->where('reference_id', $transfer->id)->where('movement_type', 'stock_in')->first();

        $this->assertNotNull($outbound);
        $this->assertNotNull($inbound);
        $this->assertEquals($outbound->reference_id, $inbound->reference_id);
    }

    public function test_transfer_operations_are_atomic(): void
    {
        $transfer = WarehouseTransfer::create([
            'transfer_code' => 'TRF-TEST-ATOMIC',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 20,
            'status' => WarehouseTransferStatus::DRAFT,
        ]);

        $service = app(WarehouseTransferService::class);
        $shipped = $service->shipTransfer($transfer, 'atomic_key_1', $this->adminUser);

        $this->assertEquals(WarehouseTransferStatus::IN_TRANSIT, $shipped->status);
        $this->inventoryItem->refresh();
        $this->assertEquals(80, $this->inventoryItem->on_hand_quantity);
    }

    public function test_transfer_kpi_metrics_calculation(): void
    {
        WarehouseTransfer::create([
            'transfer_code' => 'TRF-KPI-1',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 20,
            'status' => WarehouseTransferStatus::IN_TRANSIT,
        ]);

        WarehouseTransfer::create([
            'transfer_code' => 'TRF-KPI-2',
            'product_sku_id' => $this->sku->id,
            'source_location' => InventoryLocation::MAIN_WAREHOUSE,
            'destination_location' => InventoryLocation::STORE,
            'quantity' => 30,
            'status' => WarehouseTransferStatus::COMPLETED,
        ]);

        $metrics = new WarehouseTransferMetrics(new WarehouseTransferFilters);

        $this->assertEquals(2, $metrics->totalTransfersCount);
        $this->assertEquals(1, $metrics->activeInTransitCount);
        $this->assertEquals(1, $metrics->completedTransfersCount);
        $this->assertEquals(50, $metrics->totalTransferredUnits);
    }
}
