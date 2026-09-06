<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryBalanceService;
use App\Services\OrderCancellationService;
use App\Services\SalesOrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderCancellationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $salesUser;
    protected Customer $customer;
    protected ProductSku $sku;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed access permissions
        $permissions = [
            'orders.view',
            'orders.manage',
            'orders.update_status',
            'orders.cancel',
            'orders.cancel_in_production',
            'orders.cancel_ready_to_ship',
            'dashboard.access',
            'refunds.request',
        ];

        foreach ($permissions as $slug) {
            Permission::query()->updateOrCreate(['slug' => $slug], [
                'name' => ucwords(str_replace(['.', '_'], ' ', $slug)),
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => $slug,
                'is_sensitive' => false,
            ]);
        }

        // Admin role
        $adminRole = Role::query()->updateOrCreate(['slug' => 'admin'], [
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $adminRole->permissions()->sync(Permission::query()->pluck('id')->all());

        // Sales Staff role
        $salesRole = Role::query()->updateOrCreate(['slug' => 'sales_staff'], [
            'name' => 'Sales Staff',
            'guard_name' => 'web',
            'description' => 'Sales staff role',
            'is_system' => true,
            'sort_order' => 1,
        ]);
        $salesRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['orders.view', 'orders.cancel', 'dashboard.access'])->pluck('id')->all()
        );

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->sync([$adminRole->id]);

        $this->salesUser = User::factory()->create();
        $this->salesUser->roles()->sync([$salesRole->id]);

        $this->customer = Customer::factory()->create();

        $product = Product::factory()->create();
        $this->sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 100,
        ]);

        InventoryItem::query()->updateOrCreate(
            ['product_sku_id' => $this->sku->id],
            [
                'on_hand_quantity' => 100,
                'reserved_quantity' => 0,
                'available_quantity' => 100,
            ]
        );
    }

    protected function createOrder(OrderStatus $status, int $totalMinor = 200000): Order
    {
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => $status->value,
            'total_amount_minor' => $totalMinor,
            'subtotal_amount_minor' => $totalMinor,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->sku->product_id,
            'sku_id' => $this->sku->id,
            'quantity' => 10,
            'unit_price_minor' => (int) ($totalMinor / 10),
            'line_subtotal_minor' => $totalMinor,
            'line_total_minor' => $totalMinor,
            'currency' => 'INR',
            'product_name_snapshot' => 'Test T-Shirt',
            'product_slug_snapshot' => 'test-t-shirt',
            'sku_code_snapshot' => $this->sku->sku_code,
            'customization_fingerprint' => 'none',
            'customization_snapshot' => [],
        ]);

        return $order;
    }

    public function test_sales_staff_can_cancel_confirmed_order(): void
    {
        $order = $this->createOrder(OrderStatus::Confirmed);

        $response = $this->actingAs($this->salesUser)
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'reason_note' => 'Customer requested pre-production cancellation',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'previous_status' => OrderStatus::Confirmed->value,
            'reason_code' => 'customer_request',
            'cancelled_by_user_id' => $this->salesUser->id,
        ]);
    }

    public function test_sales_staff_cannot_cancel_in_production_order(): void
    {
        $order = $this->createOrder(OrderStatus::InProduction);

        $response = $this->actingAs($this->salesUser)
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'production_impact_notes' => 'Attempting cancellation',
            ]);

        $response->assertForbidden();
        $this->assertEquals(OrderStatus::InProduction->value, $order->fresh()->status);
    }

    public function test_manager_with_override_can_cancel_in_production_order(): void
    {
        $order = $this->createOrder(OrderStatus::InProduction);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'material_consumed' => true,
                'scrap_incurred' => true,
                'affected_quantity' => 10,
                'scrap_quantity' => 4,
                'scrap_amount' => 4200.50,
                'production_impact_notes' => '4 custom blanks spoiled during mid-run halt.',
            ]);

        $response->assertRedirect();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->fresh()->status);

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'previous_status' => OrderStatus::InProduction->value,
            'material_consumed' => true,
            'scrap_incurred' => true,
            'affected_quantity' => 10,
            'scrap_quantity' => 4,
            'scrap_amount_minor' => 420050,
        ]);
    }

    public function test_in_production_cancellation_requires_production_impact_notes(): void
    {
        $order = $this->createOrder(OrderStatus::InProduction);

        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                // production_impact_notes intentionally omitted
            ]);

        $response->assertSessionHasErrors('production_impact_notes');
        $this->assertEquals(OrderStatus::InProduction->value, $order->fresh()->status);
    }

    public function test_reason_other_requires_reason_note(): void
    {
        $order = $this->createOrder(OrderStatus::Confirmed);

        $response = $this->actingAs($this->salesUser)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'other',
                'reason_note' => '',
            ]);

        $response->assertSessionHasErrors('reason_note');
        $this->assertEquals(OrderStatus::Confirmed->value, $order->fresh()->status);
    }

    public function test_generic_state_machine_blocks_in_production_to_cancelled(): void
    {
        $order = $this->createOrder(OrderStatus::InProduction);

        $salesOrderService = app(SalesOrderService::class);

        $this->expectException(ValidationException::class);
        $salesOrderService->transitionStatus(
            order: $order,
            targetStatus: OrderStatus::Cancelled,
            actor: $this->adminUser
        );
    }

    public function test_order_cancellation_is_strictly_idempotent(): void
    {
        $order = $this->createOrder(OrderStatus::Confirmed);
        $service = app(OrderCancellationService::class);

        $firstResult = $service->cancel(
            order: $order,
            actor: $this->adminUser,
            reasonCode: 'customer_request',
            reasonNote: 'First cancellation attempt',
        );

        $secondResult = $service->cancel(
            order: $order,
            actor: $this->adminUser,
            reasonCode: 'customer_request',
            reasonNote: 'Second duplicate cancellation attempt',
        );

        $this->assertEquals($firstResult->id, $secondResult->id);
        $this->assertEquals(1, \App\Models\OrderCancellation::where('order_id', $order->id)->count());
    }

    public function test_ready_to_ship_cancellation_requires_interception_confirmation(): void
    {
        $order = $this->createOrder(OrderStatus::ReadyToShip);

        // Missing physical interception confirmation
        $response = $this->actingAs($this->adminUser)
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'physical_interception_confirmed' => false,
            ]);

        $response->assertSessionHasErrors('physical_interception_confirmed');
        $this->assertEquals(OrderStatus::ReadyToShip->value, $order->fresh()->status);

        // With physical interception confirmation
        $successResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'physical_interception_confirmed' => true,
            ]);

        $successResponse->assertRedirect();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->fresh()->status);
        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'physical_interception_confirmed' => true,
            'shipping_intercept_confirmed_by_user_id' => $this->adminUser->id,
        ]);
    }

    public function test_shipped_order_cancellation_is_completely_blocked(): void
    {
        $order = $this->createOrder(OrderStatus::Shipped);
        $order->update(['shipping_status' => 'shipped', 'shipped_at' => now()]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'physical_interception_confirmed' => true,
            ]);

        $response->assertForbidden();
        $this->assertEquals(OrderStatus::Shipped->value, $order->fresh()->status);
    }

    public function test_multi_payment_refund_request_allocation(): void
    {
        $order = $this->createOrder(OrderStatus::Confirmed, 5000000); // ₹50,000

        // Payment 1: ₹20,000 advance
        $payment1 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_ADVANCE,
            'provider' => 'manual',
            'method' => 'upi',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 2000000,
            'currency' => 'INR',
            'receipt_number' => 'RC-TEST-001',
            'paid_at' => now(),
        ]);

        // Payment 2: ₹25,000 balance
        $payment2 = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FINAL_BALANCE,
            'provider' => 'manual',
            'method' => 'card',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 2500000,
            'currency' => 'INR',
            'receipt_number' => 'RC-TEST-002',
            'paid_at' => now(),
        ]);

        // Prior partial refund of ₹5,000 on Payment 1
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment1->id,
            'provider' => 'manual',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 500000,
            'currency' => 'INR',
            'reason_code' => 'discount',
        ]);

        // Financial formula test:
        // Captured: 4,500,000 (₹45,000)
        // Succeeded Refunds: 500,000 (₹5,000)
        // Refundable Captured: 4,000,000 (₹40,000)
        $this->assertEquals(4500000, $order->getCapturedPaymentsAmountMinor());
        $this->assertEquals(500000, $order->getSucceededRefundsAmountMinor());
        $this->assertEquals(4000000, $order->getRefundableCapturedAmountMinor());
        $this->assertEquals(4000000, $order->getAvailableRefundRequestAmountMinor());

        // Cancel order and request refund of ₹30,000 (3,000,000 minor)
        // Should allocate ₹15,000 to Payment 1 (which had ₹15,000 remaining) and ₹15,000 to Payment 2
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.cancel', $order), [
                'reason_code' => 'customer_request',
                'create_refund_request' => true,
                'refund_amount' => 30000.00,
            ]);

        $response->assertRedirect();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->fresh()->status);

        $newRefunds = Refund::query()
            ->where('order_id', $order->id)
            ->where('status', Refund::STATUS_REQUESTED)
            ->get();

        $this->assertCount(2, $newRefunds);
        $this->assertEquals(1500000, $newRefunds->where('payment_id', $payment1->id)->first()->amount_minor);
        $this->assertEquals(1500000, $newRefunds->where('payment_id', $payment2->id)->first()->amount_minor);
    }

    public function test_bulk_cancellation_cancels_eligible_and_skips_in_production(): void
    {
        $orderPending = $this->createOrder(OrderStatus::PendingPayment);
        $orderConfirmed = $this->createOrder(OrderStatus::Confirmed);
        $orderInProd = $this->createOrder(OrderStatus::InProduction);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.bulk.cancel'), [
                'order_ids' => [
                    $orderPending->public_id,
                    $orderConfirmed->public_id,
                    $orderInProd->public_id,
                ],
                'reason_code' => 'customer_request',
                'reason_note' => 'Bulk cancellation of client test orders',
            ]);

        $response->assertRedirect();

        // Pending and Confirmed are cancelled
        $this->assertEquals(OrderStatus::Cancelled->value, $orderPending->fresh()->status);
        $this->assertEquals(OrderStatus::Cancelled->value, $orderConfirmed->fresh()->status);

        // In Production is skipped!
        $this->assertEquals(OrderStatus::InProduction->value, $orderInProd->fresh()->status);

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $orderPending->id,
            'previous_status' => OrderStatus::PendingPayment->value,
        ]);
        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $orderConfirmed->id,
            'previous_status' => OrderStatus::Confirmed->value,
        ]);
        $this->assertDatabaseMissing('order_cancellations', [
            'order_id' => $orderInProd->id,
        ]);
    }

    public function test_cancellation_accepts_boolean_strings_from_browser_form_submission(): void
    {
        $order = $this->createOrder(OrderStatus::InProduction);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.orders.cancel', ['order' => $order->public_id]), [
                'reason_code' => 'customer_request',
                'material_consumed' => 'false',
                'customization_applied' => 'false',
                'scrap_incurred' => 'false',
                'production_impact_notes' => 'Blanks were not cut yet, verified by floor manager.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertEquals(OrderStatus::Cancelled->value, $order->fresh()->status);

        $this->assertDatabaseHas('order_cancellations', [
            'order_id' => $order->id,
            'material_consumed' => false,
            'scrap_incurred' => false,
        ]);
    }
}
