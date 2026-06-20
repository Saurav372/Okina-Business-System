<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookLog;
use App\Models\Refund;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashfree_webhook_records_successful_payment_and_recalculates_status(): void
    {
        [$order, $attempt, $payload] = $this->createPendingCheckoutWebhookContext();

        $response = $this->postWebhook($payload, $this->signatureFor($payload));

        $response->assertOk()
            ->assertJsonPath('data.processing_status', 'processed')
            ->assertJsonPath('data.signature_verified', true)
            ->assertJsonPath('data.order_public_id', $order->public_id)
            ->assertJsonPath('data.payment_attempt_public_id', $attempt->public_id)
            ->assertJsonPath('data.payment_recorded', true)
            ->assertJsonPath('data.refund_recorded', false)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.payment_attempt_status', 'succeeded')
            ->assertJsonPath('data.gateway_order_id', $attempt->gateway_order_id)
            ->assertJsonPath('data.gateway_payment_id', 'cf_pay_1001');

        $this->assertSame('succeeded', $attempt->refresh()->status);
        $this->assertSame('cf_pay_1001', $attempt->gateway_payment_id);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_webhook_logs', 1);

        $payment = Payment::query()->firstOrFail();
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame($attempt->id, $payment->payment_attempt_id);
        $this->assertSame('cashfree', $payment->provider);
        $this->assertSame('full', $payment->payment_type);
        $this->assertSame('succeeded', $payment->status);
        $this->assertSame(3798, $payment->amount_minor);
        $this->assertSame('INR', $payment->currency);
        $this->assertSame('cf_pay_1001', $payment->provider_payment_id);
        $this->assertSame($attempt->gateway_order_id, $payment->provider_order_id);

        $log = PaymentWebhookLog::query()->firstOrFail();
        $this->assertSame('processed', $log->processing_status);
        $this->assertTrue($log->signature_verified);
        $this->assertSame($attempt->id, $log->payment_attempt_id);
        $this->assertSame($payment->id, $log->payment_id);
    }

    public function test_cashfree_webhook_ignores_duplicate_event_ids(): void
    {
        [$order, $attempt, $payload] = $this->createPendingCheckoutWebhookContext();

        $this->postWebhook($payload, $this->signatureFor($payload))->assertOk();
        $duplicateResponse = $this->postWebhook($payload, $this->signatureFor($payload));

        $duplicateResponse->assertOk()
            ->assertJsonPath('data.processing_status', 'ignored_duplicate')
            ->assertJsonPath('data.signature_verified', true)
            ->assertJsonPath('data.order_public_id', $order->public_id)
            ->assertJsonPath('data.payment_attempt_public_id', $attempt->public_id);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('payment_webhook_logs', 1);
        $this->assertSame('succeeded', $attempt->refresh()->status);
    }

    public function test_cashfree_webhook_rejects_invalid_signatures_without_updating_payment_state(): void
    {
        [$order, $attempt, $payload] = $this->createPendingCheckoutWebhookContext();

        $response = $this->postWebhook($payload, 'invalid-signature');

        $response->assertStatus(401)
            ->assertJsonPath('data.processing_status', 'signature_mismatch')
            ->assertJsonPath('data.signature_verified', false)
            ->assertJsonPath('data.error_message', 'Webhook signature verification failed.');

        $attempt->refresh();
        $this->assertSame('initiated', $attempt->status);
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(1, PaymentWebhookLog::query()->count());

        $log = PaymentWebhookLog::query()->firstOrFail();
        $this->assertSame('signature_mismatch', $log->processing_status);
        $this->assertFalse($log->signature_verified);
        $this->assertSame($attempt->gateway_order_id, $log->provider_order_id);
    }

    public function test_cashfree_failed_payment_marks_attempt_failed_without_creating_payment_record(): void
    {
        [$order, $attempt, $payload] = $this->createPendingCheckoutWebhookContext([
            'event_type' => 'payment.failed',
            'status' => 'failed',
            'payment_id' => 'cf_pay_1002',
            'event_id' => 'evt_failed_1',
        ]);

        $response = $this->postWebhook($payload, $this->signatureFor($payload));

        $response->assertOk()
            ->assertJsonPath('data.processing_status', 'processed')
            ->assertJsonPath('data.payment_recorded', false)
            ->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.payment_attempt_status', 'failed')
            ->assertJsonPath('data.order_public_id', $order->public_id)
            ->assertJsonPath('data.payment_attempt_public_id', $attempt->public_id);

        $this->assertSame('failed', $attempt->refresh()->status);
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(1, PaymentWebhookLog::query()->count());
    }

    public function test_cashfree_refund_webhook_creates_refund_record_and_updates_payment_status(): void
    {
        [$order, $attempt, $payload] = $this->createPendingCheckoutWebhookContext();
        $this->postWebhook($payload, $this->signatureFor($payload))->assertOk();

        $refundPayload = [
            'provider' => 'cashfree',
            'event_id' => 'evt_refund_1',
            'event_type' => 'refund.succeeded',
            'order_id' => $attempt->gateway_order_id,
            'payment_id' => 'cf_pay_1001',
            'refund_id' => 'cf_ref_2001',
            'amount_minor' => 3798,
            'currency' => 'INR',
            'status' => 'succeeded',
            'received_at' => '2026-06-20T10:15:00Z',
        ];

        $response = $this->postWebhook($refundPayload, $this->signatureFor($refundPayload));

        $response->assertOk()
            ->assertJsonPath('data.processing_status', 'processed')
            ->assertJsonPath('data.payment_recorded', true)
            ->assertJsonPath('data.refund_recorded', true)
            ->assertJsonPath('data.payment_status', 'refunded')
            ->assertJsonPath('data.payment_attempt_public_id', $attempt->public_id)
            ->assertJsonPath('data.order_public_id', $order->public_id);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseCount('payment_webhook_logs', 2);

        $refund = Refund::query()->firstOrFail();
        $this->assertSame($order->id, $refund->order_id);
        $this->assertSame('cashfree', $refund->provider);
        $this->assertSame('full', $refund->refund_type);
        $this->assertSame('succeeded', $refund->status);
        $this->assertSame(3798, $refund->amount_minor);
        $this->assertSame('cf_ref_2001', $refund->provider_refund_id);
        $this->assertSame('cf_pay_1001', $refund->provider_payment_id);
    }

    /**
     * @return array{0: Order, 1: PaymentAttempt, 2: array<string, mixed>}
     */
    private function createPendingCheckoutWebhookContext(array $overrides = []): array
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 3798,
            'subtotal_amount_minor' => 3798,
            'currency' => 'INR',
        ]);

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'initiated',
            'amount_minor' => 3798,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->public_id,
            'gateway_order_id' => 'cf_order_1001',
            'gateway_reference' => 'cf_order_1001',
            'checkout_url' => 'https://cashfree.test/checkout/cf_order_1001',
            'initiated_at' => now(),
        ]);

        $payload = array_merge([
            'provider' => 'cashfree',
            'event_id' => 'evt_success_1',
            'event_type' => 'payment.succeeded',
            'order_id' => $attempt->gateway_order_id,
            'payment_id' => 'cf_pay_1001',
            'amount_minor' => 3798,
            'currency' => 'INR',
            'status' => 'succeeded',
            'received_at' => '2026-06-20T10:00:00Z',
        ], $overrides);

        return [$order, $attempt, $payload];
    }

    private function postWebhook(array $payload, string $signature)
    {
        return $this->withHeader('X-Signature', $signature)
            ->postJson('/api/webhooks/payments/cashfree', $payload);
    }

    private function signatureFor(array $payload): string
    {
        $parts = [
            (string) ($payload['provider'] ?? ''),
            (string) ($payload['event_id'] ?? ''),
            (string) ($payload['event_type'] ?? ''),
            (string) ($payload['order_id'] ?? ''),
            (string) ($payload['payment_id'] ?? ''),
            (string) ($payload['refund_id'] ?? ''),
            (string) ($payload['status'] ?? ''),
            (string) ($payload['amount_minor'] ?? ''),
            (string) ($payload['currency'] ?? ''),
        ];

        return hash_hmac('sha256', implode('|', $parts), (string) config('app.key'));
    }
}
