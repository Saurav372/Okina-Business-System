<?php

namespace App\Support\Payments;

use App\Contracts\PaymentWebhookContract;

readonly class PaymentWebhookRules implements PaymentWebhookContract
{
    public function sourceOfTruth(): string
    {
        return 'payment_webhook_logs';
    }

    public function duplicateHandling(): string
    {
        return 'ignore_duplicate';
    }

    public function requiresSignatureVerification(): bool
    {
        return true;
    }

    public function requiresProviderEventId(): bool
    {
        return true;
    }

    public function keepsRawPayloadsOutOfSharedRecords(): bool
    {
        return true;
    }

    public function webhookLogProcessingStatusReceived(): string
    {
        return 'received';
    }

    public function webhookLogProcessingStatusProcessed(): string
    {
        return 'processed';
    }

    public function webhookLogProcessingStatusIgnoredDuplicate(): string
    {
        return 'ignored_duplicate';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function normalizeWebhook(array $payload, array $headers): array
    {
        return [
            'provider' => $payload['provider'] ?? 'cashfree',
            'provider_event_id' => $payload['event_id'] ?? null,
            'event_type' => $payload['event_type'] ?? null,
            'provider_order_id' => $payload['order_id'] ?? null,
            'provider_payment_id' => $payload['payment_id'] ?? null,
            'provider_refund_id' => $payload['refund_id'] ?? null,
            'processing_status' => $this->webhookLogProcessingStatusReceived(),
            'signature_verified' => false,
            'payload_summary' => [
                'amount_minor' => $payload['amount_minor'] ?? null,
                'currency' => $payload['currency'] ?? 'INR',
                'status' => $payload['status'] ?? null,
            ],
            'error_message' => null,
            'received_at' => $payload['received_at'] ?? null,
            'headers_isolated' => true,
            'signature_header_present' => array_key_exists('x-signature', $headers),
            'raw_payload_isolated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_of_truth' => $this->sourceOfTruth(),
            'duplicate_handling' => $this->duplicateHandling(),
            'requires_signature_verification' => $this->requiresSignatureVerification(),
            'requires_provider_event_id' => $this->requiresProviderEventId(),
            'keeps_raw_payloads_out_of_shared_records' => $this->keepsRawPayloadsOutOfSharedRecords(),
            'processing_statuses' => [
                'received' => $this->webhookLogProcessingStatusReceived(),
                'processed' => $this->webhookLogProcessingStatusProcessed(),
                'ignored_duplicate' => $this->webhookLogProcessingStatusIgnoredDuplicate(),
            ],
        ];
    }
}
