<?php

namespace App\Support\Payments;

final class GatewayFailureHandlingCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
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
        ];
    }
}
