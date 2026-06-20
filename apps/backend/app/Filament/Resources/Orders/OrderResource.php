<?php

namespace App\Filament\Resources\Orders;

use App\Models\Order;
use App\Models\User;

final class OrderResource
{
    /**
     * Keep the resource boundary read-only until later subtasks add the order index and detail pages.
     */
    public static function registration(): array
    {
        return [
            'key' => 'orders',
            'label' => 'Orders',
            'model' => Order::class,
            'permission' => 'orders.view',
            'read_only' => true,
            'allowed_actions' => ['view'],
            'blocked_actions' => [
                'create',
                'edit',
                'delete',
                'forceDelete',
                'restore',
                'replicate',
                'status',
                'payment',
                'refund',
                'shipping',
            ],
            'pages' => [],
        ];
    }

    public static function canAccess(User $user): bool
    {
        return $user->hasPermissionTo(self::registration()['permission']);
    }
}
