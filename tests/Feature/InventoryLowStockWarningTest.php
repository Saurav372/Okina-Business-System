<?php

namespace Tests\Feature;

use App\Enums\InventoryMovementReason;
use App\Events\LowStockDetected;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Services\InventoryBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InventoryLowStockWarningTest extends TestCase
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
            'sku_code' => 'SKU-001',
            'low_stock_threshold' => 10, // Default SKU threshold
        ]);
        $this->service = new InventoryBalanceService;

        // Initialize SKU: 20 on hand
        $this->service->setBalance($this->sku, 20, 0);
    }

    /**
     * Test threshold resolution and basic crossing.
     */
    public function test_threshold_resolution_and_basic_crossing(): void
    {
        Event::fake([LowStockDetected::class]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Low stock detected.', \Mockery::on(function ($context) {
                return $context['sku_code'] === 'SKU-001'
                    && $context['available_quantity'] === 10
                    && $context['threshold'] === 10;
            }));

        // Drops 20 -> 10 (dispatches event)
        $this->service->stockOut($this->sku, 10, InventoryMovementReason::ORDER_FULFILLMENT);

        Event::assertDispatched(LowStockDetected::class, 1);
        Event::assertDispatched(LowStockDetected::class, function (LowStockDetected $event) {
            return $event->sku->id === $this->sku->id
                && $event->availableQuantity === 10
                && $event->threshold === 10
                && $event->movement->quantity === 10;
        });
    }

    /**
     * Test crossing override via InventoryItem override.
     */
    public function test_threshold_resolution_override(): void
    {
        Event::fake([LowStockDetected::class]);
        Log::shouldReceive('warning')
            ->once()
            ->with('Low stock detected.', \Mockery::on(function ($context) {
                return $context['sku_code'] === 'SKU-001'
                    && $context['available_quantity'] === 15
                    && $context['threshold'] === 15;
            }));

        // Set InventoryItem override threshold to 15
        $this->sku->inventoryItem->update(['low_stock_threshold' => 15]);

        // Drops 20 -> 15 (dispatches event because of override threshold 15)
        $this->service->stockOut($this->sku, 5, InventoryMovementReason::ORDER_FULFILLMENT);

        Event::assertDispatched(LowStockDetected::class, 1);
    }

    /**
     * Test multiple crossings in a sequence:
     * 20 -> 10 (alert) -> 9 (no alert) -> 15 (no alert) -> 10 (alert again).
     */
    public function test_multiple_crossings_in_sequence(): void
    {
        Event::fake([LowStockDetected::class]);

        // Mock Log to expect exactly two warning calls (for the two crossings at 10)
        Log::shouldReceive('warning')
            ->twice()
            ->with('Low stock detected.', \Mockery::any());

        // 1. Drops 20 -> 10 (crosses threshold -> alert)
        $this->service->stockOut($this->sku, 10, InventoryMovementReason::ORDER_FULFILLMENT);
        Event::assertDispatched(LowStockDetected::class, 1);

        // 2. Drops 10 -> 9 (already below/equal -> no alert)
        $this->service->stockOut($this->sku, 1, InventoryMovementReason::ORDER_FULFILLMENT);
        Event::assertDispatched(LowStockDetected::class, 1); // still 1 total dispatch

        // 3. Rises 9 -> 15 (goes above -> no alert)
        $this->service->stockIn($this->sku, 6, InventoryMovementReason::PURCHASE_RECEIPT);
        Event::assertDispatched(LowStockDetected::class, 1); // still 1 total dispatch

        // 4. Drops 15 -> 10 (crosses threshold again -> alert)
        $this->service->stockOut($this->sku, 5, InventoryMovementReason::ORDER_FULFILLMENT);
        Event::assertDispatched(LowStockDetected::class, 2); // 2 total dispatches now
    }

    /**
     * Test no threshold configured.
     */
    public function test_no_threshold_configured(): void
    {
        Event::fake([LowStockDetected::class]);
        Log::shouldReceive('warning')->never();

        // Remove thresholds
        $this->sku->update(['low_stock_threshold' => null]);
        $this->sku->inventoryItem->update(['low_stock_threshold' => null]);

        // Drops 20 -> 5
        $this->service->stockOut($this->sku, 15, InventoryMovementReason::ORDER_FULFILLMENT);

        Event::assertNotDispatched(LowStockDetected::class);
    }

    /**
     * Test crossing behavior across different movement types: manual adjustment.
     */
    public function test_crossing_via_manual_adjustment(): void
    {
        Event::fake([LowStockDetected::class]);
        Log::shouldReceive('warning')->once();

        // Adjust on-hand from 20 to 8 (crosses threshold 10 -> alert)
        $this->service->adjust($this->sku, 8, 0, InventoryMovementReason::MANUAL_ADJUSTMENT);

        Event::assertDispatched(LowStockDetected::class, 1);
    }

    /**
     * Test crossing behavior across different movement types: order stock deduction.
     */
    public function test_crossing_via_order_stock_deduction(): void
    {
        Event::fake([LowStockDetected::class]);
        Log::shouldReceive('warning')->once();

        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'sku_id' => $this->sku->id,
            'quantity' => 12, // drops 20 -> 8
            'sku_code_snapshot' => $this->sku->sku_code,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [],
        ]);

        $this->service->deductOrderStock($order);

        Event::assertDispatched(LowStockDetected::class, 1);
    }
}
