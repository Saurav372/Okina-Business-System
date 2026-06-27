<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\InventoryBalanceService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a parent product for SKUs
        $this->product = Product::factory()->create();
    }

    /**
     * Test creating a ProductSku automatically initializes its InventoryItem record.
     */
    public function test_creating_sku_initializes_inventory_item(): void
    {
        // 1. Create SKU without custom stock_quantity (defaults to 0)
        $sku1 = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU-TEST-1',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $sku1->id,
            'on_hand_quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
        ]);

        $sku1->refresh();
        $this->assertEquals(0, $sku1->stock_quantity);

        // 2. Create SKU with custom stock_quantity (e.g. legacy seeding/factories)
        $sku2 = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU-TEST-2',
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $sku2->id,
            'on_hand_quantity' => 10,
            'reserved_quantity' => 0,
            'available_quantity' => 10,
        ]);

        $sku2->refresh();
        $this->assertEquals(10, $sku2->stock_quantity);
    }

    /**
     * Test InventoryBalanceService updates balances correctly and syncs to ProductSku.
     */
    public function test_service_updates_and_syncs_balances_atomically(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);

        $service = new InventoryBalanceService;
        $service->setBalance($sku, 20, 5);

        // Assert inventory_items values
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $sku->id,
            'on_hand_quantity' => 20,
            'reserved_quantity' => 5,
            'available_quantity' => 15,
        ]);

        // Assert cached stock_quantity is updated
        $sku->refresh();
        $this->assertEquals(15, $sku->stock_quantity);
    }

    /**
     * Test double validation of invariants.
     */
    public function test_invariants_application_level_validation(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);
        $service = new InventoryBalanceService;

        // 1. Negative reserved quantity must throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reserved quantity cannot be negative.');
        $service->setBalance($sku, 10, -5);
    }

    /**
     * Test negative on_hand validation when allow_negative_stock is false.
     */
    public function test_invariants_negative_on_hand_fails_if_disallowed(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);
        $service = new InventoryBalanceService;

        // negative on hand is disallowed by default
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('On hand quantity cannot be negative unless negative stock is allowed.');
        $service->setBalance($sku, -10, 0);
    }

    /**
     * Test negative on_hand is allowed when allow_negative_stock is true.
     */
    public function test_invariants_negative_on_hand_allowed_if_enabled(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);
        $sku->inventoryItem->update(['allow_negative_stock' => true]);

        $service = new InventoryBalanceService;
        $service->setBalance($sku, -10, 5); // on_hand = -10, reserved = 5 => available = -15

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $sku->id,
            'on_hand_quantity' => -10,
            'reserved_quantity' => 5,
            'available_quantity' => -15,
        ]);

        $sku->refresh();
        $this->assertEquals(-15, $sku->stock_quantity);
    }

    /**
     * Test transaction integrity and rollback.
     */
    public function test_transaction_rollback_on_failure(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);
        $service = new InventoryBalanceService;

        // Set initial valid balance
        $service->setBalance($sku, 10, 2);

        try {
            // Attempt to update with invalid parameters (will throw Exception)
            $service->setBalance($sku, 20, -5);
        } catch (InvalidArgumentException $e) {
            // Suppressed exception
        }

        // Verify values remained unchanged
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $sku->id,
            'on_hand_quantity' => 10,
            'reserved_quantity' => 2,
            'available_quantity' => 8,
        ]);

        $sku->refresh();
        $this->assertEquals(8, $sku->stock_quantity);
    }

    /**
     * Test that database CHECK constraints enforce safety net.
     */
    public function test_database_constraints_integrity(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            $this->markTestSkipped('Database constraints only enforced/tested on MySQL.');
        }

        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);

        // Attempting to bypass Laravel model validation using raw SQL update
        $this->expectException(QueryException::class);
        DB::table('inventory_items')
            ->where('product_sku_id', $sku->id)
            ->update([
                'reserved_quantity' => -10,
            ]);
    }

    /**
     * Test that available_quantity CHECK constraint enforces matching values.
     */
    public function test_database_available_quantity_constraint(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            $this->markTestSkipped('Database constraints only enforced/tested on MySQL.');
        }

        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);

        // Attempt to bypass recalculateAvailable and save mismatched values
        $this->expectException(QueryException::class);
        DB::table('inventory_items')
            ->where('product_sku_id', $sku->id)
            ->update([
                'on_hand_quantity' => 10,
                'reserved_quantity' => 2,
                'available_quantity' => 5, // mismatched: 10 - 2 != 5
            ]);
    }

    /**
     * Test cascading delete on SKU removes the InventoryItem record.
     */
    public function test_sku_deletion_cascades_to_inventory_item(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);

        $this->assertDatabaseHas('inventory_items', ['product_sku_id' => $sku->id]);

        $sku->forceDelete(); // Force delete to completely remove from table

        $this->assertDatabaseMissing('inventory_items', ['product_sku_id' => $sku->id]);
    }

    /**
     * Test unique constraint on product_sku_id.
     */
    public function test_unique_product_sku_id_constraint(): void
    {
        $sku = ProductSku::factory()->create(['product_id' => $this->product->id]);

        $this->expectException(QueryException::class);
        DB::table('inventory_items')->insert([
            'product_sku_id' => $sku->id,
            'on_hand_quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
