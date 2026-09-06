<?php

namespace App\Contracts;

interface PaymentGatewayContract
{
    public function provider(): string;

    public function isGatewayIndependentCheckoutSupported(): bool;

    public function supportsOnlinePaymentInitiation(): bool;

    public function supportsWebhookVerification(): bool;

    public function supportsRefunds(): bool;

    public function requiresIdempotencyForPaymentAttempt(): bool;

    public function requiresOrderBeforePaymentInitiation(): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiatePayment(array $payload): array;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function verifyWebhook(array $payload, array $headers): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRefund(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
