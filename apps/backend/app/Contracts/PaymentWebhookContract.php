<?php

namespace App\Contracts;

interface PaymentWebhookContract
{
    public function sourceOfTruth(): string;

    public function duplicateHandling(): string;

    public function requiresSignatureVerification(): bool;

    public function requiresProviderEventId(): bool;

    public function keepsRawPayloadsOutOfSharedRecords(): bool;

    public function webhookLogProcessingStatusReceived(): string;

    public function webhookLogProcessingStatusProcessed(): string;

    public function webhookLogProcessingStatusIgnoredDuplicate(): string;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function normalizeWebhook(array $payload, array $headers): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
