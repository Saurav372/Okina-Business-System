<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ManualPaymentRecordingTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create recording permission
        Permission::query()->updateOrCreate(
            ['slug' => 'payments.record'],
            [
                'name' => 'Record Payments',
                'group' => 'payments',
                'guard_name' => 'web',
                'description' => 'Can record manual payments',
                'is_sensitive' => true,
            ]
        );

        $role = Role::query()->updateOrCreate(
            ['slug' => 'finance_staff'],
            [
                'name' => 'Finance Staff',
                'guard_name' => 'web',
                'description' => 'Finance staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $role->permissions()->sync(
            Permission::query()->whereIn('slug', ['payments.record'])->pluck('id')->all()
        );

        $dashboardRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $this->authorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->authorizedUser->assignRole($role);
        $this->authorizedUser->assignRole($dashboardRole);

        $this->unauthorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedUser->assignRole($dashboardRole);
    }

    public function test_authorized_user_can_record_manual_payment(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 4000,
                'method' => Payment::METHOD_UPI,
                'payment_type' => Payment::TYPE_ADVANCE,
                'notes' => 'First advance payment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.payment_status', 'partially_paid');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount_minor' => 4000,
            'provider' => 'manual',
            'method' => Payment::METHOD_UPI,
            'payment_type' => Payment::TYPE_ADVANCE,
            'status' => 'succeeded',
            'recorded_by_user_id' => $this->authorizedUser->id,
            'verified_by_user_id' => null,
            'notes' => 'First advance payment',
        ]);

        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($payment->receipt_number);
        $this->assertStringStartsWith('RC-', $payment->receipt_number);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($order, $payment) {
            return $event->key === 'payments.payment_recorded'
                && $event->payload['order_public_id'] === $order->public_id
                && $event->payload['payment_public_id'] === $payment->id
                && $event->payload['amount_minor'] === 4000
                && $event->payload['payment_type'] === Payment::TYPE_ADVANCE
                && $event->payload['method'] === Payment::METHOD_UPI
                && $event->payload['recorded_by_user_id'] === $this->authorizedUser->id;
        });
    }

    public function test_unauthorized_user_cannot_record_manual_payment(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
        ]);

        $response = $this->actingAs($this->unauthorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 4000,
                'method' => Payment::METHOD_CASH,
            ]);

        $response->assertStatus(403);
    }

    public function test_manual_payment_does_not_auto_verify_payment(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 5000,
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 2000,
                'method' => Payment::METHOD_CASH,
            ]);

        $response->assertStatus(201);
        $payment = Payment::query()->where('order_id', $order->id)->first();
        $this->assertNull($payment->verified_by_user_id);
    }

    public function test_cannot_record_payment_exceeding_balance(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
        ]);

        // Record a payment of 6000
        Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 6000,
            'status' => 'succeeded',
            'payment_type' => Payment::TYPE_ADVANCE,
            'provider' => 'manual',
            'method' => Payment::METHOD_BANK_TRANSFER,
            'currency' => 'INR',
            'receipt_number' => 'RC-TEST-123',
            'paid_at' => now(),
        ]);

        // Attempting to pay 5000 (exceeds 4000 remaining) should be rejected
        $response = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 5000,
                'method' => Payment::METHOD_BANK_TRANSFER,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_minor']);
    }

    public function test_zero_remaining_balance_cannot_accept_payment(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount_minor' => 10000,
            'status' => 'succeeded',
            'payment_type' => Payment::TYPE_ADVANCE,
            'provider' => 'manual',
            'method' => Payment::METHOD_BANK_TRANSFER,
            'currency' => 'INR',
            'receipt_number' => 'RC-TEST-456',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 1,
                'method' => Payment::METHOD_CASH,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount_minor']);
    }

    public function test_multiple_partial_payments_update_status_correctly(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'confirmed',
        ]);

        // First partial payment
        $response1 = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 3000,
                'method' => Payment::METHOD_CASH,
                'payment_type' => Payment::TYPE_PARTIAL,
            ]);

        $response1->assertStatus(201)
            ->assertJsonPath('order.payment_status', 'partially_paid');

        // Second partial payment (completes order amount)
        $response2 = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 7000,
                'method' => Payment::METHOD_BANK_TRANSFER,
                'payment_type' => Payment::TYPE_FINAL_BALANCE,
            ]);

        $response2->assertStatus(201)
            ->assertJsonPath('order.payment_status', 'paid');
    }

    public function test_idempotency_key_prevents_duplicate_payments(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
        ]);

        $idempotencyKey = 'payment-idem-12345';

        // First request
        $response1 = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 2000,
                'method' => Payment::METHOD_UPI,
                'idempotency_key' => $idempotencyKey,
            ]);

        $response1->assertStatus(201);
        $paymentId = $response1->json('payment.id');

        // Second request with same idempotency key
        $response2 = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $order->public_id), [
                'amount_minor' => 2000,
                'method' => Payment::METHOD_UPI,
                'idempotency_key' => $idempotencyKey,
            ]);

        $response2->assertStatus(200)
            ->assertJsonPath('message', 'Payment recorded successfully (idempotent).')
            ->assertJsonPath('payment.id', $paymentId);

        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_cannot_record_payment_on_cancelled_or_refunded_orders(): void
    {
        $cancelledOrder = Order::factory()->create([
            'total_amount_minor' => 5000,
            'status' => 'cancelled',
        ]);

        $response1 = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $cancelledOrder->public_id), [
                'amount_minor' => 1000,
                'method' => Payment::METHOD_CASH,
            ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['order']);

        $refundedOrder = Order::factory()->create([
            'total_amount_minor' => 5000,
            'status' => 'refunded',
        ]);

        $response2 = $this->actingAs($this->authorizedUser)
            ->postJson(route('admin.orders.payments.record', $refundedOrder->public_id), [
                'amount_minor' => 1000,
                'method' => Payment::METHOD_CASH,
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }
}
