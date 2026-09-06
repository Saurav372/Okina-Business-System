<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class OrderPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('orders.view');
        }

        return false;
    }

    public function view(Authenticatable $actor, Order $order): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('orders.view');
        }

        return false;
    }

    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('orders.manage');
        }

        return false;
    }

    public function update(Authenticatable $actor, Order $order): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('orders.manage');
        }

        return false;
    }

    public function delete(Authenticatable $actor, Order $order): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('orders.manage');
        }

        return false;
    }

    public function recordPayment(Authenticatable $actor, Order $order): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.record');
        }

        return false;
    }

    public function cancel(Authenticatable $actor, Order $order): bool
    {
        if (! ($actor instanceof User)) {
            return false;
        }

        $status = $order->status instanceof OrderStatus
            ? $order->status
            : OrderStatus::tryFrom((string) $order->status);

        return match ($status) {
            OrderStatus::PendingPayment, OrderStatus::Confirmed => $actor->hasPermissionTo('orders.cancel') || $actor->hasPermissionTo('orders.manage'),
            OrderStatus::InProduction => $actor->hasPermissionTo('orders.cancel_in_production'),
            OrderStatus::ReadyToShip => $actor->hasPermissionTo('orders.cancel_ready_to_ship'),
            default => false,
        };
    }
}
