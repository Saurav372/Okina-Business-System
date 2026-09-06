<?php

namespace App\Contracts;

interface GatewayFailureHandlingContract
{
    public function sourceOfTruth(): string;

    public function failureLogChannel(): string;

    public function retryUntilMinutes(): int;

    public function retryableFailureTypes(): array;

    public function nonRetryableFailureTypes(): array;

    public function logsSafeContextOnly(): bool;

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function normalizeFailureContext(array $context): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
