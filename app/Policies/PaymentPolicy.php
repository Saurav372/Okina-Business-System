<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class PaymentPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.view');
        }

        return false;
    }

    public function view(Authenticatable $actor, Payment $payment): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.view');
        }

        return false;
    }

    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.record');
        }

        return false;
    }

    public function update(Authenticatable $actor, Payment $payment): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.edit');
        }

        return false;
    }

    public function delete(Authenticatable $actor, Payment $payment): bool
    {
        return false;
    }

    public function viewSensitive(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.view_cost');
        }

        return false;
    }
}
