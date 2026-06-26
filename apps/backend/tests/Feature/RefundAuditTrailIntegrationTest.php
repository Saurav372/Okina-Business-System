<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RefundAuditTrailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $financeStaff;

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
    }

    public function test_refund_lifecycle_audit_events_are_dispatched(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // 1. Request Refund
        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => $order->public_id,
                'payment_id' => $payment->id,
                'refund_type' => Refund::TYPE_PARTIAL,
                'amount_minor' => 3000,
                'currency' => 'INR',
                'reason_code' => 'customer_request',
                'reason_note' => 'Customer requested a partial refund',
            ])
            ->assertStatus(201);

        $refundId = $response->json('data.id');
        $refund = Refund::findOrFail($refundId);

        Event::assertDispatched(function (AuditEvent $event) use ($refund) {
            return $event->key === 'refunds.refund_requested' &&
                $event->payload['refund_public_id'] === $refund->id;
        });

        // 2. Approve Refund
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.approve', $refund->id))
            ->assertOk();

        Event::assertDispatched(function (AuditEvent $event) use ($refund) {
            return $event->key === 'refunds.refund_approved' &&
                $event->payload['refund_public_id'] === $refund->id;
        });

        // 3. Start Processing Refund
        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.process', $refund->id))
            ->assertOk();

        Event::assertDispatched(function (AuditEvent $event) use ($refund) {
            return $event->key === 'refunds.refund_processing_started' &&
                $event->payload['refund_public_id'] === $refund->id;
        });

        // 4. Cancel Refund (Wait, a processing refund cannot be cancelled. Let's create another requested/approved refund to test cancellation)
        $responseCancel = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.store'), [
                'order_public_id' => $order->public_id,
                'payment_id' => $payment->id,
                'refund_type' => Refund::TYPE_PARTIAL,
                'amount_minor' => 2000,
                'currency' => 'INR',
                'reason_code' => 'customer_request',
                'reason_note' => 'Second refund',
            ])
            ->assertStatus(201);

        $refundToCancel = Refund::findOrFail($responseCancel->json('data.id'));

        $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.cancel', $refundToCancel->id))
            ->assertOk();

        Event::assertDispatched(function (AuditEvent $event) use ($refundToCancel) {
            return $event->key === 'refunds.refund_cancelled' &&
                $event->payload['refund_public_id'] === $refundToCancel->id;
        });
    }
}
