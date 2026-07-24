<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class RefundPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.view');
        }

        return false;
    }

    public function view(Authenticatable $actor, Refund $refund): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('payments.view');
        }

        return false;
    }

    public function viewSensitive(Authenticatable $actor, Refund $refund): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.view_cost');
        }

        return false;
    }

    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('refunds.request');
        }

        return false;
    }

    public function approve(Authenticatable $actor, Refund $refund): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('refunds.approve');
        }

        return false;
    }

    public function process(Authenticatable $actor, Refund $refund): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('refunds.approve');
        }

        return false;
    }

    public function retry(Authenticatable $actor, Refund $refund): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('refunds.approve');
        }

        return false;
    }

    public function cancel(Authenticatable $actor, Refund $refund): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('refunds.request') || $actor->hasPermissionTo('refunds.approve');
        }

        return false;
    }

    public function update(Authenticatable $actor, Refund $refund): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, Refund $refund): bool
    {
        return false;
    }
}
