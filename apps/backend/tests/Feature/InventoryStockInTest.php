<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\InventoryBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class InventoryStockInTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductSku $sku;

    private InventoryBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->sku = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU-IN-1',
        ]);
        // The SKU is automatically initialized with InventoryItem (0, 0, 0)
        $this->service = new InventoryBalanceService;
    }

    /**
     * Test valid stock-in increments balances and writes trace.
     */
    public function test_valid_stock_in_increases_balances_and_records_trace(): void
    {
        $movement = $this->service->stockIn($this->sku, 15, InventoryMovementReason::PURCHASE_RECEIPT, [
            'notes' => 'Received PO #123',
        ]);

        $this->assertInstanceOf(InventoryMovement::class, $movement);

        // Assert balance increments
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 15,
            'reserved_quantity' => 0,
            'available_quantity' => 15,
        ]);

        $this->sku->refresh();
        $this->assertEquals(15, $this->sku->stock_quantity);

        // Assert movement trace
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'product_sku_id' => $this->sku->id,
            'quantity' => 15,
            'movement_type' => InventoryMovementType::STOCK_IN->value,
            'direction' => InventoryDirection::IN->value,
            'before_on_hand_quantity' => 0,
            'after_on_hand_quantity' => 15,
            'before_reserved_quantity' => 0,
            'after_reserved_quantity' => 0,
            'before_available_quantity' => 0,
            'after_available_quantity' => 15,
            'reason_code' => InventoryMovementReason::PURCHASE_RECEIPT->value,
            'notes' => 'Received PO #123',
        ]);
    }

    /**
     * Test invalid stock-in inputs throw InvalidArgumentException.
     */
    public function test_invalid_stock_in_inputs_rejected(): void
    {
        // 1. Negative quantity
        $this->expectException(InvalidArgumentException::class);
        $this->service->stockIn($this->sku, -5, InventoryMovementReason::MANUAL_ADJUSTMENT);
    }

    /**
     * Test zero quantity stock-in throws InvalidArgumentException.
     */
    public function test_zero_quantity_stock_in_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->stockIn($this->sku, 0, InventoryMovementReason::MANUAL_ADJUSTMENT);
    }

    /**
     * Test append-only immutability.
     */
    public function test_inventory_movements_are_append_only(): void
    {
        $movement = $this->service->stockIn($this->sku, 10, InventoryMovementReason::MIGRATION);

        // 1. Attempting to update throws LogicException
        try {
            $movement->update(['quantity' => 20]);
            $this->fail('Expected LogicException on update.');
        } catch (LogicException $e) {
            $this->assertEquals('Inventory movements are append-only and cannot be updated.', $e->getMessage());
        }

        // 2. Attempting to delete throws LogicException
        try {
            $movement->delete();
            $this->fail('Expected LogicException on delete.');
        } catch (LogicException $e) {
            $this->assertEquals('Inventory movements are append-only and cannot be deleted.', $e->getMessage());
        }

        // Confirm original record remains intact
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'quantity' => 10,
        ]);
    }

    /**
     * Test idempotency return logic.
     */
    public function test_race_safe_idempotency_returns_original_record(): void
    {
        $options = ['idempotency_key' => 'idemp-key-100'];

        $movement1 = $this->service->stockIn($this->sku, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, $options);
        $movement2 = $this->service->stockIn($this->sku, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, $options);

        // Assert second call returned same movement without mutating stock a second time
        $this->assertEquals($movement1->id, $movement2->id);

        $this->sku->refresh();
        $this->assertEquals(10, $this->sku->stock_quantity); // not 20

        $this->assertDatabaseCount('inventory_movements', 1);
    }

    /**
     * Test user context auditing.
     */
    public function test_user_context_populates_created_by_user_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $movement = $this->service->stockIn($this->sku, 5, InventoryMovementReason::MANUAL_ADJUSTMENT);

        $this->assertEquals($user->id, $movement->created_by_user_id);
    }

    /**
     * Test custom occurred_at parameter is respected.
     */
    public function test_occurred_at_parameter_respected(): void
    {
        $pastTime = Carbon::now()->subDays(5);

        $movement = $this->service->stockIn($this->sku, 12, InventoryMovementReason::INVENTORY_CORRECTION, [
            'occurred_at' => $pastTime,
        ]);

        $this->assertEquals($pastTime->toDateTimeString(), $movement->occurred_at->toDateTimeString());
    }

    /**
     * Test database rollback on transaction failure.
     */
    public function test_rollback_on_failure(): void
    {
        $initialAvailable = $this->sku->stock_quantity; // 0

        try {
            $this->service->stockIn($this->sku, 20, InventoryMovementReason::MANUAL_ADJUSTMENT, [
                'occurred_at' => 'invalid-timestamp-string', // will trigger QueryException on database insert
            ]);
            $this->fail('Expected Throwable.');
        } catch (\Throwable $e) {
            // Success
        }

        // Verify balance and stock counts were not committed
        $this->sku->refresh();
        $this->assertEquals($initialAvailable, $this->sku->stock_quantity);

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 0,
        ]);

        $this->assertDatabaseCount('inventory_movements', 0);
    }
}
