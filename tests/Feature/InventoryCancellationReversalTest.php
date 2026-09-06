<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Events\AuditEvent;
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

class InventoryCancellationReversalTest extends TestCase
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

        // Initialize: SKU1 = 100, SKU2 = 50
        $this->service->setBalance($this->sku1, 100, 0);
        $this->service->setBalance($this->sku2, 50, 0);
    }

    /**
     * Test successful stock reversal after a deduction has occurred.
     */
    public function test_successful_stock_reversal(): void
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

        // 1. Deduct stock first
        $this->service->deductOrderStock($order);

        // Reset fake events to only track reversal dispatches
        Event::fake([AuditEvent::class]);

        // 2. Reverse stock
        $reversals = $this->service->reverseOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertCount(2, $reversals);

        // Verify balances restored: 95 -> 100, 40 -> 50
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku1->id,
            'on_hand_quantity' => 100,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku2->id,
            'on_hand_quantity' => 50,
        ]);

        $this->sku1->refresh();
        $this->sku2->refresh();
        $this->assertEquals(100, $this->sku1->stock_quantity);
        $this->assertEquals(50, $this->sku2->stock_quantity);

        // Verify AuditEvents
        Event::assertDispatched(AuditEvent::class, 2);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['sku_public_id'] === 'SKU-001'
                && $event->payload['movement_type'] === InventoryMovementType::CANCELLATION_REVERSAL->value
                && $event->payload['direction'] === InventoryDirection::IN->value
                && $event->payload['reason'] === InventoryMovementReason::ORDER_CANCELLATION->value
                && $event->payload['quantity'] === 5
                && $event->payload['before_on_hand'] === 95
                && $event->payload['after_on_hand'] === 100
                && $event->payload['actor_user_id'] === $this->user->id;
        });
    }

    /**
     * Test empty order returns empty array immediately.
     */
    public function test_empty_order_returns_empty_array(): void
    {
        $order = Order::factory()->create(); // no items

        $movements = $this->service->reverseOrderStock($order);
        $this->assertEmpty($movements);
    }

    /**
     * Test skipping items that have no prior deduction movement.
     */
    public function test_skapping_items_without_deduction(): void
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

        // No deduction runs. Calling reverse should be a no-op
        $reversals = $this->service->reverseOrderStock($order);
        $this->assertEmpty($reversals);

        // Verify no audit events were dispatched
        Event::assertNotDispatched(AuditEvent::class);

        // Balances remain 100
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku1->id,
            'on_hand_quantity' => 100,
        ]);
    }

    /**
     * Test mixed order: Item A has prior deduction, Item B does not.
     */
    public function test_mixed_order_reversal(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $itemA = OrderItem::create([
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
        $itemB = OrderItem::create([
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

        // Deduct stock only for SKU 1 manually (so SKU 2 has no deduction)
        $this->service->recordMovement(
            $this->sku1,
            5,
            InventoryMovementType::ORDER_DEDUCTION,
            InventoryDirection::OUT,
            InventoryMovementReason::ORDER_FULFILLMENT,
            ['order_id' => $order->id, 'order_item_id' => $itemA->id]
        );

        $this->sku1->inventoryItem->refresh();
        $this->assertEquals(95, $this->sku1->inventoryItem->on_hand_quantity);

        // Reset fake events
        Event::fake([AuditEvent::class]);

        // Run reverse Order
        $reversals = $this->service->reverseOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);

        // Only Item A is reversed
        $this->assertCount(1, $reversals);
        $this->assertEquals($itemA->id, $reversals[0]->order_item_id);

        // Balance of SKU 1 restored to 100, SKU 2 remains 50
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku1->id,
            'on_hand_quantity' => 100,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku2->id,
            'on_hand_quantity' => 50,
        ]);

        // Only 1 audit event dispatched
        Event::assertDispatched(AuditEvent::class, 1);
    }

    /**
     * Test duplicate calls (idempotency).
     */
    public function test_idempotency_prevents_duplicate_reversals_and_events(): void
    {
        $order = Order::factory()->create();
        $item = OrderItem::create([
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

        // Deduct
        $this->service->deductOrderStock($order);

        Event::fake([AuditEvent::class]);

        // First reversal call: 1 movement, 1 event
        $reversals1 = $this->service->reverseOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);
        $this->assertCount(1, $reversals1);
        Event::assertDispatched(AuditEvent::class, 1);

        // Second reversal call: should return existing and not dispatch new events
        $reversals2 = $this->service->reverseOrderStock($order, [
            'created_by_user_id' => $this->user->id,
        ]);
        $this->assertCount(1, $reversals2);
        $this->assertEquals($reversals1[0]->id, $reversals2[0]->id);

        // Event count remains 1
        Event::assertDispatched(AuditEvent::class, 1);

        // Verify DB count
        $this->assertDatabaseCount('inventory_movements', 2); // 1 deduction + 1 reversal
    }

    /**
     * Complete lifecycle regression test:
     * Deduct -> Deduct (idempotent) -> Reverse -> Reverse (idempotent)
     */
    public function test_lifecycle_deduction_and_reversal(): void
    {
        $order = Order::factory()->create();
        $item = OrderItem::create([
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

        Event::fake([AuditEvent::class]);

        // 1. Deduct first time
        $deductions1 = $this->service->deductOrderStock($order);
        $this->assertCount(1, $deductions1);

        // 2. Deduct second time (idempotent)
        $deductions2 = $this->service->deductOrderStock($order);
        $this->assertCount(1, $deductions2);
        $this->assertEquals($deductions1[0]->id, $deductions2[0]->id);

        // 3. Reverse first time
        $reversals1 = $this->service->reverseOrderStock($order);
        $this->assertCount(1, $reversals1);

        // 4. Reverse second time (idempotent)
        $reversals2 = $this->service->reverseOrderStock($order);
        $this->assertCount(1, $reversals2);
        $this->assertEquals($reversals1[0]->id, $reversals2[0]->id);

        // Original stock balance restored
        $this->sku1->inventoryItem->refresh();
        $this->assertEquals(100, $this->sku1->inventoryItem->on_hand_quantity);

        // Exactly 2 audit events dispatched in total (1 deduction + 1 reversal)
        Event::assertDispatched(AuditEvent::class, 2);
    }

    /**
     * Test missing InventoryItem throws InventoryItemNotFoundException.
     */
    public function test_missing_inventory_item_throws_exception(): void
    {
        $skuNoInventory = ProductSku::factory()->create(['product_id' => $this->product->id]);
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
        $this->service->reverseOrderStock($order);
    }

    /**
     * Test duplicate SKUs in the same order.
     */
    public function test_duplicate_skus_in_same_order_reversed_correctly(): void
    {
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
            'sku_id' => $this->sku1->id,
            'quantity' => 10,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        // Deduct
        $this->service->deductOrderStock($order);

        // Reverse
        $reversals = $this->service->reverseOrderStock($order);
        $this->assertCount(2, $reversals);

        // Balance restored back to 100
        $this->sku1->inventoryItem->refresh();
        $this->assertEquals(100, $this->sku1->inventoryItem->on_hand_quantity);
    }
}
