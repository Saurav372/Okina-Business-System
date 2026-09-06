<?php

namespace App\Filament\Resources\Products;

use App\Models\ProductCategory;
use App\Models\User;
use App\Support\Admin\CategoryDetailCatalog;
use App\Support\Admin\CategoryIndexCatalog;

/**
 * A3.2.7 Admin catalog management — ProductCategory resource boundary.
 *
 * Super Admin and Admin can fully manage categories.
 * Inventory Staff can view categories (needed for stock/product reference).
 * All other roles are denied.
 */
final class ProductCategoryResource
{
    public static function registration(): array
    {
        return [
            'key' => 'product_categories',
            'label' => 'Categories',
            'model' => ProductCategory::class,
            'permission_view' => 'product_categories.view',
            'permission_manage' => 'product_categories.manage',
            'allowed_actions' => ['view', 'create', 'edit', 'delete'],
            'restricted_actions' => ['delete'],  // delete restricted to Super Admin only
            'blocked_actions' => ['forceDelete'],
            'index' => (new CategoryIndexCatalog)->definition(),
            'detail' => (new CategoryDetailCatalog)->definition(),
            'pages' => [],
        ];
    }

    public static function canAccess(User $user): bool
    {
        return $user->hasPermissionTo(self::registration()['permission_view'])
            || $user->hasPermissionTo(self::registration()['permission_manage']);
    }

    public static function canManage(User $user): bool
    {
        return $user->hasPermissionTo(self::registration()['permission_manage']);
    }
}
