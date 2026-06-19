<?php

namespace Tests\Feature;

use App\Support\Payments\PaymentWebhookCatalog;
use App\Support\Payments\PaymentWebhookRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_webhook_rules_define_authenticated_deduplicated_replay_safe_webhooks(): void
    {
        $rules = app(PaymentWebhookRules::class);

        $this->assertSame('payment_webhook_logs', $rules->sourceOfTruth());
        $this->assertSame('ignore_duplicate', $rules->duplicateHandling());
        $this->assertTrue($rules->requiresSignatureVerification());
        $this->assertTrue($rules->requiresProviderEventId());
        $this->assertTrue($rules->keepsRawPayloadsOutOfSharedRecords());
        $this->assertSame('received', $rules->webhookLogProcessingStatusReceived());
        $this->assertSame('processed', $rules->webhookLogProcessingStatusProcessed());
        $this->assertSame('ignored_duplicate', $rules->webhookLogProcessingStatusIgnoredDuplicate());
    }

    public function test_payment_webhook_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(PaymentWebhookRules::class);

        $this->assertSame(
            [
                'source_of_truth' => 'payment_webhook_logs',
                'duplicate_handling' => 'ignore_duplicate',
                'requires_signature_verification' => true,
                'requires_provider_event_id' => true,
                'keeps_raw_payloads_out_of_shared_records' => true,
                'processing_statuses' => [
                    'received' => 'received',
                    'processed' => 'processed',
                    'ignored_duplicate' => 'ignored_duplicate',
                ],
            ],
            $rules->toArray(),
        );
    }

    public function test_payment_webhook_rules_normalize_webhooks_without_leaking_raw_payloads(): void
    {
        $rules = app(PaymentWebhookRules::class);
        $normalized = $rules->normalizeWebhook([
            'provider' => 'cashfree',
            'event_id' => 'evt_123',
            'event_type' => 'payment.succeeded',
            'order_id' => 'cf_order_789',
            'payment_id' => 'cf_pay_456',
            'refund_id' => null,
            'amount_minor' => 1250,
            'currency' => 'INR',
            'status' => 'succeeded',
            'raw_payload' => ['token' => 'secret'],
            'received_at' => '2026-06-18 12:34:00',
        ], [
            'x-signature' => 'sig_abc',
            'x-event' => 'payment.succeeded',
        ]);

        $this->assertSame('cashfree', $normalized['provider']);
        $this->assertSame('evt_123', $normalized['provider_event_id']);
        $this->assertSame('payment.succeeded', $normalized['event_type']);
        $this->assertSame('cf_order_789', $normalized['provider_order_id']);
        $this->assertSame('cf_pay_456', $normalized['provider_payment_id']);
        $this->assertNull($normalized['provider_refund_id']);
        $this->assertSame('received', $normalized['processing_status']);
        $this->assertFalse($normalized['signature_verified']);
        $this->assertSame(['amount_minor' => 1250, 'currency' => 'INR', 'status' => 'succeeded'], $normalized['payload_summary']);
        $this->assertNull($normalized['error_message']);
        $this->assertSame('2026-06-18 12:34:00', $normalized['received_at']);
        $this->assertTrue($normalized['headers_isolated']);
        $this->assertTrue($normalized['signature_header_present']);
        $this->assertTrue($normalized['raw_payload_isolated']);

        $catalog = app(PaymentWebhookCatalog::class);
        $this->assertSame(
            [
                'key' => 'payment_webhook_contract',
                'label' => 'Payment Webhook Contract',
                'usage' => 'Webhook events are authenticated, deduplicated, and reduced to safe summary fields before payment or refund processing uses them.',
                'rules' => [
                    'source_of_truth' => 'payment_webhook_logs',
                    'duplicate_handling' => 'ignore_duplicate',
                    'requires_signature_verification' => true,
                    'requires_provider_event_id' => true,
                    'keeps_raw_payloads_out_of_shared_records' => true,
                    'processing_statuses' => [
                        'received' => 'received',
                        'processed' => 'processed',
                        'ignored_duplicate' => 'ignored_duplicate',
                    ],
                    'headers_isolated' => true,
                    'raw_payload_isolated' => true,
                ],
                'safety_note' => 'Webhook handling stays at the replay-safety layer until a later task turns the safe summary into payment or refund updates.',
                'references' => ['A4.5', 'A5.3.1', 'A5.3.2', 'A5.3.4', 'B3.3.5', 'B3.3.6'],
            ],
            $catalog->definition(),
        );
    }
}
