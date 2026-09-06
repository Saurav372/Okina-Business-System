<?php

namespace Tests\Feature;

use App\Support\Payments\CashfreeAdapterCatalog;
use App\Support\Payments\CashfreeAdapterRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashfreeAdapterContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashfree_adapter_exposes_the_provider_specific_mapping_shape(): void
    {
        $adapter = app(CashfreeAdapterRules::class);

        $this->assertSame('cashfree', $adapter->provider());
        $this->assertTrue($adapter->supportsSandboxMode());
        $this->assertTrue($adapter->keepsProviderPayloadsIsolated());

        $this->assertSame(
            [
                'provider' => 'cashfree',
                'order_public_id' => 'ord_123',
                'amount_minor' => 1250,
                'currency' => 'INR',
                'customer_public_id' => 'cus_456',
                'idempotency_key' => 'idempotency:payment_attempt:ord_123:cashfree:cf_order_789',
                'gateway_mode' => 'sandbox',
                'return_url' => 'https://example.test/return',
                'notify_url' => 'https://example.test/notify',
            ],
            $adapter->buildPaymentRequest([
                'order_public_id' => 'ord_123',
                'amount_minor' => 1250,
                'customer_public_id' => 'cus_456',
                'idempotency_key' => 'idempotency:payment_attempt:ord_123:cashfree:cf_order_789',
                'return_url' => 'https://example.test/return',
                'notify_url' => 'https://example.test/notify',
            ]),
        );
    }

    public function test_cashfree_adapter_normalizes_shared_response_webhook_and_refund_shapes(): void
    {
        $adapter = app(CashfreeAdapterRules::class);

        $this->assertSame(
            [
                'provider' => 'cashfree',
                'gateway_order_id' => 'cf_order_789',
                'gateway_payment_id' => 'cf_pay_456',
                'checkout_url' => 'https://cashfree.test/checkout',
                'status' => 'initiated',
                'amount_minor' => 1250,
                'currency' => 'INR',
            ],
            $adapter->normalizePaymentResponse([
                'order_id' => 'cf_order_789',
                'payment_id' => 'cf_pay_456',
                'checkout_url' => 'https://cashfree.test/checkout',
                'status' => 'initiated',
                'amount_minor' => 1250,
                'currency' => 'INR',
            ]),
        );

        $this->assertSame(
            [
                'provider' => 'cashfree',
                'event_id' => 'cf_evt_123',
                'event_type' => 'payment.succeeded',
                'gateway_order_id' => 'cf_order_789',
                'gateway_payment_id' => 'cf_pay_456',
                'status' => 'succeeded',
                'amount_minor' => 1250,
                'currency' => 'INR',
                'payload_isolated' => true,
            ],
            $adapter->normalizeWebhookPayload([
                'event_id' => 'cf_evt_123',
                'event_type' => 'payment.succeeded',
                'order_id' => 'cf_order_789',
                'payment_id' => 'cf_pay_456',
                'status' => 'succeeded',
                'amount_minor' => 1250,
                'currency' => 'INR',
                'raw_payload' => ['secret' => 'should not escape'],
            ]),
        );

        $this->assertSame(
            [
                'provider' => 'cashfree',
                'provider_refund_id' => 'cf_ref_321',
                'gateway_payment_id' => 'cf_pay_456',
                'status' => 'succeeded',
                'amount_minor' => 500,
                'currency' => 'INR',
            ],
            $adapter->normalizeRefundResponse([
                'refund_id' => 'cf_ref_321',
                'payment_id' => 'cf_pay_456',
                'status' => 'succeeded',
                'amount_minor' => 500,
                'currency' => 'INR',
            ]),
        );
    }

    public function test_cashfree_adapter_catalog_documents_provider_payload_isolation(): void
    {
        $catalog = app(CashfreeAdapterCatalog::class);

        $this->assertSame(
            [
                'key' => 'cashfree_adapter_contract',
                'label' => 'Cashfree Adapter Contract',
                'usage' => 'Cashfree maps provider-specific request, response, webhook, and refund payloads into shared payment gateway shapes while keeping provider-only data isolated.',
                'rules' => [
                    'provider' => 'cashfree',
                    'supports_sandbox_mode' => true,
                    'keeps_provider_payloads_isolated' => true,
                    'payment_request_shape' => ['provider', 'order_public_id', 'amount_minor', 'currency', 'customer_public_id', 'idempotency_key', 'gateway_mode', 'return_url', 'notify_url'],
                    'response_shape' => ['provider', 'gateway_order_id', 'gateway_payment_id', 'checkout_url', 'status', 'amount_minor', 'currency'],
                    'webhook_shape' => ['provider', 'event_id', 'event_type', 'gateway_order_id', 'gateway_payment_id', 'status', 'amount_minor', 'currency', 'payload_isolated'],
                    'refund_shape' => ['provider', 'provider_refund_id', 'gateway_payment_id', 'status', 'amount_minor', 'currency'],
                ],
                'safety_note' => 'Cashfree-specific payloads stay isolated from shared business logic; adapter mapping is provider-facing only and does not start gateway operations by itself.',
                'references' => ['A4.2', 'A4.5', 'A5.3.1', 'A5.3.3', 'A5.3.5', 'B3.1', 'B3.3'],
            ],
            $catalog->definition(),
        );
    }
}
