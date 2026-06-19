<?php

namespace App\Contracts;

interface PaymentAttemptContract
{
    public function attemptType(): string;

    public function provider(): string;

    public function duplicateHandling(): string;

    public function requiresIdempotencyKey(): bool;

    public function requiresTraceableGatewayReference(): bool;

    public function isTerminalStatus(string $status): bool;

    public function allowedStatuses(): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeAttemptPayload(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
