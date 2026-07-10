<?php

namespace App\Policies;

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
}
