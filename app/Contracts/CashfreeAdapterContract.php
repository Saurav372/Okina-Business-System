<?php

namespace App\Contracts;

interface CashfreeAdapterContract
{
    public function provider(): string;

    public function supportsSandboxMode(): bool;

    public function keepsProviderPayloadsIsolated(): bool;

    /**
     * @param  array<string, mixed>  $sharedPayload
     * @return array<string, mixed>
     */
    public function buildPaymentRequest(array $sharedPayload): array;

    /**
     * @param  array<string, mixed>  $cashfreePayload
     * @return array<string, mixed>
     */
    public function normalizePaymentResponse(array $cashfreePayload): array;

    /**
     * @param  array<string, mixed>  $cashfreePayload
     * @return array<string, mixed>
     */
    public function normalizeWebhookPayload(array $cashfreePayload): array;

    /**
     * @param  array<string, mixed>  $cashfreePayload
     * @return array<string, mixed>
     */
    public function normalizeRefundResponse(array $cashfreePayload): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
