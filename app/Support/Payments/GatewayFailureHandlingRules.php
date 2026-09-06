<?php

namespace App\Support\Payments;

use App\Contracts\GatewayFailureHandlingContract;
use App\Support\Queue\QueueFoundation;

readonly class GatewayFailureHandlingRules implements GatewayFailureHandlingContract
{
    private readonly QueueFoundation $foundation;

    public function __construct(?QueueFoundation $foundation = null)
    {
        $this->foundation = $foundation ?? new QueueFoundation;
    }

    public function sourceOfTruth(): string
    {
        return 'queue_failure_log_and_failed_jobs';
    }

    public function failureLogChannel(): string
    {
        return $this->foundation->failureLogChannel();
    }

    public function retryUntilMinutes(): int
    {
        return $this->foundation->retryUntilMinutes();
    }

    public function retryableFailureTypes(): array
    {
        return ['connection_timeout', 'temporary_provider_error', 'provider_5xx', 'network_error'];
    }

    public function nonRetryableFailureTypes(): array
    {
        return ['validation_error', 'signature_mismatch', 'bad_request', 'duplicate_event', 'unauthorized'];
    }

    public function logsSafeContextOnly(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function normalizeFailureContext(array $context): array
    {
        $safe = [
            'gateway' => $context['gateway'] ?? null,
            'failure_type' => $context['failure_type'] ?? null,
            'failure_reason' => $context['failure_reason'] ?? null,
            'retryable' => $context['retryable'] ?? null,
            'attempt_public_id' => $context['attempt_public_id'] ?? null,
            'order_public_id' => $context['order_public_id'] ?? null,
            'provider_event_id' => $context['provider_event_id'] ?? null,
            'provider_payment_id' => $context['provider_payment_id'] ?? null,
            'provider_refund_id' => $context['provider_refund_id'] ?? null,
            'queue_connection' => $context['queue_connection'] ?? null,
            'queue_name' => $context['queue_name'] ?? null,
            'job_class' => $context['job_class'] ?? null,
            'failed_at' => $context['failed_at'] ?? null,
            'retry_until_minutes' => $context['retry_until_minutes'] ?? $this->retryUntilMinutes(),
        ];

        return array_filter($safe, static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_of_truth' => $this->sourceOfTruth(),
            'failure_log_channel' => $this->failureLogChannel(),
            'retry_until_minutes' => $this->retryUntilMinutes(),
            'retryable_failure_types' => $this->retryableFailureTypes(),
            'non_retryable_failure_types' => $this->nonRetryableFailureTypes(),
            'logs_safe_context_only' => $this->logsSafeContextOnly(),
        ];
    }
}
