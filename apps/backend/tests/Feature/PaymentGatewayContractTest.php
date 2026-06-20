<?php

namespace Tests\Feature;

use App\Support\Payments\PaymentGatewayCatalog;
use App\Support\Payments\PaymentGatewayRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_gateway_contract_exposes_the_gateway_agnostic_shape(): void
    {
        $rules = app(PaymentGatewayRules::class);

        $this->assertSame('gateway_agnostic', $rules->provider());
        $this->assertTrue($rules->isGatewayIndependentCheckoutSupported());
        $this->assertTrue($rules->supportsOnlinePaymentInitiation());
        $this->assertTrue($rules->supportsWebhookVerification());
        $this->assertTrue($rules->supportsRefunds());
        $this->assertTrue($rules->requiresIdempotencyForPaymentAttempt());
        $this->assertTrue($rules->requiresOrderBeforePaymentInitiation());
    }

    public function test_payment_gateway_rules_initiate_payment_returns_a_provider_checkout_shape(): void
    {
        $rules = app(PaymentGatewayRules::class);

        $response = $rules->initiatePayment([
            'provider' => 'cashfree',
            'idempotency_key' => 'idempotency:payment_attempt:example',
            'amount_minor' => 3798,
            'currency' => 'INR',
        ]);

        $this->assertSame('cashfree', $response['provider']);
        $this->assertMatchesRegularExpression('/^cf_order_[A-F0-9]{16}$/', $response['order_id']);
        $this->assertNull($response['payment_id']);
        $this->assertStringStartsWith('https://cashfree.test/checkout/', $response['checkout_url']);
        $this->assertStringEndsWith($response['order_id'], $response['checkout_url']);
        $this->assertSame('initiated', $response['status']);
        $this->assertSame(3798, $response['amount_minor']);
        $this->assertSame('INR', $response['currency']);
    }

    public function test_payment_gateway_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(PaymentGatewayRules::class);

        $this->assertSame(
            [
                'provider' => 'gateway_agnostic',
                'gateway_independent_checkout_supported' => true,
                'supports_online_payment_initiation' => true,
                'supports_webhook_verification' => true,
                'supports_refunds' => true,
                'requires_idempotency_for_payment_attempt' => true,
                'requires_order_before_payment_initiation' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_payment_gateway_catalog_documents_the_replaceable_provider_boundary(): void
    {
        $catalog = app(PaymentGatewayCatalog::class);

        $this->assertSame(
            [
                'key' => 'payment_gateway_contract',
                'label' => 'Payment Gateway Contract',
                'usage' => 'Checkout and admin depend on an interface that can be implemented by Cashfree or any future provider without hardcoding provider logic into the core flow.',
                'rules' => [
                    'provider' => 'gateway_agnostic',
                    'gateway_independent_checkout_supported' => true,
                    'supports_online_payment_initiation' => true,
                    'supports_webhook_verification' => true,
                    'supports_refunds' => true,
                    'requires_idempotency_for_payment_attempt' => true,
                    'requires_order_before_payment_initiation' => true,
                ],
                'safety_note' => 'The contract describes provider-neutral behavior only; Cashfree adapter details, webhook parsing, and provider-specific exceptions belong to later subtasks.',
                'references' => ['A4.2', 'A4.5', 'A5.1.1', 'A5.1.3', 'A5.3.2', 'B3.1', 'B3.3'],
            ],
            $catalog->definition(),
        );
    }
}
