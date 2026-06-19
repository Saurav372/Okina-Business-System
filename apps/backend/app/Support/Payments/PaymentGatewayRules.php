<?php

namespace App\Support\Payments;

use App\Contracts\PaymentGatewayContract;

readonly class PaymentGatewayRules implements PaymentGatewayContract
{
    public function provider(): string
    {
        return 'gateway_agnostic';
    }

    public function isGatewayIndependentCheckoutSupported(): bool
    {
        return true;
    }

    public function supportsOnlinePaymentInitiation(): bool
    {
        return true;
    }

    public function supportsWebhookVerification(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return true;
    }

    public function requiresIdempotencyForPaymentAttempt(): bool
    {
        return true;
    }

    public function requiresOrderBeforePaymentInitiation(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initiatePayment(array $payload): array
    {
        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    public function verifyWebhook(array $payload, array $headers): array
    {
        return [
            'verified' => true,
            'payload' => $payload,
            'headers' => $headers,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createRefund(array $payload): array
    {
        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider(),
            'gateway_independent_checkout_supported' => $this->isGatewayIndependentCheckoutSupported(),
            'supports_online_payment_initiation' => $this->supportsOnlinePaymentInitiation(),
            'supports_webhook_verification' => $this->supportsWebhookVerification(),
            'supports_refunds' => $this->supportsRefunds(),
            'requires_idempotency_for_payment_attempt' => $this->requiresIdempotencyForPaymentAttempt(),
            'requires_order_before_payment_initiation' => $this->requiresOrderBeforePaymentInitiation(),
        ];
    }
}
