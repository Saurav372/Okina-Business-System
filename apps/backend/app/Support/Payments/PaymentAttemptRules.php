<?php

namespace App\Support\Payments;

use App\Contracts\PaymentAttemptContract;

readonly class PaymentAttemptRules implements PaymentAttemptContract
{
    public function attemptType(): string
    {
        return 'website_checkout';
    }

    public function provider(): string
    {
        return 'cashfree';
    }

    public function duplicateHandling(): string
    {
        return 'reuse_existing';
    }

    public function requiresIdempotencyKey(): bool
    {
        return true;
    }

    public function requiresTraceableGatewayReference(): bool
    {
        return true;
    }

    public function isTerminalStatus(string $status): bool
    {
        return in_array($status, ['succeeded', 'failed', 'expired', 'cancelled'], true);
    }

    public function allowedStatuses(): array
    {
        return ['created', 'initiated', 'requires_action', 'succeeded', 'failed', 'expired', 'cancelled'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeAttemptPayload(array $payload): array
    {
        return [
            'order_public_id' => $payload['order_public_id'] ?? null,
            'provider' => $payload['provider'] ?? $this->provider(),
            'attempt_type' => $payload['attempt_type'] ?? $this->attemptType(),
            'status' => $payload['status'] ?? 'created',
            'amount_minor' => $payload['amount_minor'] ?? null,
            'currency' => $payload['currency'] ?? 'INR',
            'gateway_order_id' => $payload['gateway_order_id'] ?? null,
            'gateway_payment_id' => $payload['gateway_payment_id'] ?? null,
            'gateway_reference' => $payload['gateway_reference'] ?? null,
            'checkout_url' => $payload['checkout_url'] ?? null,
            'idempotency_key' => $payload['idempotency_key'] ?? null,
            'metadata_isolated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attempt_type' => $this->attemptType(),
            'provider' => $this->provider(),
            'duplicate_handling' => $this->duplicateHandling(),
            'requires_idempotency_key' => $this->requiresIdempotencyKey(),
            'requires_traceable_gateway_reference' => $this->requiresTraceableGatewayReference(),
            'allowed_statuses' => $this->allowedStatuses(),
            'terminal_statuses' => ['succeeded', 'failed', 'expired', 'cancelled'],
            'metadata_isolated' => true,
        ];
    }
}
