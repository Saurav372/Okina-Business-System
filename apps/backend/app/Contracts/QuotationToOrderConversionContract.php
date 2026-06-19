<?php

namespace App\Contracts;

interface QuotationToOrderConversionContract
{
    public function sourceStatus(): string;

    public function convertedOrderType(): string;

    public function convertedOrderSource(): string;

    public function convertedOrderStatus(): string;

    public function requiresApprovedQuotation(): bool;

    public function convertsOnlyOnce(): bool;

    public function usesConversionIdempotencyKey(): bool;

    public function preservesQuotationHistory(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
