<?php

namespace App\Support\Orders;

use App\Contracts\CancellationEligibilityContract;
use App\Enums\OrderStatus;
use App\Enums\OrderType;

readonly class CancellationEligibilityRules implements CancellationEligibilityContract
{
    /**
     * @return array<int, string>
     */
    public function orderTypes(): array
    {
        return OrderType::values();
    }

    /**
     * @return array<int, string>
     */
    public function cancellableStatusesForWebsiteOrders(): array
    {
        return [
            OrderStatus::PendingPayment->value(),
            OrderStatus::Confirmed->value(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function cancellableStatusesForSalesOrders(): array
    {
        return [
            OrderStatus::Confirmed->value(),
        ];
    }

    public function canCancel(string $orderType, string $status): bool
    {
        $type = OrderType::tryFrom($orderType);

        if (! $type instanceof OrderType) {
            return false;
        }

        $orderStatus = OrderStatus::tryFrom($status);

        if (! $orderStatus instanceof OrderStatus) {
            return false;
        }

        return match ($type) {
            OrderType::WebsiteOrder => in_array($orderStatus->value(), $this->cancellableStatusesForWebsiteOrders(), true),
            OrderType::SalesOrder => in_array($orderStatus->value(), $this->cancellableStatusesForSalesOrders(), true),
        };
    }

    public function cancellationIsSeparateFromRefunds(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_types' => $this->orderTypes(),
            'website_order_cancellable_statuses' => $this->cancellableStatusesForWebsiteOrders(),
            'sales_order_cancellable_statuses' => $this->cancellableStatusesForSalesOrders(),
            'cancellation_is_separate_from_refunds' => $this->cancellationIsSeparateFromRefunds(),
        ];
    }
}
