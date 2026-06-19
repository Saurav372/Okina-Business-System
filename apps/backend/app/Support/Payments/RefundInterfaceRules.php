<?php

namespace App\Support\Payments;

use App\Contracts\RefundInterfaceContract;

readonly class RefundInterfaceRules implements RefundInterfaceContract
{
    public function sourceOfTruth(): string
    {
        return 'refunds';
    }

    public function partialRefundType(): string
    {
        return 'partial';
    }

    public function fullRefundType(): string
    {
        return 'full';
    }

    public function requestedStatus(): string
    {
        return 'requested';
    }

    public function approvedStatus(): string
    {
        return 'approved';
    }

    public function processingStatus(): string
    {
        return 'processing';
    }

    public function succeededStatus(): string
    {
        return 'succeeded';
    }

    public function failedStatus(): string
    {
        return 'failed';
    }

    public function cancelledStatus(): string
    {
        return 'cancelled';
    }

    public function usesSharedRefundsTable(): bool
    {
        return true;
    }

    public function keepsOriginalPayments(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeRefundPayload(array $payload): array
    {
        return [
            'order_public_id' => $payload['order_public_id'] ?? null,
            'payment_public_id' => $payload['payment_public_id'] ?? null,
            'provider' => $payload['provider'] ?? 'manual',
            'refund_type' => $payload['refund_type'] ?? $this->partialRefundType(),
            'status' => $payload['status'] ?? $this->requestedStatus(),
            'amount_minor' => $payload['amount_minor'] ?? null,
            'currency' => $payload['currency'] ?? 'INR',
            'provider_refund_id' => $payload['provider_refund_id'] ?? null,
            'provider_payment_id' => $payload['provider_payment_id'] ?? null,
            'provider_reference' => $payload['provider_reference'] ?? null,
            'requested_by_user_id' => $payload['requested_by_user_id'] ?? null,
            'approved_by_user_id' => $payload['approved_by_user_id'] ?? null,
            'processed_by_user_id' => $payload['processed_by_user_id'] ?? null,
            'requested_at' => $payload['requested_at'] ?? null,
            'approved_at' => $payload['approved_at'] ?? null,
            'processed_at' => $payload['processed_at'] ?? null,
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
            'partial_refund_type' => $this->partialRefundType(),
            'full_refund_type' => $this->fullRefundType(),
            'requested_status' => $this->requestedStatus(),
            'approved_status' => $this->approvedStatus(),
            'processing_status' => $this->processingStatus(),
            'succeeded_status' => $this->succeededStatus(),
            'failed_status' => $this->failedStatus(),
            'cancelled_status' => $this->cancelledStatus(),
            'uses_shared_refunds_table' => $this->usesSharedRefundsTable(),
            'keeps_original_payments' => $this->keepsOriginalPayments(),
        ];
    }
}
