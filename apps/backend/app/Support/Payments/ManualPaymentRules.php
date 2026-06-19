<?php

namespace App\Support\Payments;

use App\Contracts\ManualPaymentContract;

readonly class ManualPaymentRules implements ManualPaymentContract
{
    public function provider(): string
    {
        return 'manual';
    }

    public function paymentType(): string
    {
        return 'manual_adjustment';
    }

    public function supportsManualPayments(): bool
    {
        return true;
    }

    public function requiresPaymentAttempt(): bool
    {
        return false;
    }

    public function requiresVerifiedByUser(): bool
    {
        return true;
    }

    public function keepsManualPaymentsSeparateFromGatewayLogic(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeManualPayment(array $payload): array
    {
        return [
            'order_public_id' => $payload['order_public_id'] ?? null,
            'provider' => $payload['provider'] ?? $this->provider(),
            'payment_type' => $payload['payment_type'] ?? $this->paymentType(),
            'status' => $payload['status'] ?? 'pending_verification',
            'amount_minor' => $payload['amount_minor'] ?? null,
            'currency' => $payload['currency'] ?? 'INR',
            'method' => $payload['method'] ?? 'cash',
            'provider_payment_id' => $payload['provider_payment_id'] ?? null,
            'provider_reference' => $payload['provider_reference'] ?? null,
            'payment_attempt_public_id' => null,
            'verified_by_user_id' => $payload['verified_by_user_id'] ?? null,
            'recorded_by_user_id' => $payload['recorded_by_user_id'] ?? null,
            'notes_isolated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider(),
            'payment_type' => $this->paymentType(),
            'supports_manual_payments' => $this->supportsManualPayments(),
            'requires_payment_attempt' => $this->requiresPaymentAttempt(),
            'requires_verified_by_user' => $this->requiresVerifiedByUser(),
            'keeps_manual_payments_separate_from_gateway_logic' => $this->keepsManualPaymentsSeparateFromGatewayLogic(),
        ];
    }
}
