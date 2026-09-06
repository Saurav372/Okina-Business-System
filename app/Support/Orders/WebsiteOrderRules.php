<?php

namespace App\Support\Orders;

use App\Contracts\WebsiteOrderContract;
use App\Enums\OrderStatus;
use App\Enums\OrderType;

readonly class WebsiteOrderRules implements WebsiteOrderContract
{
    public function orderType(): string
    {
        return OrderType::WebsiteOrder->value();
    }

    public function orderSource(): string
    {
        return 'website';
    }

    public function initialStatus(): string
    {
        return OrderStatus::PendingPayment->value();
    }

    public function createsPendingOrderBeforePayment(): bool
    {
        return true;
    }

    public function usesIdempotencyKey(): bool
    {
        return true;
    }

    public function requiresPaymentAttemptAfterOrderCreation(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_type' => $this->orderType(),
            'order_source' => $this->orderSource(),
            'initial_status' => $this->initialStatus(),
            'creates_pending_order_before_payment' => $this->createsPendingOrderBeforePayment(),
            'uses_idempotency_key' => $this->usesIdempotencyKey(),
            'requires_payment_attempt_after_order_creation' => $this->requiresPaymentAttemptAfterOrderCreation(),
        ];
    }
}
