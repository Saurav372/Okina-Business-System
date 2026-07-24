<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryMovementTest extends TestCase
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
            'description' => 'View stock balances and movements',
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

        $category = ProductCategory::factory()->create(['name' => 'Apparel', 'slug' => 'apparel']);

        $this->product = Product::factory()->create([
            'primary_category_id' => $category->id,
            'name' => 'Custom Premium Hooded Sweatshirt',
            'slug' => 'custom-premium-hooded-sweatshirt',
        ]);

        $this->sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'HOODIE-NAVY-L',
            'barcode' => '8909876543210',
            'stock_quantity' => 150,
        ]);

        $this->inventoryItem = InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $this->sku->id],
            [
                'on_hand_quantity' => 150,
                'reserved_quantity' => 20,
                'available_quantity' => 130,
            ]
        );
    }

    public function test_admin_and_inventory_staff_can_view_movement_logs(): void
    {
        InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::STOCK_IN,
            'direction' => InventoryDirection::IN,
            'quantity' => 50,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index'));
        $response->assertOk();
        $response->assertSee('Inventory Movement Audit Trail');
        $response->assertSee('HOODIE-NAVY-L');

        $staffResponse = $this->actingAs($this->inventoryStaffUser)->get(route('admin.inventory.movements.index'));
        $staffResponse->assertOk();
    }

    public function test_unauthorized_users_cannot_access_movement_logs(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('admin.inventory.movements.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_movements_are_sorted_by_latest_first(): void
    {
        $older = InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => Carbon::now()->subDays(5),
            'notes' => 'Older movement',
        ]);

        $newer = InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => Carbon::now()->subDays(1),
            'notes' => 'Newer movement',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index'));
        $response->assertOk();

        $movements = $response->viewData('movements');
        $this->assertEquals($newer->id, $movements->first()->id);
        $this->assertEquals($older->id, $movements->last()->id);
    }

    public function test_search_by_sku_code_product_name_and_reference_id(): void
    {
        InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => now(),
            'notes' => 'Unique search note 12345',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index', ['search' => 'HOODIE-NAVY-L']));
        $response->assertOk();
        $response->assertSee('HOODIE-NAVY-L');

        $searchName = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index', ['search' => 'Hooded Sweatshirt']));
        $searchName->assertOk();
        $searchName->assertSee('HOODIE-NAVY-L');
    }

    public function test_date_range_filtering_default_and_custom_windows(): void
    {
        $recent = InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => Carbon::now()->subDays(2),
            'notes' => 'Recent movement',
        ]);

        $old = InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => Carbon::now()->subDays(45),
            'notes' => 'Old movement 45d ago',
        ]);

        // Default: Last 30 Days -> should see recent, but NOT old
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index'));
        $response->assertOk();
        $response->assertSee('Recent movement');
        $response->assertDontSee('Old movement 45d ago');

        // Custom range: date_from 60 days ago
        $customResponse = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index', [
            'date_from' => Carbon::now()->subDays(60)->toDateString(),
            'date_to' => Carbon::now()->toDateString(),
        ]));
        $customResponse->assertOk();
        $customResponse->assertSee('Old movement 45d ago');
    }

    public function test_filtering_by_movement_type_direction_and_reason(): void
    {
        $inbound = InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::STOCK_IN,
            'direction' => InventoryDirection::IN,
            'reason_code' => InventoryMovementReason::PURCHASE_RECEIPT,
            'occurred_at' => now(),
        ]);

        $outbound = InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::ORDER_DEDUCTION,
            'direction' => InventoryDirection::OUT,
            'reason_code' => InventoryMovementReason::ORDER_FULFILLMENT,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index', [
            'movement_type' => InventoryMovementType::STOCK_IN->value,
        ]));
        $response->assertOk();
        $response->assertSee('Purchase Order Receipt');

        $movements = $response->viewData('movements');
        $this->assertCount(1, $movements);
        $this->assertEquals($inbound->id, $movements->first()->id);
    }

    public function test_sku_id_filter_returns_sku_specific_history_timeline(): void
    {
        $otherSku = ProductSku::factory()->create(['sku_code' => 'OTHER-SKU-99']);
        $otherItem = InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $otherSku->id],
            [
                'on_hand_quantity' => 10,
                'reserved_quantity' => 0,
                'available_quantity' => 10,
            ]
        );

        InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => now(),
        ]);

        InventoryMovement::factory()->create([
            'product_sku_id' => $otherSku->id,
            'inventory_item_id' => $otherItem->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index', ['sku_id' => $this->sku->id]));
        $response->assertOk();
        $response->assertSee('HOODIE-NAVY-L');
        $response->assertDontSee('OTHER-SKU-99');
    }

    public function test_movement_kpi_metrics_calculation(): void
    {
        InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::STOCK_IN,
            'direction' => InventoryDirection::IN,
            'quantity' => 100,
            'occurred_at' => now(),
        ]);

        InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::ORDER_DEDUCTION,
            'direction' => InventoryDirection::OUT,
            'quantity' => 30,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index'));
        $response->assertOk();

        $metrics = $response->viewData('metrics');
        $this->assertEquals(2, $metrics->totalMovements);
        $this->assertEquals(100, $metrics->totalInboundUnits);
        $this->assertEquals(30, $metrics->totalOutboundUnits);
        $this->assertEquals(70, $metrics->netStockDelta);
    }

    public function test_empty_filter_result_shows_empty_state(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.index', ['search' => 'NON-EXISTENT-SKU-999']));
        $response->assertOk();
        $response->assertSee('No inventory movements match the active filters');
        $response->assertSee('Clear All Filters');
    }

    public function test_csv_export_honors_all_active_filters(): void
    {
        InventoryMovement::factory()->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::MANUAL_ADJUSTMENT,
            'direction' => InventoryDirection::ADJUST,
            'reason_code' => InventoryMovementReason::INVENTORY_CORRECTION,
            'quantity' => 25,
            'before_on_hand_quantity' => 100,
            'after_on_hand_quantity' => 125,
            'occurred_at' => now(),
            'notes' => 'Export test audit note',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.export', [
            'search' => 'HOODIE-NAVY-L',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertTrue(str_contains((string) $response->headers->get('content-disposition'), 'attachment; filename='));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('HOODIE-NAVY-L', $content);
        $this->assertStringContainsString('Export test audit note', $content);
        $this->assertStringContainsString('Manual Stock Adjustment', $content);
    }

    public function test_export_handles_large_dataset_without_memory_errors(): void
    {
        InventoryMovement::factory()->count(100)->create([
            'product_sku_id' => $this->sku->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.movements.export'));
        $response->assertOk();

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertGreaterThan(5000, strlen($content));
    }
}
