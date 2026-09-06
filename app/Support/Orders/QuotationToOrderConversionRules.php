<?php

namespace App\Support\Orders;

use App\Contracts\QuotationToOrderConversionContract;
use App\Enums\OrderStatus;
use App\Enums\OrderType;

readonly class QuotationToOrderConversionRules implements QuotationToOrderConversionContract
{
    public function sourceStatus(): string
    {
        return 'approved';
    }

    public function convertedOrderType(): string
    {
        return OrderType::SalesOrder->value();
    }

    public function convertedOrderSource(): string
    {
        return 'quotation';
    }

    public function convertedOrderStatus(): string
    {
        return OrderStatus::Confirmed->value();
    }

    public function requiresApprovedQuotation(): bool
    {
        return true;
    }

    public function convertsOnlyOnce(): bool
    {
        return true;
    }

    public function usesConversionIdempotencyKey(): bool
    {
        return true;
    }

    public function preservesQuotationHistory(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source_status' => $this->sourceStatus(),
            'converted_order_type' => $this->convertedOrderType(),
            'converted_order_source' => $this->convertedOrderSource(),
            'converted_order_status' => $this->convertedOrderStatus(),
            'requires_approved_quotation' => $this->requiresApprovedQuotation(),
            'converts_only_once' => $this->convertsOnlyOnce(),
            'uses_conversion_idempotency_key' => $this->usesConversionIdempotencyKey(),
            'preserves_quotation_history' => $this->preservesQuotationHistory(),
        ];
    }
}
