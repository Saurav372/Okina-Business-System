<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookLog;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array<string, string|bool|null>>
     */
    public static function refundWebhookStateMatrixProvider(): array
    {
        return [
            // Valid transition
            'successful_failure_transition' => [
                'initial_status' => Refund::STATUS_PROCESSING,
                'incoming_event' => 'refund_failed',
                'expected_status' => Refund::STATUS_FAILED,
                'expected_audit' => 'refunds.refund_processing_failed',
                'expected_log_status' => 'processed',
                'expected_error' => null,
                'expect_mutated' => true,
            ],
            // Duplicate replay
            'duplicate_failed_webhook' => [
                'initial_status' => Refund::STATUS_FAILED,
                'incoming_event' => 'refund_failed',
                'expected_status' => Refund::STATUS_FAILED,
                'expected_audit' => null,
                'expected_log_status' => 'ignored_duplicate',
                'expected_error' => null,
                'expect_mutated' => false,
            ],
            'duplicate_success_webhook' => [
                'initial_status' => Refund::STATUS_SUCCEEDED,
                'incoming_event' => 'refund_succeeded',
                'expected_status' => Refund::STATUS_SUCCEEDED,
                'expected_audit' => null,
                'expected_log_status' => 'ignored_duplicate',
                'expected_error' => null,
                'expect_mutated' => false,
            ],
            // Regression protection
            'late_failed_webhook' => [
                'initial_status' => Refund::STATUS_SUCCEEDED,
                'incoming_event' => 'refund_failed',
                'expected_status' => Refund::STATUS_SUCCEEDED,
                'expected_audit' => null,
                'expected_log_status' => 'processed',
                'expected_error' => 'invalid_state_transition',
                'expect_mutated' => false,
            ],
            'cancelled_regression' => [
                'initial_status' => Refund::STATUS_CANCELLED,
                'incoming_event' => 'refund_failed',
                'expected_status' => Refund::STATUS_CANCELLED,
                'expected_audit' => null,
                'expected_log_status' => 'processed',
                'expected_error' => 'invalid_state_transition',
                'expect_mutated' => false,
            ],
            'failed_to_success_regression' => [
                'initial_status' => Refund::STATUS_FAILED,
                'incoming_event' => 'refund_succeeded',
                'expected_status' => Refund::STATUS_FAILED,
                'expected_audit' => null,
                'expected_log_status' => 'processed',
                'expected_error' => 'invalid_state_transition',
                'expect_mutated' => false,
            ],
            'approved_to_failed_regression' => [
                'initial_status' => Refund::STATUS_APPROVED,
                'incoming_event' => 'refund_failed',
                'expected_status' => Refund::STATUS_APPROVED,
                'expected_audit' => null,
                'expected_log_status' => 'processed',
                'expected_error' => 'invalid_state_transition',
                'expect_mutated' => false,
            ],
        ];
    }

    #[DataProvider('refundWebhookStateMatrixProvider')]
    public function test_refund_webhook_state_matrix(
        string $initial_status,
        string $incoming_event,
        string $expected_status,
        ?string $expected_audit,
        string $expected_log_status,
        ?string $expected_error,
        bool $expect_mutated
    ): void {
        $initialStatus = $initial_status;
        $incomingEvent = $incoming_event;
        $expectedStatus = $expected_status;
        $expectedAudit = $expected_audit;
        $expectedLogStatus = $expected_log_status;
        $expectedError = $expected_error;
        $expectMutated = $expect_mutated;

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
            'gateway_order_id' => $order->public_id,
            'gateway_payment_id' => 'pay_123',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'provider_payment_id' => 'pay_123',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => $initialStatus,
            'amount_minor' => 3000,
            'currency' => 'INR',
            'provider_refund_id' => 'ref_123',
            'provider_payment_id' => 'pay_123',
        ]);

        if ($initialStatus === Refund::STATUS_FAILED) {
            $refund->reason_code = 'gateway_failure';
            $refund->reason_note = 'Original failure reason';
            $refund->save();
        }

        $refund->refresh();
        $originalUpdatedAt = $refund->updated_at?->toIso8601String();
        $originalProcessedAt = $refund->processed_at?->toIso8601String();

        $payload = [
            'event_id' => 'evt_'.uniqid(),
            'event_type' => $incomingEvent === 'refund_failed' ? 'REFUND_FAILED' : 'REFUND_SUCCESS',
            'order_id' => $order->public_id,
            'payment_id' => 'pay_123',
            'refund_id' => 'ref_123',
            'status' => $incomingEvent === 'refund_failed' ? 'FAILED' : 'SUCCESS',
            'amount_minor' => 3000,
            'currency' => 'INR',
            'received_at' => now()->toIso8601String(),
            'payload_summary' => [
                'amount_minor' => 3000,
                'currency' => 'INR',
                'status' => $incomingEvent === 'refund_failed' ? 'failed' : 'succeeded',
                'reason_code' => 'gateway_failure',
                'reason_note' => 'Failed via gateway webhook',
            ],
        ];

        // Generate signature
        $parts = [
            'cashfree',
            $payload['event_id'],
            $payload['event_type'],
            $payload['order_id'],
            $payload['payment_id'],
            $payload['refund_id'],
            $payload['status'],
            (string) $payload['amount_minor'],
            $payload['currency'],
        ];
        $signature = hash_hmac('sha256', implode('|', $parts), config('app.key'));

        $response = $this->postJson('/api/webhooks/payments/cashfree', $payload, [
            'X-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        $refund->refresh();
        $this->assertSame($expectedStatus, $refund->status);

        if (! $expectMutated) {
            $this->assertSame($originalUpdatedAt, $refund->updated_at?->toIso8601String());
            $this->assertSame($originalProcessedAt, $refund->processed_at?->toIso8601String());
            if ($initialStatus === Refund::STATUS_FAILED) {
                $this->assertSame('gateway_failure', $refund->reason_code);
                $this->assertSame('Original failure reason', $refund->reason_note);
            }
        }

        if ($expectedAudit !== null) {
            Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($expectedAudit, $refund, $payment) {
                $this->assertSame($expectedAudit, $event->key);
                $this->assertSame($refund->id, $event->payload['refund_public_id']);
                $this->assertSame($payment->id, $event->payload['payment_public_id']);
                $this->assertNotNull($event->payload['old_status']);
                $this->assertNotNull($event->payload['new_status']);
                $this->assertSame('system', $event->payload['actor_type']);

                $occurredAt = $event->payload['occurred_at'];
                $this->assertIsString($occurredAt);

                // Assert it's a valid ATOM or ISO-8601 timestamp
                $parsed = Carbon::parse($occurredAt);
                $this->assertNotNull($parsed);

                return true;
            });
        } else {
            Event::assertNotDispatched(AuditEvent::class);
        }

        $webhookLog = PaymentWebhookLog::where('provider_event_id', $payload['event_id'])->firstOrFail();
        $this->assertSame($expectedLogStatus, $webhookLog->processing_status);
        $this->assertSame($expectedError, $webhookLog->error_message);
    }

    public function test_unknown_refund_webhook_returns_http_200_and_needs_review(): void
    {
        Event::fake([AuditEvent::class]);

        $payload = [
            'event_id' => 'evt_unknown_ref',
            'event_type' => 'REFUND_FAILED',
            'order_id' => 'ORD-12345',
            'payment_id' => 'pay_unknown',
            'refund_id' => 'ref_nonexistent',
            'status' => 'FAILED',
            'amount_minor' => 3000,
            'currency' => 'INR',
            'received_at' => now()->toIso8601String(),
            'payload_summary' => [
                'amount_minor' => 3000,
                'currency' => 'INR',
                'status' => 'failed',
                'reason_code' => 'gateway_failure',
                'reason_note' => 'Failed via gateway webhook',
            ],
        ];

        // Generate signature
        $parts = [
            'cashfree',
            $payload['event_id'],
            $payload['event_type'],
            $payload['order_id'],
            $payload['payment_id'],
            $payload['refund_id'],
            $payload['status'],
            (string) $payload['amount_minor'],
            $payload['currency'],
        ];
        $signature = hash_hmac('sha256', implode('|', $parts), config('app.key'));

        $response = $this->postJson('/api/webhooks/payments/cashfree', $payload, [
            'X-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.error_message', 'refund_record_unmatched');
        $response->assertJsonPath('data.processing_status', 'needs_review');

        Event::assertNotDispatched(AuditEvent::class);

        $webhookLog = PaymentWebhookLog::where('provider_event_id', $payload['event_id'])->firstOrFail();
        $this->assertSame('needs_review', $webhookLog->processing_status);
        $this->assertSame('refund_record_unmatched', $webhookLog->error_message);
    }
}
