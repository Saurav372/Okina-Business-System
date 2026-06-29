<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\InventoryBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InventoryMovementHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductSku $sku1;

    private ProductSku $sku2;

    private InventoryBalanceService $service;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->sku1 = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU-TEST-1',
        ]);
        $this->sku2 = ProductSku::factory()->create([
            'product_id' => $this->product->id,
            'sku_code' => 'SKU-TEST-2',
        ]);
        $this->service = new InventoryBalanceService;
        $this->adminUser = User::factory()->create();
    }

    /**
     * Test history query filters.
     */
    public function test_history_query_filters(): void
    {
        // Setup initial balances
        $this->service->setBalance($this->sku1, 50, 10);
        $this->service->setBalance($this->sku2, 100, 20);

        // Perform some movements
        // 1. Stock In for SKU 1
        $m1 = $this->service->stockIn($this->sku1, 10, InventoryMovementReason::PURCHASE_RECEIPT, [
            'vendor_order_id' => 101,
            'vendor_order_item_id' => 201,
            'purchase_stock_in_id' => 301,
            'occurred_at' => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        // 2. Stock Out for SKU 1
        $m2 = $this->service->stockOut($this->sku1, 5, InventoryMovementReason::MANUAL_ADJUSTMENT, [
            'occurred_at' => Carbon::parse('2026-06-02 12:00:00'),
        ]);

        // 3. Stock In for SKU 2
        $m3 = $this->service->stockIn($this->sku2, 20, InventoryMovementReason::PURCHASE_RECEIPT, [
            'vendor_order_id' => 102,
            'occurred_at' => Carbon::parse('2026-06-03 14:00:00'),
        ]);

        // 4. Adjust for SKU 1
        $m4 = $this->service->adjust($this->sku1, 60, 5, InventoryMovementReason::INVENTORY_CORRECTION, [
            'occurred_at' => Carbon::parse('2026-06-04 16:00:00'),
            'created_by_user_id' => $this->adminUser->id,
        ]);

        // Filter by SKU 1
        $results = $this->service->getMovementHistory(['product_sku_id' => $this->sku1->id]);
        $this->assertCount(3, $results);
        $this->assertTrue($results->contains('id', $m1->id));
        $this->assertTrue($results->contains('id', $m2->id));
        $this->assertTrue($results->contains('id', $m4->id));
        $this->assertFalse($results->contains('id', $m3->id));

        // Filter by SKU code
        $results = $this->service->getMovementHistory(['sku_code' => 'SKU-TEST-2']);
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains('id', $m3->id));

        // Filter by vendor_order_id
        $results = $this->service->getMovementHistory(['vendor_order_id' => 101]);
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains('id', $m1->id));

        // Filter by movement_type
        $results = $this->service->getMovementHistory(['movement_type' => InventoryMovementType::STOCK_OUT]);
        $this->assertCount(1, $results);
        $this->assertTrue($results->contains('id', $m2->id));

        // Filter by direction
        $results = $this->service->getMovementHistory(['direction' => InventoryDirection::IN]);
        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $m1->id));
        $this->assertTrue($results->contains('id', $m3->id));

        // Filter with empty string (should be normalized to null/ignored)
        $results = $this->service->getMovementHistory(['sku_code' => '']);
        $this->assertCount(4, $results);

        // Filter with invalid movement_type (should be ignored)
        $results = $this->service->getMovementHistory(['movement_type' => 'invalid_movement_type']);
        $this->assertCount(4, $results);

        // Filter with invalid direction (should be ignored)
        $results = $this->service->getMovementHistory(['direction' => 'invalid_direction']);
        $this->assertCount(4, $results);
    }

    /**
     * Test inclusive date boundaries and date-only normalization.
     */
    public function test_date_boundaries_inclusive_normalization(): void
    {
        $this->service->setBalance($this->sku1, 10, 0);

        $m1 = $this->service->stockIn($this->sku1, 5, InventoryMovementReason::MIGRATION, [
            'occurred_at' => Carbon::parse('2026-06-10 08:00:00'),
        ]);

        $m2 = $this->service->stockIn($this->sku1, 5, InventoryMovementReason::MIGRATION, [
            'occurred_at' => Carbon::parse('2026-06-10 23:30:00'),
        ]);

        $m3 = $this->service->stockIn($this->sku1, 5, InventoryMovementReason::MIGRATION, [
            'occurred_at' => Carbon::parse('2026-06-11 01:00:00'),
        ]);

        // Filter strictly between 2026-06-10 and 2026-06-10 (date only)
        // occurred_to should normalize to 2026-06-10 23:59:59
        $results = $this->service->getMovementHistory([
            'occurred_from' => '2026-06-10',
            'occurred_to' => '2026-06-10',
        ]);

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains('id', $m1->id));
        $this->assertTrue($results->contains('id', $m2->id));
        $this->assertFalse($results->contains('id', $m3->id));
    }

    /**
     * Test whitelisted columns and sorting stability.
     */
    public function test_sorting_stability_and_whitelist(): void
    {
        $this->service->setBalance($this->sku1, 10, 0);

        // Create 3 movements with the exact same occurred_at timestamp
        $sameTime = Carbon::parse('2026-06-15 12:00:00');
        $m1 = $this->service->stockIn($this->sku1, 5, InventoryMovementReason::MIGRATION, [
            'occurred_at' => $sameTime,
        ]);
        $m2 = $this->service->stockIn($this->sku1, 10, InventoryMovementReason::MIGRATION, [
            'occurred_at' => $sameTime,
        ]);
        $m3 = $this->service->stockIn($this->sku1, 15, InventoryMovementReason::MIGRATION, [
            'occurred_at' => $sameTime,
        ]);

        // Sorted by occurred_at desc, id desc (stability check)
        $results = $this->service->getMovementHistory([
            'sort_by' => 'occurred_at',
            'sort_direction' => 'desc',
        ]);

        $this->assertEquals($m3->id, $results[0]->id);
        $this->assertEquals($m2->id, $results[1]->id);
        $this->assertEquals($m1->id, $results[2]->id);

        // Sorted by occurred_at asc, id asc
        $results = $this->service->getMovementHistory([
            'sort_by' => 'occurred_at',
            'sort_direction' => 'asc',
        ]);

        $this->assertEquals($m1->id, $results[0]->id);
        $this->assertEquals($m2->id, $results[1]->id);
        $this->assertEquals($m3->id, $results[2]->id);

        // Fallback for non-whitelisted sort columns
        $results = $this->service->getMovementHistory([
            'sort_by' => 'notes_or_something_invalid',
            'sort_direction' => 'desc',
        ]);

        // Should fall back to occurred_at desc, id desc
        $this->assertEquals($m3->id, $results[0]->id);
    }

    /**
     * Test pagination limits are clamped correctly.
     */
    public function test_pagination_limits(): void
    {
        $this->service->setBalance($this->sku1, 10, 0);

        for ($i = 0; $i < 15; $i++) {
            $this->service->stockIn($this->sku1, 1, InventoryMovementReason::MIGRATION);
        }

        // Test normal page size
        $results = $this->service->getMovementHistory([], 5);
        $this->assertCount(5, $results);

        // Test clamping to maximum (100)
        $results = $this->service->getMovementHistory([], 500);
        $this->assertEquals(100, $results->perPage());

        // Test clamping to minimum (1)
        $results = $this->service->getMovementHistory([], 0);
        $this->assertEquals(1, $results->perPage());
    }

    /**
     * Test AuditEvent dispatches across all 5 movement types with exact payload checks.
     */
    public function test_audit_event_dispatches(): void
    {
        Event::fake([AuditEvent::class]);

        $this->service->setBalance($this->sku1, 100, 0);

        // 1. Stock In
        $mIn = $this->service->stockIn($this->sku1, 10, InventoryMovementReason::PURCHASE_RECEIPT);
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($mIn) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['movement_public_id'] === (string) $mIn->id
                && $event->payload['sku_public_id'] === $this->sku1->sku_code
                && $event->payload['movement_type'] === InventoryMovementType::STOCK_IN->value
                && $event->payload['direction'] === InventoryDirection::IN->value
                && $event->payload['reason'] === InventoryMovementReason::PURCHASE_RECEIPT->value
                && $event->payload['quantity'] === 10
                && $event->payload['before_on_hand'] === 100
                && $event->payload['after_on_hand'] === 110
                && $event->payload['before_reserved'] === 0
                && $event->payload['after_reserved'] === 0;
        });

        // 2. Stock Out
        $mOut = $this->service->stockOut($this->sku1, 5, InventoryMovementReason::MANUAL_ADJUSTMENT);
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($mOut) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['movement_public_id'] === (string) $mOut->id
                && $event->payload['sku_public_id'] === $this->sku1->sku_code
                && $event->payload['movement_type'] === InventoryMovementType::STOCK_OUT->value
                && $event->payload['direction'] === InventoryDirection::OUT->value
                && $event->payload['reason'] === InventoryMovementReason::MANUAL_ADJUSTMENT->value
                && $event->payload['quantity'] === 5
                && $event->payload['before_on_hand'] === 110
                && $event->payload['after_on_hand'] === 105
                && $event->payload['before_reserved'] === 0
                && $event->payload['after_reserved'] === 0;
        });

        // 3. Manual Adjustment
        $mAdj = $this->service->adjust($this->sku1, 80, 10, InventoryMovementReason::INVENTORY_CORRECTION);
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($mAdj) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['movement_public_id'] === (string) $mAdj->id
                && $event->payload['sku_public_id'] === $this->sku1->sku_code
                && $event->payload['movement_type'] === InventoryMovementType::MANUAL_ADJUSTMENT->value
                && $event->payload['direction'] === InventoryDirection::ADJUST->value
                && $event->payload['reason'] === InventoryMovementReason::INVENTORY_CORRECTION->value
                && $event->payload['quantity'] === 25 // max(abs(80-105), abs(10-0)) = max(25, 10) = 25
                && $event->payload['before_on_hand'] === 105
                && $event->payload['after_on_hand'] === 80
                && $event->payload['before_reserved'] === 0
                && $event->payload['after_reserved'] === 10;
        });

        // Set up Order and OrderItem for deduction
        $order = Order::factory()->create();
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku1->id,
            'quantity' => 15,
            'sku_code_snapshot' => $this->sku1->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        // 4. Order Stock Deduction
        $mDeducts = $this->service->deductOrderStock($order);
        $this->assertCount(1, $mDeducts);
        $mDed = $mDeducts[0];
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($mDed) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['movement_public_id'] === (string) $mDed->id
                && $event->payload['sku_public_id'] === $this->sku1->sku_code
                && $event->payload['movement_type'] === InventoryMovementType::ORDER_DEDUCTION->value
                && $event->payload['direction'] === InventoryDirection::OUT->value
                && $event->payload['reason'] === InventoryMovementReason::ORDER_FULFILLMENT->value
                && $event->payload['quantity'] === 15
                && $event->payload['before_on_hand'] === 80
                && $event->payload['after_on_hand'] === 65
                && $event->payload['before_reserved'] === 10
                && $event->payload['after_reserved'] === 10;
        });

        // 5. Cancellation Stock Reversal
        $mReversals = $this->service->reverseOrderStock($order);
        $this->assertCount(1, $mReversals);
        $mRev = $mReversals[0];
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($mRev) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['movement_public_id'] === (string) $mRev->id
                && $event->payload['sku_public_id'] === $this->sku1->sku_code
                && $event->payload['movement_type'] === InventoryMovementType::CANCELLATION_REVERSAL->value
                && $event->payload['direction'] === InventoryDirection::IN->value
                && $event->payload['reason'] === InventoryMovementReason::ORDER_CANCELLATION->value
                && $event->payload['quantity'] === 15
                && $event->payload['before_on_hand'] === 65
                && $event->payload['after_on_hand'] === 80
                && $event->payload['before_reserved'] === 10
                && $event->payload['after_reserved'] === 10;
        });
    }

    /**
     * Test idempotency guards prevent duplicate AuditEvent dispatches.
     */
    public function test_idempotency_duplicate_event_prevention(): void
    {
        Event::fake([AuditEvent::class]);

        $this->service->setBalance($this->sku1, 100, 0);

        // Perform stockIn with idempotency key
        $key = 'test-idempotency-key-123';
        $m1 = $this->service->stockIn($this->sku1, 10, InventoryMovementReason::PURCHASE_RECEIPT, [
            'idempotency_key' => $key,
        ]);

        Event::assertDispatched(AuditEvent::class, 1);

        // Perform second stockIn with identical idempotency key
        $m2 = $this->service->stockIn($this->sku1, 10, InventoryMovementReason::PURCHASE_RECEIPT, [
            'idempotency_key' => $key,
        ]);

        $this->assertEquals($m1->id, $m2->id);

        // Event should NOT be dispatched a second time
        Event::assertDispatched(AuditEvent::class, 1);
    }
}
