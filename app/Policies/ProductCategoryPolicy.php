<?php

namespace App\Policies;

use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;

final class ProductCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::INVENTORY_STAFF,
        ]);
    }

    public function view(User $user, ProductCategory $category): bool
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

    public function update(User $user, ProductCategory $category): bool
    {
        return $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN]);
    }

    public function delete(User $user, ProductCategory $category): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN);
    }

    public function restore(User $user, ProductCategory $category): bool
    {
        return $user->hasRole(Role::SUPER_ADMIN);
    }

    public function forceDelete(User $user, ProductCategory $category): bool
    {
        return false;
    }
}
