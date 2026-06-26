<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Dashboard Access', 'slug' => 'dashboard.access', 'group' => 'settings', 'is_sensitive' => false],
            ['name' => 'View Staff', 'slug' => 'users.view', 'group' => 'users', 'is_sensitive' => false],
            ['name' => 'Manage Staff', 'slug' => 'users.manage_staff', 'group' => 'users', 'is_sensitive' => true],
            ['name' => 'Manage Roles', 'slug' => 'users.manage_roles', 'group' => 'roles', 'is_sensitive' => true],
            ['name' => 'View Customers', 'slug' => 'customers.view', 'group' => 'customers', 'is_sensitive' => false],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'group' => 'customers', 'is_sensitive' => false],
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'products', 'is_sensitive' => false],
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'group' => 'products', 'is_sensitive' => false],
            ['name' => 'View Orders', 'slug' => 'orders.view', 'group' => 'orders', 'is_sensitive' => false],
            ['name' => 'Update Orders', 'slug' => 'orders.update_status', 'group' => 'orders', 'is_sensitive' => false],
            ['name' => 'Delete Orders', 'slug' => 'orders.delete', 'group' => 'orders', 'is_sensitive' => true],
            ['name' => 'View Quotations', 'slug' => 'quotations.view', 'group' => 'quotations', 'is_sensitive' => false],
            ['name' => 'Manage Quotations', 'slug' => 'quotations.manage', 'group' => 'quotations', 'is_sensitive' => false],
            ['name' => 'View Payments', 'slug' => 'payments.view', 'group' => 'payments', 'is_sensitive' => false],
            ['name' => 'Record Payments', 'slug' => 'payments.record', 'group' => 'payments', 'is_sensitive' => true],
            ['name' => 'Edit Payments', 'slug' => 'payments.edit', 'group' => 'payments', 'is_sensitive' => true],
            ['name' => 'Approve Refunds', 'slug' => 'refunds.approve', 'group' => 'refunds', 'is_sensitive' => true],
            ['name' => 'Request Refunds', 'slug' => 'refunds.request', 'group' => 'refunds', 'is_sensitive' => true],
            ['name' => 'View Inventory', 'slug' => 'inventory.view', 'group' => 'inventory', 'is_sensitive' => false],
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'group' => 'inventory', 'is_sensitive' => false],
            ['name' => 'Adjust Inventory', 'slug' => 'inventory.adjust', 'group' => 'inventory', 'is_sensitive' => true],
            ['name' => 'View Finance Cost', 'slug' => 'finance.view_cost', 'group' => 'finance', 'is_sensitive' => true],
            ['name' => 'View Finance Profit', 'slug' => 'finance.view_profit', 'group' => 'finance', 'is_sensitive' => true],
            ['name' => 'Manage Expenses', 'slug' => 'finance.manage_expenses', 'group' => 'finance', 'is_sensitive' => true],
            ['name' => 'Approve Expenses', 'slug' => 'finance.approve_expenses', 'group' => 'finance', 'is_sensitive' => true],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports', 'is_sensitive' => false],
            ['name' => 'View Audit', 'slug' => 'audit.view', 'group' => 'audit', 'is_sensitive' => true],
            ['name' => 'Manage Production', 'slug' => 'production.manage', 'group' => 'production', 'is_sensitive' => false],
            ['name' => 'Manage Files', 'slug' => 'files.download_private', 'group' => 'files', 'is_sensitive' => true],
            ['name' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings', 'is_sensitive' => true],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                    'guard_name' => 'web',
                    'description' => $permission['name'],
                    'is_sensitive' => $permission['is_sensitive'],
                ],
            );
        }

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => Role::SUPER_ADMIN,
                'sort_order' => 10,
                'is_system' => true,
                'permissions' => array_column($permissions, 'slug'),
            ],
            [
                'name' => 'Admin',
                'slug' => Role::ADMIN,
                'sort_order' => 20,
                'is_system' => true,
                'permissions' => [
                    'dashboard.access',
                    'users.view',
                    'users.manage_staff',
                    'customers.view',
                    'customers.manage',
                    'products.view',
                    'products.manage',
                    'orders.view',
                    'orders.update_status',
                    'quotations.view',
                    'quotations.manage',
                    'production.manage',
                    'reports.view',
                    'finance.manage_expenses',
                    'finance.approve_expenses',
                ],
            ],
            [
                'name' => 'Sales Staff',
                'slug' => Role::SALES_STAFF,
                'sort_order' => 30,
                'is_system' => true,
                'permissions' => [
                    'dashboard.access',
                    'customers.view',
                    'customers.manage',
                    'orders.view',
                    'orders.update_status',
                    'quotations.view',
                    'quotations.manage',
                    'payments.record',
                ],
            ],
            [
                'name' => 'Inventory Staff',
                'slug' => Role::INVENTORY_STAFF,
                'sort_order' => 40,
                'is_system' => true,
                'permissions' => [
                    'dashboard.access',
                    'products.view',
                    'inventory.view',
                    'inventory.manage',
                    'inventory.adjust',
                    'orders.view',
                    'files.download_private',
                ],
            ],
            [
                'name' => 'Finance Staff',
                'slug' => Role::FINANCE_STAFF,
                'sort_order' => 50,
                'is_system' => true,
                'permissions' => [
                    'dashboard.access',
                    'orders.view',
                    'payments.view',
                    'payments.record',
                    'payments.edit',
                    'refunds.approve',
                    'refunds.request',
                    'finance.view_cost',
                    'finance.view_profit',
                    'reports.view',
                    'audit.view',
                    'finance.manage_expenses',
                    'finance.approve_expenses',
                ],
            ],
            [
                'name' => 'Production Staff',
                'slug' => Role::PRODUCTION_STAFF,
                'sort_order' => 60,
                'is_system' => true,
                'permissions' => [
                    'dashboard.access',
                    'orders.view',
                    'production.manage',
                    'files.download_private',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                    'description' => $roleData['name'],
                    'is_system' => $roleData['is_system'],
                    'sort_order' => $roleData['sort_order'],
                ],
            );

            $permissionIds = Permission::query()
                ->whereIn('slug', $roleData['permissions'])
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
