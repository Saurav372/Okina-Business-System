<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Contracts\Auth\Authenticatable;

class VendorOrderPolicy
{
    /**
     * Determine whether the user can view any vendor orders.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.view') || $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can view the vendor order.
     */
    public function view(Authenticatable $actor, VendorOrder $vendorOrder): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.view') || $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can create vendor orders.
     */
    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can update the vendor order.
     */
    public function update(Authenticatable $actor, VendorOrder $vendorOrder): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the vendor order.
     */
    public function delete(Authenticatable $actor, VendorOrder $vendorOrder): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }
}
