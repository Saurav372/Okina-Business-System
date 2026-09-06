<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;

final class ProductPolicy
{
    /**
     * Super Admin and Admin may manage catalog records.
     * Inventory Staff may view (for stock/order reference) but not mutate.
     * All other roles cannot access catalog management screens.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::INVENTORY_STAFF,
        ]);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::INVENTORY_STAFF,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN]);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN]);
    }

    public function manageSeo(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('products.manage_seo')
            || $user->hasPermissionTo('products.manage')
            || $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN]);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN);
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN);
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
