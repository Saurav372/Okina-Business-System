<?php

namespace App\Contracts;

interface RefundInterfaceContract
{
    public function sourceOfTruth(): string;

    public function partialRefundType(): string;

    public function fullRefundType(): string;

    public function requestedStatus(): string;

    public function approvedStatus(): string;

    public function processingStatus(): string;

    public function succeededStatus(): string;

    public function failedStatus(): string;

    public function cancelledStatus(): string;

    public function usesSharedRefundsTable(): bool;

    public function keepsOriginalPayments(): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeRefundPayload(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
