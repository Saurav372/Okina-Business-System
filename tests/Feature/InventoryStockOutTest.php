<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\InventoryBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryStockOutTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductSku $sku;

    private InventoryBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->sku = ProductSku::factory()->create(['product_id' => $this->product->id]);
        $this->service = new InventoryBalanceService;

        // Initialize SKU to 30 on hand, 5 reserved
        $this->service->setBalance($this->sku, 30, 5);
    }

    /**
     * Test valid stock-out decrements on-hand and syncs cached quantity.
     */
    public function test_valid_stock_out_decrements_balances_and_records_snapshots(): void
    {
        $movement = $this->service->stockOut($this->sku, 10, InventoryMovementReason::ORDER_FULFILLMENT, [
            'notes' => 'Shipped order #456',
        ]);

        $this->assertInstanceOf(InventoryMovement::class, $movement);

        // Assert balance decrements: 30 - 10 = 20 on hand
        // Available: 20 - 5 = 15
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 20,
            'reserved_quantity' => 5,
            'available_quantity' => 15,
        ]);

        $this->sku->refresh();
        $this->assertEquals(15, $this->sku->stock_quantity);

        // Assert snapshots
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'product_sku_id' => $this->sku->id,
            'quantity' => 10,
            'movement_type' => InventoryMovementType::STOCK_OUT->value,
            'direction' => InventoryDirection::OUT->value,
            'before_on_hand_quantity' => 30,
            'after_on_hand_quantity' => 20,
            'before_reserved_quantity' => 5,
            'after_reserved_quantity' => 5,
            'before_available_quantity' => 25,
            'after_available_quantity' => 15,
            'reason_code' => InventoryMovementReason::ORDER_FULFILLMENT->value,
            'notes' => 'Shipped order #456',
        ]);
    }

    /**
     * Test invalid input stock-out quantities are rejected.
     */
    public function test_invalid_stock_out_inputs_rejected(): void
    {
        // 1. Negative quantity
        $this->expectException(InvalidArgumentException::class);
        $this->service->stockOut($this->sku, -5, InventoryMovementReason::INVENTORY_LOSS);
    }

    /**
     * Test zero quantity stock-out is rejected.
     */
    public function test_zero_quantity_stock_out_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->stockOut($this->sku, 0, InventoryMovementReason::INVENTORY_LOSS);
    }

    /**
     * Test stock-out fails with InsufficientStockException if it causes negative on-hand.
     */
    public function test_stock_out_throws_insufficient_stock_exception_on_negative_on_hand(): void
    {
        // current on_hand is 30. Stock-out of 35 would make it -5, which is disallowed by default.
        try {
            $this->service->stockOut($this->sku, 35, InventoryMovementReason::THEFT);
            $this->fail('Expected InsufficientStockException.');
        } catch (InsufficientStockException $e) {
            $this->assertEquals($this->sku->id, $e->sku->id);
            $this->assertEquals(35, $e->requested);
            $this->assertEquals(30, $e->available);
        }

        // Verify state is unchanged
        $this->sku->refresh();
        $this->assertEquals(25, $this->sku->stock_quantity); // 30 - 5 = 25 available
    }

    /**
     * Test allow_negative_stock = true lets on-hand go below zero and records snapshots.
     */
    public function test_allow_negative_stock_allows_negative_on_hand(): void
    {
        $this->sku->inventoryItem->update(['allow_negative_stock' => true]);

        $movement = $this->service->stockOut($this->sku, 35, InventoryMovementReason::THEFT);

        // Assert balance decrements: 30 - 35 = -5 on hand
        // Available: -5 - 5 = -10
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => -5,
            'reserved_quantity' => 5,
            'available_quantity' => -10,
        ]);

        $this->sku->refresh();
        $this->assertEquals(-10, $this->sku->stock_quantity);

        // Assert snapshots
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'product_sku_id' => $this->sku->id,
            'quantity' => 35,
            'before_on_hand_quantity' => 30,
            'after_on_hand_quantity' => -5,
            'before_reserved_quantity' => 5,
            'after_reserved_quantity' => 5,
            'before_available_quantity' => 25,
            'after_available_quantity' => -10,
            'reason_code' => InventoryMovementReason::THEFT->value,
        ]);
    }

    /**
     * Test sequential movement ordering and chaining.
     */
    public function test_sequential_movement_ordering_and_chaining(): void
    {
        // 1st stock-out: 10
        $movement1 = $this->service->stockOut($this->sku, 10, InventoryMovementReason::DAMAGED_GOODS);
        // 2nd stock-out: 8
        $movement2 = $this->service->stockOut($this->sku, 8, InventoryMovementReason::EXPIRED_STOCK);

        // Assert 1st snapshots: 30 -> 20
        $this->assertEquals(30, $movement1->before_on_hand_quantity);
        $this->assertEquals(20, $movement1->after_on_hand_quantity);

        // Assert 2nd snapshots: 20 -> 12 (verifies chaining)
        $this->assertEquals(20, $movement2->before_on_hand_quantity);
        $this->assertEquals(12, $movement2->after_on_hand_quantity);
    }

    /**
     * Test idempotency return logic.
     */
    public function test_race_safe_idempotency_returns_original_record(): void
    {
        $options = ['idempotency_key' => 'idemp-out-100'];

        $movement1 = $this->service->stockOut($this->sku, 5, InventoryMovementReason::ORDER_FULFILLMENT, $options);
        $movement2 = $this->service->stockOut($this->sku, 5, InventoryMovementReason::ORDER_FULFILLMENT, $options);

        $this->assertEquals($movement1->id, $movement2->id);

        $this->sku->refresh();
        $this->assertEquals(20, $this->sku->stock_quantity); // 25 - 5 = 20

        $this->assertDatabaseCount('inventory_movements', 1);
    }

    /**
     * Test database rollback on transaction failure.
     */
    public function test_rollback_on_failure(): void
    {
        $initialAvailable = $this->sku->stock_quantity; // 25

        try {
            $this->service->stockOut($this->sku, 10, InventoryMovementReason::ORDER_FULFILLMENT, [
                'occurred_at' => 'invalid-timestamp-string',
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
            'on_hand_quantity' => 30,
        ]);
    }
}
