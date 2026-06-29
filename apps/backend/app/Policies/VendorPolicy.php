<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\Auth\Authenticatable;

class VendorPolicy
{
    /**
     * Determine whether the user can view any vendors.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('vendors.view') || $actor->hasPermissionTo('vendors.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can view the vendor.
     */
    public function view(Authenticatable $actor, Vendor $vendor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('vendors.view') || $actor->hasPermissionTo('vendors.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can create vendors.
     */
    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('vendors.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can update the vendor.
     */
    public function update(Authenticatable $actor, Vendor $vendor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('vendors.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the vendor.
     */
    public function delete(Authenticatable $actor, Vendor $vendor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('vendors.manage');
        }

        return false;
    }
}
