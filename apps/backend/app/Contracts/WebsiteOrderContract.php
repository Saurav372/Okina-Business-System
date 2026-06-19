<?php

namespace App\Contracts;

interface WebsiteOrderContract
{
    public function orderType(): string;

    public function orderSource(): string;

    public function initialStatus(): string;

    public function createsPendingOrderBeforePayment(): bool;

    public function usesIdempotencyKey(): bool;

    public function requiresPaymentAttemptAfterOrderCreation(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
