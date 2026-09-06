<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryItemNotFoundException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\InventoryBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InventoryOrderDeductionTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductSku $sku1;

    private ProductSku $sku2;

    private InventoryBalanceService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->sku1 = ProductSku::factory()->create(['product_id' => $this->product->id, 'sku_code' => 'SKU-001']);
        $this->sku2 = ProductSku::factory()->create(['product_id' => $this->product->id, 'sku_code' => 'SKU-002']);
        $this->service = new InventoryBalanceService;
        $this->user = User::factory()->create();

        // SKU 1: 100 on hand, 0 reserved
        $this->service->setBalance($this->sku1, 100, 0);

        // SKU 2: 50 on hand, 0 reserved
        $this->service->setBalance($this->sku2, 50, 0);
    }

    /**
     * Test successful stock deduction for all items in an order.
     */
    public function test_successful_stock_deduction_for_all_items(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku1->id,
            'quantity' => 5,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku2->id,
            'quantity' => 10,
            'sku_code_snapshot' => $this->sku2->sku_code,
            'product_name_snapshot' => 'Product Name 2',
            'product_slug_snapshot' => 'product-slug-2',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        $movements = $this->service->deductOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertCount(2, $movements);

        // Assert balances updated: 100 -> 95 on hand, 50 -> 40 on hand
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku1->id,
            'on_hand_quantity' => 95,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku2->id,
            'on_hand_quantity' => 40,
        ]);

        // Assert parent SKU cached stock quantity matches available quantity
        $this->sku1->refresh();
        $this->sku2->refresh();
        $this->assertEquals(95, $this->sku1->stock_quantity);
        $this->assertEquals(40, $this->sku2->stock_quantity);

        // Assert AuditEvents dispatched for both movements
        Event::assertDispatched(AuditEvent::class, 2);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['sku_public_id'] === 'SKU-001'
                && $event->payload['quantity'] === 5
                && $event->payload['before_on_hand'] === 100
                && $event->payload['after_on_hand'] === 95
                && $event->payload['actor_user_id'] === $this->user->id;
        });

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['sku_public_id'] === 'SKU-002'
                && $event->payload['quantity'] === 10
                && $event->payload['before_on_hand'] === 50
                && $event->payload['after_on_hand'] === 40
                && $event->payload['actor_user_id'] === $this->user->id;
        });
    }

    /**
     * Test empty order returns empty array immediately.
     */
    public function test_empty_order_returns_empty_array(): void
    {
        $order = Order::factory()->create(); // no items

        $movements = $this->service->deductOrderStock($order);
        $this->assertEmpty($movements);
    }

    /**
     * Test insufficient stock on any item rolls back all items completely.
     */
    public function test_insufficient_stock_rolls_back_entire_order(): void
    {
        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku1->id,
            'quantity' => 5,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);
        // SKU 2 has only 50 on hand. Requesting 60 should fail.
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku2->id,
            'quantity' => 60,
            'sku_code_snapshot' => $this->sku2->sku_code,
            'product_name_snapshot' => 'Product Name 2',
            'product_slug_snapshot' => 'product-slug-2',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        $this->expectException(InsufficientStockException::class);

        try {
            $this->service->deductOrderStock($order);
        } finally {
            // Verify BOTH SKU balances remain unchanged (rollback occurred)
            $this->assertDatabaseHas('inventory_items', [
                'product_sku_id' => $this->sku1->id,
                'on_hand_quantity' => 100,
            ]);
            $this->assertDatabaseHas('inventory_items', [
                'product_sku_id' => $this->sku2->id,
                'on_hand_quantity' => 50,
            ]);
        }
    }

    /**
     * Test duplicate calls (idempotency).
     */
    public function test_idempotency_prevents_duplicate_movements_and_audit_events(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $item1 = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku1->id,
            'quantity' => 5,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);
        $item2 = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku2->id,
            'quantity' => 10,
            'sku_code_snapshot' => $this->sku2->sku_code,
            'product_name_snapshot' => 'Product Name 2',
            'product_slug_snapshot' => 'product-slug-2',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        // First call: 2 movements, 2 audit events
        $movements1 = $this->service->deductOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);
        $this->assertCount(2, $movements1);
        Event::assertDispatched(AuditEvent::class, 2);

        // Second call: should return existing movements without duplicates or new events
        $movements2 = $this->service->deductOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);
        $this->assertCount(2, $movements2);
        $this->assertEquals($movements1[0]->id, $movements2[0]->id);
        $this->assertEquals($movements1[1]->id, $movements2[1]->id);

        // Total movements in DB remains 2
        $this->assertDatabaseCount('inventory_movements', 2);

        // Total audit events dispatched remains 2 (no new ones)
        Event::assertDispatched(AuditEvent::class, 2);
    }

    /**
     * Test negative stock scenarios.
     */
    public function test_negative_stock_rules(): void
    {
        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku2->id,
            'quantity' => 60, // exceeds 50 on hand
            'sku_code_snapshot' => $this->sku2->sku_code,
            'product_name_snapshot' => 'Product Name 2',
            'product_slug_snapshot' => 'product-slug-2',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        // 1. allow_negative_stock = false (default) throws InsufficientStockException
        $this->sku2->inventoryItem->update(['allow_negative_stock' => false]);
        try {
            $this->service->deductOrderStock($order);
            $this->fail('Expected InsufficientStockException.');
        } catch (InsufficientStockException $e) {
            // Success
        }

        // 2. allow_negative_stock = true allows it to go below zero
        $this->sku2->inventoryItem->update(['allow_negative_stock' => true]);
        $movements = $this->service->deductOrderStock($order);
        $this->assertCount(1, $movements);
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku2->id,
            'on_hand_quantity' => -10,
        ]);
    }

    /**
     * Test missing InventoryItem throws InventoryItemNotFoundException.
     */
    public function test_missing_inventory_item_throws_exception(): void
    {
        $skuNoInventory = ProductSku::factory()->create(['product_id' => $this->product->id]);
        // Delete auto-initialized inventory item
        InventoryItem::query()->where('product_sku_id', $skuNoInventory->id)->delete();

        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $skuNoInventory->id,
            'quantity' => 1,
            'sku_code_snapshot' => $skuNoInventory->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        $this->expectException(InventoryItemNotFoundException::class);
        $this->service->deductOrderStock($order);
    }

    /**
     * Test duplicate SKUs in the same order are processed correctly.
     */
    public function test_duplicate_skus_in_same_order_processed_correctly(): void
    {
        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku1->id,
            'quantity' => 5,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);
        // Another item with same SKU
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku1->id,
            'quantity' => 10,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        $movements = $this->service->deductOrderStock($order);
        $this->assertCount(2, $movements);

        // Balance should decrease by 15: 100 -> 85
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku1->id,
            'on_hand_quantity' => 85,
        ]);
    }
}
