<?php

namespace App\Contracts;

interface ManualPaymentContract
{
    public function provider(): string;

    public function paymentType(): string;

    public function supportsManualPayments(): bool;

    public function requiresPaymentAttempt(): bool;

    public function requiresVerifiedByUser(): bool;

    public function keepsManualPaymentsSeparateFromGatewayLogic(): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeManualPayment(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
