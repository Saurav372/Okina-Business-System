<?php

namespace Tests\Feature;

use App\Support\Payments\GatewayFailureHandlingCatalog;
use App\Support\Payments\GatewayFailureHandlingRules;
use App\Support\Queue\QueueFoundation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayFailureHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_failure_rules_define_safe_recoverable_failure_handling(): void
    {
        $rules = app(GatewayFailureHandlingRules::class);

        $this->assertSame('queue_failure_log_and_failed_jobs', $rules->sourceOfTruth());
        $this->assertSame(config('queue.okina.failure_log_channel', 'stack'), $rules->failureLogChannel());
        $this->assertSame(app(QueueFoundation::class)->retryUntilMinutes(), $rules->retryUntilMinutes());
        $this->assertSame(['connection_timeout', 'temporary_provider_error', 'provider_5xx', 'network_error'], $rules->retryableFailureTypes());
        $this->assertSame(['validation_error', 'signature_mismatch', 'bad_request', 'duplicate_event', 'unauthorized'], $rules->nonRetryableFailureTypes());
        $this->assertTrue($rules->logsSafeContextOnly());
    }

    public function test_gateway_failure_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(GatewayFailureHandlingRules::class);

        $this->assertSame(
            [
                'source_of_truth' => 'queue_failure_log_and_failed_jobs',
                'failure_log_channel' => config('queue.okina.failure_log_channel', 'stack'),
                'retry_until_minutes' => app(QueueFoundation::class)->retryUntilMinutes(),
                'retryable_failure_types' => ['connection_timeout', 'temporary_provider_error', 'provider_5xx', 'network_error'],
                'non_retryable_failure_types' => ['validation_error', 'signature_mismatch', 'bad_request', 'duplicate_event', 'unauthorized'],
                'logs_safe_context_only' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_gateway_failure_rules_normalize_failure_context_without_leaking_raw_payloads(): void
    {
        $rules = app(GatewayFailureHandlingRules::class);

        $normalized = $rules->normalizeFailureContext([
            'gateway' => 'cashfree',
            'failure_type' => 'provider_5xx',
            'failure_reason' => 'temporary upstream error',
            'retryable' => true,
            'attempt_public_id' => 'payatt_123',
            'order_public_id' => 'ord_456',
            'provider_event_id' => 'evt_789',
            'provider_payment_id' => 'pay_012',
            'provider_refund_id' => 'ref_345',
            'queue_connection' => 'database',
            'queue_name' => 'default',
            'job_class' => 'App\\Jobs\\SyncPayment',
            'failed_at' => '2026-06-18 14:00:00',
            'retry_until_minutes' => 180,
            'raw_payload' => ['token' => 'secret'],
            'credentials' => 'should-not-escape',
        ]);

        $this->assertSame('cashfree', $normalized['gateway']);
        $this->assertSame('provider_5xx', $normalized['failure_type']);
        $this->assertSame('temporary upstream error', $normalized['failure_reason']);
        $this->assertTrue($normalized['retryable']);
        $this->assertSame('payatt_123', $normalized['attempt_public_id']);
        $this->assertSame('ord_456', $normalized['order_public_id']);
        $this->assertSame('evt_789', $normalized['provider_event_id']);
        $this->assertSame('pay_012', $normalized['provider_payment_id']);
        $this->assertSame('ref_345', $normalized['provider_refund_id']);
        $this->assertSame('database', $normalized['queue_connection']);
        $this->assertSame('default', $normalized['queue_name']);
        $this->assertSame('App\\Jobs\\SyncPayment', $normalized['job_class']);
        $this->assertSame('2026-06-18 14:00:00', $normalized['failed_at']);
        $this->assertSame(180, $normalized['retry_until_minutes']);

        $catalog = app(GatewayFailureHandlingCatalog::class);
        $this->assertSame(
            [
                'key' => 'gateway_failure_handling',
                'label' => 'Gateway Failure Handling',
                'usage' => 'Gateway failures are logged with safe context, queued jobs may retry within the shared queue window, and only retryable failures are treated as recoverable.',
                'rules' => [
                    'source_of_truth' => 'queue_failure_log_and_failed_jobs',
                    'failure_log_channel' => 'stack',
                    'retry_until_minutes' => 120,
                    'retryable_failure_types' => ['connection_timeout', 'temporary_provider_error', 'provider_5xx', 'network_error'],
                    'non_retryable_failure_types' => ['validation_error', 'signature_mismatch', 'bad_request', 'duplicate_event', 'unauthorized'],
                    'logs_safe_context_only' => true,
                ],
                'safety_note' => 'Failure logging never captures raw gateway payloads, credentials, or signatures, and recovery logic remains separate from payment-record writes.',
                'references' => ['A4.2', 'A4.3', 'A4.5', 'A5.3.1', 'A5.3.2', 'A5.3.5', 'B3.3.6'],
            ],
            $catalog->definition(),
        );
    }
}
