<?php

namespace App\Support\Payments;

use App\Contracts\PaymentVerificationContract;

readonly class PaymentVerificationRules implements PaymentVerificationContract
{
    public function sourceOfTruth(): string
    {
        return 'payments';
    }

    public function verifiedPaymentStatus(): string
    {
        return 'succeeded';
    }

    public function pendingVerificationStatus(): string
    {
        return 'pending_verification';
    }

    public function failedVerificationStatus(): string
    {
        return 'failed';
    }

    public function keepsPaymentsSeparateFromRefunds(): bool
    {
        return true;
    }

    public function updatesPaymentRecordsSafely(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeVerifiedPaymentPayload(array $payload): array
    {
        return [
            'order_public_id' => $payload['order_public_id'] ?? null,
            'payment_attempt_public_id' => $payload['payment_attempt_public_id'] ?? null,
            'provider' => $payload['provider'] ?? 'cashfree',
            'payment_type' => $payload['payment_type'] ?? 'full',
            'status' => $this->verifiedPaymentStatus(),
            'amount_minor' => $payload['amount_minor'] ?? null,
            'currency' => $payload['currency'] ?? 'INR',
            'provider_payment_id' => $payload['provider_payment_id'] ?? null,
            'provider_order_id' => $payload['provider_order_id'] ?? null,
            'provider_reference' => $payload['provider_reference'] ?? null,
            'gateway_fee_minor' => $payload['gateway_fee_minor'] ?? null,
            'net_amount_minor' => $payload['net_amount_minor'] ?? null,
            'paid_at' => $payload['paid_at'] ?? null,
            'metadata_isolated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_of_truth' => $this->sourceOfTruth(),
            'verified_payment_status' => $this->verifiedPaymentStatus(),
            'pending_verification_status' => $this->pendingVerificationStatus(),
            'failed_verification_status' => $this->failedVerificationStatus(),
            'keeps_payments_separate_from_refunds' => $this->keepsPaymentsSeparateFromRefunds(),
            'updates_payment_records_safely' => $this->updatesPaymentRecordsSafely(),
        ];
    }
}
