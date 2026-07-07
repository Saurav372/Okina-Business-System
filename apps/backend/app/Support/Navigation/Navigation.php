<?php

namespace App\Support\Navigation;

class Navigation
{
    /**
     * Get the defined admin navigation structure.
     */
    public function items(): array
    {
        return [
            [
                'group' => 'Dashboard',
                'order' => 10,
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'admin.dashboard',
                        'icon' => 'home',
                        'order' => 10,
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Sales',
                'order' => 20,
                'items' => [
                    [
                        'label' => 'Sales Orders',
                        'route' => 'admin.sales_orders.create',
                        'icon' => 'shopping-cart',
                        'order' => 10,
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Products',
                'order' => 30,
                'items' => [],
            ],
            [
                'group' => 'Inventory',
                'order' => 40,
                'items' => [],
            ],
            [
                'group' => 'Purchase',
                'order' => 50,
                'items' => [
                    [
                        'label' => 'Purchase Orders',
                        'route' => 'admin.purchase_orders.index',
                        'icon' => 'truck',
                        'order' => 10,
                        'children' => [],
                    ],
                    [
                        'label' => 'Vendors',
                        'route' => 'admin.vendors.index',
                        'icon' => 'users',
                        'order' => 20,
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'CRM',
                'order' => 60,
                'items' => [
                    [
                        'label' => 'Leads',
                        'route' => 'admin.leads.index',
                        'icon' => 'user-plus',
                        'order' => 10,
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Customers',
                'order' => 70,
                'items' => [],
            ],
            [
                'group' => 'Accounting',
                'order' => 80,
                'items' => [
                    [
                        'label' => 'Payments',
                        'route' => 'admin.payments.index',
                        'icon' => 'credit-card',
                        'order' => 10,
                        'children' => [],
                    ],
                    [
                        'label' => 'Refunds',
                        'route' => 'admin.refunds.index',
                        'icon' => 'corner-down-left',
                        'order' => 20,
                        'children' => [],
                    ],
                    [
                        'label' => 'Expenses',
                        'route' => 'admin.expenses.index',
                        'icon' => 'file-text',
                        'order' => 30,
                        'children' => [],
                    ],
                    [
                        'label' => 'Expense Categories',
                        'route' => 'admin.expense_categories.index',
                        'icon' => 'tag',
                        'order' => 40,
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Reports',
                'order' => 90,
                'items' => [],
            ],
            [
                'group' => 'Users',
                'order' => 100,
                'items' => [],
            ],
            [
                'group' => 'Administration',
                'order' => 110,
                'items' => [
                    [
                        'label' => 'Audit Logs',
                        'route' => 'admin.audit_logs.index',
                        'icon' => 'clipboard',
                        'order' => 10,
                        'children' => [],
                    ],
                    [
                        'label' => 'Notification Logs',
                        'route' => 'admin.notification_logs.index',
                        'icon' => 'bell',
                        'order' => 20,
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Settings',
                'order' => 120,
                'items' => [
                    [
                        'label' => 'Google Sheets',
                        'route' => 'admin.google_sheets.sync_logs.index',
                        'icon' => 'database',
                        'order' => 10,
                        'children' => [],
                    ],
                ],
            ],
        ];
    }
}
