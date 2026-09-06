<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorOrderItem;
use Illuminate\Contracts\Auth\Authenticatable;

class VendorOrderItemPolicy
{
    /**
     * Determine whether the user can create vendor order items.
     */
    public function create(Authenticatable $actor, VendorOrder $purchaseOrder): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can update the vendor order item.
     */
    public function update(Authenticatable $actor, VendorOrderItem $item): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the vendor order item.
     */
    public function delete(Authenticatable $actor, VendorOrderItem $item): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('purchases.manage');
        }

        return false;
    }
}
