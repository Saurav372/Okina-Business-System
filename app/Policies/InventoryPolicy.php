<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class InventoryPolicy
{
    public function view(User $user): bool
    {
        return $user->hasPermissionTo('inventory.view')
            || $user->hasPermissionTo('inventory.manage')
            || $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN, Role::INVENTORY_STAFF]);
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('inventory.manage')
            || $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN, Role::INVENTORY_STAFF]);
    }
}
