<?php

namespace App\Filament\Resources\Products;

use App\Models\Product;
use App\Models\User;
use App\Support\Admin\ProductDetailCatalog;
use App\Support\Admin\ProductIndexCatalog;

/**
 * A3.2.7 Admin catalog management — Product resource boundary.
 *
 * Full CRUD is permitted for Super Admin and Admin.
 * Inventory Staff can view only.
 * All other roles are denied.
 *
 * Finance-sensitive cost/profit data is never exposed here.
 */
final class ProductResource
{
    public static function registration(): array
    {
        return [
            'key' => 'products',
            'label' => 'Products',
            'model' => Product::class,
            'permission_view' => 'products.view',
            'permission_manage' => 'products.manage',
            'allowed_actions' => ['view', 'create', 'edit', 'delete'],
            'restricted_actions' => ['delete'],   // delete restricted to Super Admin only
            'blocked_actions' => ['forceDelete', 'finance', 'cost', 'profit'],
            'index' => (new ProductIndexCatalog)->definition(),
            'detail' => (new ProductDetailCatalog)->definition(),
            'pages' => [],
        ];
    }

    /**
     * Super Admin, Admin, and Inventory Staff can see the products section.
     */
    public static function canAccess(User $user): bool
    {
        return $user->hasPermissionTo(self::registration()['permission_view'])
            || $user->hasPermissionTo(self::registration()['permission_manage']);
    }

    /**
     * Super Admin and Admin can create/edit products.
     */
    public static function canManage(User $user): bool
    {
        return $user->hasPermissionTo(self::registration()['permission_manage']);
    }
}
