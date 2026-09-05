<?php

namespace App\Support\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

final class DashboardOrders
{
    public static function open(): Builder
    {
        return Order::query()->whereNotIn('status', [OrderStatus::Delivered->value, OrderStatus::Cancelled->value, OrderStatus::Refunded->value]);
    }

    /** @return array<int, int> */
    public static function awaitingAdvanceIds(): array
    {
        return Order::query()->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Refunded->value])
            ->withSum(['payments' => fn ($query) => $query->where('status', 'succeeded')], 'amount_minor')
            ->get()->filter(fn (Order $order) => $order->getExpectedAdvanceAmount() > (int) $order->payments_sum_amount_minor)
            ->modelKeys();
    }
}
