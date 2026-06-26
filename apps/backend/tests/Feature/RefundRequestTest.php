<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $financeStaff;

    private User $salesStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

        $this->financeStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->financeStaff->assignRole(Role::FINANCE_STAFF);

        $this->salesStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->salesStaff->assignRole(Role::SALES_STAFF);
    }

    public function test_authorized_user_can_create_refund_request(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create(['public_id' => 'ORD-REF-101']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-101',
                'payment_id' => $payment->id,
                'amount_minor' => 4000,
                'refund_type' => Refund::TYPE_PARTIAL,
                'reason_code' => 'customer_request',
                'reason_note' => 'Partial refund request',
            ]);

        $response->assertStatus(201)
            ->assertHeader('Location', route('admin.refunds.show', $response->json('data.id')))
            ->assertJsonPath('data.amount_minor', 4000)
            ->assertJsonPath('data.status', Refund::STATUS_REQUESTED)
            ->assertJsonPath('data.order_public_id', 'ORD-REF-101')
            ->assertJsonMissingPath('data.order_id')
            ->assertJsonMissingPath('data.payment_id');

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount_minor' => 4000,
            'status' => Refund::STATUS_REQUESTED,
        ]);

        Event::assertDispatched(AuditEvent::class, function ($event) use ($payment, $order) {
            return $event->key === 'refunds.refund_requested' &&
                $event->payload['payment_id'] === $payment->id &&
                $event->payload['order_public_id'] === $order->public_id &&
                $event->payload['amount_minor'] === 4000 &&
                $event->payload['remaining_refundable_amount_before_request'] === 10000 &&
                $event->payload['remaining_after_request'] === 6000;
        });
    }

    public function test_unauthorized_user_is_blocked_from_requesting_refund(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-102']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->salesStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-102',
                'payment_id' => $payment->id,
                'amount_minor' => 4000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ]);

        $response->assertStatus(403);
    }

    public function test_validation_fails_if_payment_belongs_to_another_order(): void
    {
        $order1 = Order::factory()->create(['public_id' => 'ORD-1']);
        $order2 = Order::factory()->create(['public_id' => 'ORD-2']);

        $payment = Payment::create([
            'order_id' => $order1->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-2',
                'payment_id' => $payment->id,
                'amount_minor' => 4000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_id']);
    }

    public function test_validation_fails_if_payment_is_not_succeeded(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-103']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_FAILED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-103',
                'payment_id' => $payment->id,
                'amount_minor' => 4000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_id']);
    }

    public function test_validation_fails_if_refund_amount_exceeds_payment_amount(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-104']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-104',
                'payment_id' => $payment->id,
                'amount_minor' => 12000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_minor']);
    }

    public function test_transaction_fails_if_refund_exceeds_remaining_refundable_balance(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-105']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // Create an existing partial refund
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 7000,
            'currency' => 'INR',
        ]);

        // Attempting to request 4000 refund, exceeding remaining 3000
        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-105',
                'payment_id' => $payment->id,
                'amount_minor' => 4000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_minor']);
    }

    public function test_full_refund_type_requires_exact_remaining_amount(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-106']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // Type full but requesting 5000 should fail
        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-106',
                'payment_id' => $payment->id,
                'amount_minor' => 5000,
                'refund_type' => Refund::TYPE_FULL,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_minor']);
    }

    public function test_two_partial_refunds_can_equal_payment_amount_then_fails_on_zero_remaining(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-107']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // First request of 6000
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-107',
                'payment_id' => $payment->id,
                'amount_minor' => 6000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ])
            ->assertStatus(201);

        // Second request of 4000 (exactly matches remaining 4000)
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-107',
                'payment_id' => $payment->id,
                'amount_minor' => 4000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ])
            ->assertStatus(201);

        // Third request of any amount fails because remaining balance is exactly zero
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-107',
                'payment_id' => $payment->id,
                'amount_minor' => 100,
                'refund_type' => Refund::TYPE_PARTIAL,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['amount_minor']);
    }

    public function test_reserves_balance_calculations_exclude_failed_and_cancelled_refunds(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-108']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // 1. Requested refund
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        // 2. Approved refund
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_APPROVED,
            'amount_minor' => 1000,
            'currency' => 'INR',
        ]);

        // 3. Failed refund (should be excluded)
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_FAILED,
            'amount_minor' => 500,
            'currency' => 'INR',
        ]);

        // 4. Cancelled refund (should be excluded)
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_CANCELLED,
            'amount_minor' => 500,
            'currency' => 'INR',
        ]);

        // Remaining balance should be: 10000 - 2000 - 1000 = 7000.
        // We request 7000 as partial, it should succeed.
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-108',
                'payment_id' => $payment->id,
                'amount_minor' => 7000,
                'refund_type' => Refund::TYPE_PARTIAL,
            ])
            ->assertStatus(201);
    }

    public function test_terminal_refunds_fully_release_reserved_balance(): void
    {
        $order = Order::factory()->create(['public_id' => 'ORD-REF-109']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // Failed refund
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_FAILED,
            'amount_minor' => 3000,
            'currency' => 'INR',
        ]);

        // Cancelled refund
        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_CANCELLED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        // Remaining balance is still 10000. Requesting 10000 should succeed.
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => 'ORD-REF-109',
                'payment_id' => $payment->id,
                'amount_minor' => 10000,
                'refund_type' => Refund::TYPE_FULL,
            ])
            ->assertStatus(201);
    }
}
