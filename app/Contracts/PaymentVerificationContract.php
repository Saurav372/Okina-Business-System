<?php

namespace App\Contracts;

interface PaymentVerificationContract
{
    public function sourceOfTruth(): string;

    public function verifiedPaymentStatus(): string;

    public function pendingVerificationStatus(): string;

    public function failedVerificationStatus(): string;

    public function keepsPaymentsSeparateFromRefunds(): bool;

    public function updatesPaymentRecordsSafely(): bool;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeVerifiedPaymentPayload(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
