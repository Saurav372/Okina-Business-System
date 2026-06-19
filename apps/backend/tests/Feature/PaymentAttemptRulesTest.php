<?php

namespace Tests\Feature;

use App\Support\Payments\PaymentAttemptCatalog;
use App\Support\Payments\PaymentAttemptRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentAttemptRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_attempt_rules_define_traceable_idempotent_attempts(): void
    {
        $rules = app(PaymentAttemptRules::class);

        $this->assertSame('website_checkout', $rules->attemptType());
        $this->assertSame('cashfree', $rules->provider());
        $this->assertSame('reuse_existing', $rules->duplicateHandling());
        $this->assertTrue($rules->requiresIdempotencyKey());
        $this->assertTrue($rules->requiresTraceableGatewayReference());
        $this->assertTrue($rules->isTerminalStatus('succeeded'));
        $this->assertTrue($rules->isTerminalStatus('failed'));
        $this->assertFalse($rules->isTerminalStatus('initiated'));
        $this->assertSame(['created', 'initiated', 'requires_action', 'succeeded', 'failed', 'expired', 'cancelled'], $rules->allowedStatuses());
    }

    public function test_payment_attempt_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(PaymentAttemptRules::class);

        $this->assertSame(
            [
                'attempt_type' => 'website_checkout',
                'provider' => 'cashfree',
                'duplicate_handling' => 'reuse_existing',
                'requires_idempotency_key' => true,
                'requires_traceable_gateway_reference' => true,
                'allowed_statuses' => ['created', 'initiated', 'requires_action', 'succeeded', 'failed', 'expired', 'cancelled'],
                'terminal_statuses' => ['succeeded', 'failed', 'expired', 'cancelled'],
                'metadata_isolated' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_payment_attempt_rules_normalize_payloads_without_exposing_secret_data(): void
    {
        $rules = app(PaymentAttemptRules::class);
        $payload = $rules->normalizeAttemptPayload([
            'order_public_id' => 'ord_123',
            'amount_minor' => 1250,
            'gateway_order_id' => 'cf_order_789',
            'gateway_payment_id' => 'cf_pay_456',
            'gateway_reference' => 'cf_ref_999',
            'checkout_url' => 'https://cashfree.test/checkout',
            'idempotency_key' => 'idempotency:payment_attempt:ord_123:cashfree:cf_order_789',
            'metadata' => ['token' => 'secret'],
        ]);

        $this->assertSame('ord_123', $payload['order_public_id']);
        $this->assertSame('cashfree', $payload['provider']);
        $this->assertSame('website_checkout', $payload['attempt_type']);
        $this->assertSame('created', $payload['status']);
        $this->assertSame(1250, $payload['amount_minor']);
        $this->assertSame('INR', $payload['currency']);
        $this->assertSame('cf_order_789', $payload['gateway_order_id']);
        $this->assertSame('cf_pay_456', $payload['gateway_payment_id']);
        $this->assertSame('cf_ref_999', $payload['gateway_reference']);
        $this->assertSame('https://cashfree.test/checkout', $payload['checkout_url']);
        $this->assertSame('idempotency:payment_attempt:ord_123:cashfree:cf_order_789', $payload['idempotency_key']);
        $this->assertTrue($payload['metadata_isolated']);

        $catalog = app(PaymentAttemptCatalog::class);
        $this->assertSame(
            [
                'key' => 'payment_attempt_rules',
                'label' => 'Payment Attempt Rules',
                'usage' => 'A website checkout payment attempt is created once, identified by an idempotency key, and kept traceable through provider references and terminal statuses.',
                'rules' => [
                    'attempt_type' => 'website_checkout',
                    'provider' => 'cashfree',
                    'duplicate_handling' => 'reuse_existing',
                    'requires_idempotency_key' => true,
                    'requires_traceable_gateway_reference' => true,
                    'allowed_statuses' => ['created', 'initiated', 'requires_action', 'succeeded', 'failed', 'expired', 'cancelled'],
                    'terminal_statuses' => ['succeeded', 'failed', 'expired', 'cancelled'],
                    'metadata_isolated' => true,
                ],
                'safety_note' => 'Attempt payloads are normalized without exposing secrets, and duplicate checkout handling reuses the existing attempt instead of creating a second active one.',
                'references' => ['A4.5', 'A5.1.1', 'A5.3.1', 'A5.3.2', 'B3.1.6', 'B3.1.8', 'B3.3.5'],
            ],
            $catalog->definition(),
        );
    }
}
