<?php

namespace App\Support\Orders;

use App\Contracts\CancellationEffectContract;
use App\Enums\OrderStatus;

readonly class CancellationEffectRules implements CancellationEffectContract
{
    public function cancelledOrderStatus(): string
    {
        return OrderStatus::Cancelled->value();
    }

    public function changesPaymentFacts(): bool
    {
        return false;
    }

    public function triggersRefundExecution(): bool
    {
        return false;
    }

    public function changesStockOnCancellation(): bool
    {
        return false;
    }

    public function keepsTheCancelledOrderCustomerVisible(): bool
    {
        return true;
    }

    public function hidesSensitiveCancellationNotesFromCustomers(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cancelled_order_status' => $this->cancelledOrderStatus(),
            'changes_payment_facts' => $this->changesPaymentFacts(),
            'triggers_refund_execution' => $this->triggersRefundExecution(),
            'changes_stock_on_cancellation' => $this->changesStockOnCancellation(),
            'keeps_the_cancelled_order_customer_visible' => $this->keepsTheCancelledOrderCustomerVisible(),
            'hides_sensitive_cancellation_notes_from_customers' => $this->hidesSensitiveCancellationNotesFromCustomers(),
        ];
    }
}
