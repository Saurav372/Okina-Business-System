<?php

namespace Tests\Feature;

use App\Enums\InventoryDirection;
use App\Enums\InventoryMovementReason;
use App\Enums\InventoryMovementType;
use App\Events\AuditEvent;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\InventoryBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryManualAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductSku $sku;

    private InventoryBalanceService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create();
        $this->sku = ProductSku::factory()->create(['product_id' => $this->product->id]);
        $this->service = new InventoryBalanceService;
        $this->user = User::factory()->create();

        // Initialize SKU to 50 on hand, 10 reserved
        $this->service->setBalance($this->sku, 50, 10);
    }

    /**
     * Test absolute on-hand only adjustment.
     */
    public function test_valid_on_hand_only_adjustment(): void
    {
        Event::fake([AuditEvent::class]);

        // Adjust on-hand from 50 to 70. Reserved remains 10.
        // Quantity = max(|70 - 50|, |10 - 10|) = 20
        $movement = $this->service->adjust($this->sku, 70, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, [
            'notes' => 'On-hand adjustment notes',
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(InventoryMovement::class, $movement);

        // Assert balances
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 70,
            'reserved_quantity' => 10,
            'available_quantity' => 60, // 70 - 10
        ]);

        $this->sku->refresh();
        $this->assertEquals(60, $this->sku->stock_quantity);

        // Assert movement trace
        $this->assertDatabaseHas('inventory_movements', [
            'id' => $movement->id,
            'product_sku_id' => $this->sku->id,
            'quantity' => 20,
            'movement_type' => InventoryMovementType::MANUAL_ADJUSTMENT->value,
            'direction' => InventoryDirection::ADJUST->value,
            'before_on_hand_quantity' => 50,
            'after_on_hand_quantity' => 70,
            'before_reserved_quantity' => 10,
            'after_reserved_quantity' => 10,
            'before_available_quantity' => 40,
            'after_available_quantity' => 60,
            'reason_code' => InventoryMovementReason::MANUAL_ADJUSTMENT->value,
            'created_by_user_id' => $this->user->id,
            'notes' => 'On-hand adjustment notes',
        ]);

        // Assert AuditEvent
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($movement) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['movement_public_id'] === (string) $movement->id
                && $event->payload['sku_public_id'] === $this->sku->sku_code
                && $event->payload['movement_type'] === InventoryMovementType::MANUAL_ADJUSTMENT->value
                && $event->payload['reason'] === InventoryMovementReason::MANUAL_ADJUSTMENT->value
                && $event->payload['quantity'] === 20
                && $event->payload['before_on_hand'] === 50
                && $event->payload['after_on_hand'] === 70
                && $event->payload['before_reserved'] === 10
                && $event->payload['after_reserved'] === 10
                && $event->payload['actor_user_id'] === $this->user->id;
        });
    }

    /**
     * Test absolute reserved only adjustment.
     */
    public function test_valid_reserved_only_adjustment(): void
    {
        Event::fake([AuditEvent::class]);

        // Adjust reserved from 10 to 15. On-hand remains 50.
        // Quantity = max(|50 - 50|, |15 - 10|) = 5
        $movement = $this->service->adjust($this->sku, 50, 15, InventoryMovementReason::WAREHOUSE_ADJUSTMENT, [
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(InventoryMovement::class, $movement);

        // Assert balances
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 50,
            'reserved_quantity' => 15,
            'available_quantity' => 35, // 50 - 15
        ]);

        $this->sku->refresh();
        $this->assertEquals(35, $this->sku->stock_quantity);

        // Assert AuditEvent
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['quantity'] === 5
                && $event->payload['before_reserved'] === 10
                && $event->payload['after_reserved'] === 15
                && $event->payload['before_on_hand'] === 50
                && $event->payload['after_on_hand'] === 50;
        });
    }

    /**
     * Test both on-hand and reserved adjustment.
     */
    public function test_valid_on_hand_and_reserved_adjustment(): void
    {
        Event::fake([AuditEvent::class]);

        // Adjust on-hand 50 -> 60 (+10) and reserved 10 -> 8 (-2)
        // Quantity = max(|10|, |-2|) = 10
        $movement = $this->service->adjust($this->sku, 60, 8, InventoryMovementReason::MANUAL_ADJUSTMENT, [
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(InventoryMovement::class, $movement);

        // Assert balances: available = 60 - 8 = 52
        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 60,
            'reserved_quantity' => 8,
            'available_quantity' => 52,
        ]);

        // Assert AuditEvent
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['quantity'] === 10
                && $event->payload['before_on_hand'] === 50
                && $event->payload['after_on_hand'] === 60
                && $event->payload['before_reserved'] === 10
                && $event->payload['after_reserved'] === 8;
        });
    }

    /**
     * Test decreasing balances (negative delta delta, e.g. 50 -> 20).
     */
    public function test_decreasing_balances_adjustment(): void
    {
        Event::fake([AuditEvent::class]);

        // Adjust on-hand 50 -> 20 (-30)
        // Quantity = max(|-30|, |0|) = 30
        $movement = $this->service->adjust($this->sku, 20, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, [
            'created_by_user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(InventoryMovement::class, $movement);

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 20,
            'reserved_quantity' => 10,
            'available_quantity' => 10, // 20 - 10
        ]);

        // Assert AuditEvent
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'inventory.stock_moved'
                && $event->payload['quantity'] === 30
                && $event->payload['before_on_hand'] === 50
                && $event->payload['after_on_hand'] === 20;
        });
    }

    /**
     * Test exception is thrown if no balances change.
     */
    public function test_exception_thrown_when_no_balances_change(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Manual adjustment must change either the on-hand or reserved balance.');

        $this->service->adjust($this->sku, 50, 10, InventoryMovementReason::MANUAL_ADJUSTMENT);
    }

    /**
     * Test transactional rollback on failure.
     */
    public function test_transactional_rollback_on_failure(): void
    {
        $initialAvailable = $this->sku->stock_quantity; // 40 available

        try {
            $this->service->adjust($this->sku, 60, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, [
                'occurred_at' => 'invalid-timestamp',
            ]);
            $this->fail('Expected Throwable.');
        } catch (\Throwable $e) {
            // Success
        }

        // Verify balance is unchanged
        $this->sku->refresh();
        $this->assertEquals($initialAvailable, $this->sku->stock_quantity);

        $this->assertDatabaseHas('inventory_items', [
            'product_sku_id' => $this->sku->id,
            'on_hand_quantity' => 50,
            'reserved_quantity' => 10,
        ]);
    }

    /**
     * Test idempotency key prevents duplicate adjustments and duplicate audit events.
     */
    public function test_idempotency_prevents_duplicate_adjustments_and_audit_events(): void
    {
        Event::fake([AuditEvent::class]);

        $options = [
            'idempotency_key' => 'idemp-adj-999',
            'created_by_user_id' => $this->user->id,
        ];

        $movement1 = $this->service->adjust($this->sku, 60, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, $options);
        $movement2 = $this->service->adjust($this->sku, 60, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, $options);

        $this->assertEquals($movement1->id, $movement2->id);

        $this->assertDatabaseCount('inventory_movements', 1);

        // Assert Event dispatched exactly once
        Event::assertDispatched(AuditEvent::class, 1);
    }

    /**
     * Test actor override from Auth::id() vs options.
     */
    public function test_actor_override_resolves_correct_created_by(): void
    {
        // 1. Without Auth, custom user passed in options
        $movement = $this->service->adjust($this->sku, 60, 10, InventoryMovementReason::MANUAL_ADJUSTMENT, [
            'created_by_user_id' => $this->user->id,
        ]);
        $this->assertEquals($this->user->id, $movement->created_by_user_id);

        // 2. With Auth, no override
        Auth::login($this->user);
        $movement2 = $this->service->adjust($this->sku, 70, 10, InventoryMovementReason::MANUAL_ADJUSTMENT);
        $this->assertEquals($this->user->id, $movement2->created_by_user_id);
        Auth::logout();
    }
}
