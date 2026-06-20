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
        $provider = (string) ($payload['provider'] ?? 'cashfree');
        $gatewayOrderId = (string) ($payload['gateway_order_id'] ?? $this->gatewayOrderId($provider, (string) ($payload['idempotency_key'] ?? '')));

        return [
            'provider' => $provider,
            'order_id' => $gatewayOrderId,
            'payment_id' => $payload['gateway_payment_id'] ?? null,
            'checkout_url' => $payload['checkout_url'] ?? $this->checkoutUrl($provider, $gatewayOrderId),
            'status' => 'initiated',
            'amount_minor' => $payload['amount_minor'] ?? null,
            'currency' => $payload['currency'] ?? 'INR',
        ];
    }

    private function gatewayOrderId(string $provider, string $idempotencyKey): string
    {
        $prefix = $provider === 'cashfree' ? 'cf_order_' : 'gw_order_';

        return $prefix.strtoupper(substr(hash('sha256', $idempotencyKey), 0, 16));
    }

    private function checkoutUrl(string $provider, string $gatewayOrderId): string
    {
        $baseUrl = $provider === 'cashfree'
            ? 'https://cashfree.test/checkout/'
            : 'https://gateway.test/checkout/';

        return $baseUrl.$gatewayOrderId;
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
