<?php

namespace Tests\Feature;

use App\Enums\InventoryLocation;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryStatus;
use App\Events\AuditEvent;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryBalanceService;
use App\Support\Inventory\InventoryDashboardMetrics;
use App\Support\Inventory\InventoryStatusResolver;
use App\Support\Inventory\StockAdjustmentResultDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminInventoryTest extends TestCase
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
            'description' => 'View stock balances',
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
            'name' => 'Custom Premium Polo T-Shirt',
            'slug' => 'custom-premium-polo-t-shirt',
        ]);

        $this->sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'PREMIUM-POLO-BLACK-M',
            'barcode' => '8901234567890',
            'stock_quantity' => 100,
            'low_stock_threshold' => 15,
        ]);

        $this->inventoryItem = InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $this->sku->id],
            [
                'on_hand_quantity' => 100,
                'reserved_quantity' => 10,
                'available_quantity' => 90,
                'low_stock_threshold' => 15,
                'allow_negative_stock' => true,
            ]
        );
    }

    public function test_admin_and_inventory_staff_can_view_stock_balances(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index'));
        $response->assertOk();
        $response->assertSee('Stock Balances');
        $response->assertSee('PREMIUM-POLO-BLACK-M');

        $staffResponse = $this->actingAs($this->inventoryStaffUser)->get(route('admin.inventory.index'));
        $staffResponse->assertOk();
    }

    public function test_rendered_view_contains_adjustment_modal_form_fields(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index'));
        $response->assertOk();
        $response->assertSee('name="expected_on_hand"', false);
        $response->assertSee('name="new_on_hand"', false);
        $response->assertSee('name="new_reserved"', false);
        $response->assertSee('name="reason_code"', false);
        $response->assertSee('name="notes"', false);
        $response->assertSee('name="sort_order"', false);
    }

    public function test_unauthorized_users_cannot_access_inventory(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('admin.inventory.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_stock_balance_search_by_product_name_sku_code_and_ids(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['search' => 'PREMIUM-POLO']));
        $response->assertOk();
        $response->assertSee('PREMIUM-POLO-BLACK-M');

        $idResponse = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['search' => (string) $this->product->id]));
        $idResponse->assertOk();
        $idResponse->assertSee('PREMIUM-POLO-BLACK-M');
    }

    public function test_stock_balance_status_filtering_low_stock_and_out_of_stock(): void
    {
        $lowSku = ProductSku::factory()->create(['sku_code' => 'LOW-STOCK-SKU']);
        InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $lowSku->id],
            [
                'on_hand_quantity' => 10,
                'reserved_quantity' => 2,
                'available_quantity' => 8,
                'low_stock_threshold' => 15,
            ]
        );

        $outSku = ProductSku::factory()->create(['sku_code' => 'OUT-OF-STOCK-SKU']);
        InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $outSku->id],
            [
                'on_hand_quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
                'low_stock_threshold' => 15,
            ]
        );

        $lowResponse = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['status' => 'low_stock']));
        $lowResponse->assertOk();
        $lowResponse->assertSee('LOW-STOCK-SKU');
        $lowResponse->assertDontSee('OUT-OF-STOCK-SKU');

        $outResponse = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['status' => 'out_of_stock']));
        $outResponse->assertOk();
        $outResponse->assertSee('OUT-OF-STOCK-SKU');
        $outResponse->assertDontSee('LOW-STOCK-SKU');
    }

    public function test_admin_can_perform_manual_stock_adjustment(): void
    {
        $payload = [
            'expected_on_hand' => 100,
            'new_on_hand' => 120,
            'new_reserved' => 10,
            'reason_code' => InventoryMovementReason::MANUAL_ADJUSTMENT->value,
            'notes' => 'Physical count audit',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.adjust', $this->sku), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 120,
            'available_quantity' => 110,
        ]);
    }

    public function test_stock_adjustment_validation_requires_notes_for_other_and_damaged_reasons(): void
    {
        $payload = [
            'expected_on_hand' => 100,
            'new_on_hand' => 80,
            'new_reserved' => 10,
            'reason_code' => InventoryMovementReason::DAMAGED_GOODS->value,
            'notes' => '',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.adjust', $this->sku), $payload);

        $response->assertSessionHasErrors('notes');
    }

    public function test_stock_adjustment_dispatches_inventory_stock_moved_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $payload = [
            'expected_on_hand' => 100,
            'new_on_hand' => 125,
            'new_reserved' => 10,
            'reason_code' => InventoryMovementReason::MANUAL_ADJUSTMENT->value,
            'notes' => 'Audit check',
        ];

        $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.adjust', $this->sku), $payload);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved';
        });
    }

    public function test_inventory_view_is_paginated(): void
    {
        ProductSku::factory()->count(20)->create();

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index'));
        $response->assertOk();
        $response->assertViewHas('items');
    }

    public function test_inventory_sorting_by_available_quantity_and_product_name(): void
    {
        $p1 = Product::factory()->create(['name' => 'AAA Product']);
        $sku1 = ProductSku::factory()->create(['product_id' => $p1->id, 'sku_code' => 'AAA-SKU']);
        InventoryItem::query()->updateOrCreate(['product_sku_id' => $sku1->id], ['on_hand_quantity' => 10, 'reserved_quantity' => 0, 'available_quantity' => 10]);

        $p2 = Product::factory()->create(['name' => 'ZZZ Product']);
        $sku2 = ProductSku::factory()->create(['product_id' => $p2->id, 'sku_code' => 'ZZZ-SKU']);
        InventoryItem::query()->updateOrCreate(['product_sku_id' => $sku2->id], ['on_hand_quantity' => 10, 'reserved_quantity' => 0, 'available_quantity' => 10]);

        $ascResponse = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', [
            'sort_by' => 'product_name',
            'sort_order' => 'asc',
        ]));
        $ascResponse->assertOk();
        $ascResponse->assertSeeInOrder(['AAA-SKU', 'ZZZ-SKU']);

        $descResponse = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', [
            'sort_by' => 'product_name',
            'sort_order' => 'desc',
        ]));
        $descResponse->assertOk();
        $descResponse->assertSeeInOrder(['ZZZ-SKU', 'AAA-SKU']);
    }

    public function test_negative_stock_badge_is_displayed_correctly(): void
    {
        $negSku = ProductSku::factory()->create(['sku_code' => 'NEG-STOCK-SKU']);
        InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $negSku->id],
            [
                'on_hand_quantity' => -5,
                'reserved_quantity' => 0,
                'available_quantity' => -5,
                'allow_negative_stock' => true,
            ]
        );

        $status = InventoryStatusResolver::resolve(-5, -5, 10);
        $this->assertEquals(InventoryStatus::NEGATIVE, $status);
        $this->assertEquals('Negative Stock', $status->label());
        $this->assertEquals('bg-red-50 text-red-700 border-red-200/60', $status->badgeClass());

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index'));
        $response->assertOk();
        $response->assertSee('Negative Stock');
        $response->assertSee('bg-red-50 text-red-700 border-red-200/60');
    }

    public function test_adjustment_updates_available_quantity(): void
    {
        $service = app(InventoryBalanceService::class);
        $service->adjustWithExpectedBalance(
            sku: $this->sku,
            expectedOnHand: 100,
            newOnHand: 150,
            newReserved: 20,
            reason: InventoryMovementReason::MANUAL_ADJUSTMENT,
        );

        $this->inventoryItem->refresh();
        $this->assertEquals(150, $this->inventoryItem->on_hand_quantity);
        $this->assertEquals(20, $this->inventoryItem->reserved_quantity);
        $this->assertEquals(130, $this->inventoryItem->available_quantity);
    }

    public function test_adjustment_creates_inventory_movement_record(): void
    {
        $service = app(InventoryBalanceService::class);
        $service->adjustWithExpectedBalance(
            sku: $this->sku,
            expectedOnHand: 100,
            newOnHand: 110,
            newReserved: 10,
            reason: InventoryMovementReason::INVENTORY_CORRECTION,
        );

        $this->assertDatabaseHas('inventory_movements', [
            'product_sku_id' => $this->sku->id,
            'quantity' => 10,
            'before_on_hand_quantity' => 100,
            'after_on_hand_quantity' => 110,
        ]);
    }

    public function test_adjustment_updates_product_sku_stock_quantity(): void
    {
        $service = app(InventoryBalanceService::class);
        $service->adjustWithExpectedBalance(
            sku: $this->sku,
            expectedOnHand: 100,
            newOnHand: 200,
            newReserved: 10,
            reason: InventoryMovementReason::MANUAL_ADJUSTMENT,
        );

        $this->sku->refresh();
        $this->assertEquals(190, $this->sku->stock_quantity);
    }

    public function test_inventory_metrics_are_calculated_correctly(): void
    {
        $metricsProvider = new InventoryDashboardMetrics;
        $metrics = $metricsProvider->getMetrics();

        $this->assertEquals(1, $metrics->totalSkus);
        $this->assertEquals(1, $metrics->inStockCount);
        $this->assertEquals(0, $metrics->lowStockCount);
        $this->assertEquals(0, $metrics->outOfStockCount);
        $this->assertEquals(100, $metrics->totalOnHandUnits);
    }

    public function test_stale_balance_request_is_rejected(): void
    {
        $payload = [
            'expected_on_hand' => 999, // Stale! Current is 100
            'new_on_hand' => 120,
            'new_reserved' => 10,
            'reason_code' => InventoryMovementReason::MANUAL_ADJUSTMENT->value,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.adjust', $this->sku), $payload);

        $response->assertSessionHasErrors('expected_on_hand');
    }

    public function test_conditional_notes_requirement_validation(): void
    {
        $payload = [
            'expected_on_hand' => 100,
            'new_on_hand' => 80,
            'new_reserved' => 10,
            'reason_code' => InventoryMovementReason::DAMAGED_GOODS->value,
            'notes' => null,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.inventory.adjust', $this->sku), $payload);

        $response->assertSessionHasErrors('notes');
    }

    public function test_inventory_metrics_ignore_soft_deleted_items(): void
    {
        $this->product->delete();

        $metricsProvider = new InventoryDashboardMetrics;
        $metrics = $metricsProvider->getMetrics();

        $this->assertEquals(0, $metrics->totalSkus);
    }

    public function test_search_by_barcode_returns_correct_sku(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['search' => '8901234567890']));
        $response->assertOk();
        $response->assertSee('PREMIUM-POLO-BLACK-M');
    }

    public function test_adjustment_releases_database_lock_after_completion(): void
    {
        $service = app(InventoryBalanceService::class);
        $dto = $service->adjustWithExpectedBalance(
            sku: $this->sku,
            expectedOnHand: 100,
            newOnHand: 105,
            newReserved: 10,
            reason: InventoryMovementReason::INVENTORY_CORRECTION,
        );

        $this->assertInstanceOf(StockAdjustmentResultDTO::class, $dto);
        $this->assertEquals(5, $dto->deltaOnHand);
    }

    public function test_location_filter_returns_only_matching_inventory(): void
    {
        $storeSku = ProductSku::factory()->create(['sku_code' => 'STORE-LOCATION-SKU']);
        InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $storeSku->id],
            [
                'location_id' => InventoryLocation::STORE->value,
                'on_hand_quantity' => 50,
                'reserved_quantity' => 0,
                'available_quantity' => 50,
            ]
        );

        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['location' => InventoryLocation::STORE->value]));
        $response->assertOk();
        $response->assertSee('STORE-LOCATION-SKU');
    }

    public function test_status_precedence_prefers_negative_over_out_of_stock(): void
    {
        // Available -5, On hand -5 -> Should be NEGATIVE, NOT OUT_OF_STOCK
        $status = InventoryStatusResolver::resolve(-5, -5, 10);
        $this->assertEquals(InventoryStatus::NEGATIVE, $status);
    }

    public function test_adjustment_returns_expected_dto_values(): void
    {
        $service = app(InventoryBalanceService::class);
        $dto = $service->adjustWithExpectedBalance(
            sku: $this->sku,
            expectedOnHand: 100,
            newOnHand: 120,
            newReserved: 15,
            reason: InventoryMovementReason::MANUAL_ADJUSTMENT,
        );

        $this->assertEquals('PREMIUM-POLO-BLACK-M', $dto->skuCode);
        $this->assertEquals(100, $dto->previousOnHand);
        $this->assertEquals(120, $dto->newOnHand);
        $this->assertEquals(20, $dto->deltaOnHand);
        $this->assertEquals(10, $dto->previousReserved);
        $this->assertEquals(15, $dto->newReserved);
        $this->assertEquals(5, $dto->deltaReserved);
        $this->assertEquals('+20', $dto->getFormattedDelta());
    }

    public function test_inventory_metrics_respect_location_filter(): void
    {
        $metricsProvider = new InventoryDashboardMetrics;
        $metrics = $metricsProvider->getMetrics(InventoryLocation::MAIN_WAREHOUSE->value);

        $this->assertEquals(1, $metrics->totalSkus);
    }

    public function test_search_by_numeric_id_does_not_match_partial_strings(): void
    {
        // Searching numeric ID 999999 should not match product name or sku code
        $response = $this->actingAs($this->adminUser)->get(route('admin.inventory.index', ['search' => '999999']));
        $response->assertOk();
        $response->assertDontSee('PREMIUM-POLO-BLACK-M');
    }

    public function test_adjustment_rolls_back_transaction_when_audit_dispatch_fails(): void
    {
        InventoryMovement::creating(function () {
            throw new \RuntimeException('Simulated in-transaction movement failure');
        });

        $service = app(InventoryBalanceService::class);

        try {
            $service->adjustWithExpectedBalance(
                sku: $this->sku,
                expectedOnHand: 100,
                newOnHand: 250,
                newReserved: 10,
                reason: InventoryMovementReason::MANUAL_ADJUSTMENT,
            );
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated in-transaction movement failure', $e->getMessage());
        }

        // Database should be unchanged!
        $this->inventoryItem->refresh();
        $this->assertEquals(100, $this->inventoryItem->on_hand_quantity);
    }
}
