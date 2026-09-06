<?php

namespace App\Support\Navigation;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class Navigation
{
    /**
     * Get the defined admin navigation structure filtered for the user.
     *
     * @return array<NavigationGroup>
     */
    public function forUser(?User $user): array
    {
        $rawStructure = $this->rawItems();
        $filteredGroups = [];

        foreach ($rawStructure as $groupConfig) {
            $groupItems = [];
            foreach ($groupConfig['items'] as $itemConfig) {
                // If item has a permission constraint, verify the user has access via Gate
                if ($user && $itemConfig['permission'] && ! Gate::forUser($user)->allows($itemConfig['permission'])) {
                    continue;
                }

                // Sub-items/children check
                $children = [];
                if (! empty($itemConfig['children'])) {
                    foreach ($itemConfig['children'] as $childConfig) {
                        if ($user && $childConfig['permission'] && ! Gate::forUser($user)->allows($childConfig['permission'])) {
                            continue;
                        }
                        $children[] = new NavigationItem(
                            label: $childConfig['label'],
                            route: $childConfig['route'],
                            icon: $childConfig['icon'],
                            order: $childConfig['order'] ?? 10,
                            active: $childConfig['active'] ?? [],
                            badge: $childConfig['badge'] ?? null,
                            permission: $childConfig['permission'] ?? null
                        );
                    }
                }

                $groupItems[] = new NavigationItem(
                    label: $itemConfig['label'],
                    route: $itemConfig['route'],
                    icon: $itemConfig['icon'],
                    order: $itemConfig['order'] ?? 10,
                    active: $itemConfig['active'] ?? [],
                    badge: $itemConfig['badge'] ?? null,
                    permission: $itemConfig['permission'] ?? null,
                    children: $children
                );
            }

            // Hide empty groups
            if (! empty($groupItems)) {
                // Sort items by order
                usort($groupItems, fn ($a, $b) => $a->order <=> $b->order);
                $filteredGroups[] = new NavigationGroup(
                    group: $groupConfig['group'],
                    order: $groupConfig['order'],
                    items: $groupItems
                );
            }
        }

        // Sort groups by order
        usort($filteredGroups, fn ($a, $b) => $a->order <=> $b->order);

        return $filteredGroups;
    }

    /**
     * Raw navigation definition.
     * Sorted by usage frequency: Dashboard, Orders, Customers, Products, Inventory, CRM, Accounting, Administration, Settings.
     */
    protected function rawItems(): array
    {
        return [
            [
                'group' => 'Dashboard',
                'order' => 10,
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'admin.dashboard',
                        'icon' => 'lucide-home',
                        'order' => 10,
                        'permission' => null,
                        'active' => ['admin.dashboard'],
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
                        'route' => 'admin.orders.index',
                        'icon' => 'lucide-shopping-cart',
                        'order' => 10,
                        'permission' => 'orders.view',
                        'active' => ['admin.sales_orders.*', 'admin.orders.*'],
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Products',
                'order' => 30,
                'items' => [
                    [
                        'label' => 'Products',
                        'route' => 'admin.products.index',
                        'icon' => 'lucide-tag',
                        'order' => 10,
                        'permission' => 'products.view',
                        'active' => ['admin.products.*'],
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Inventory',
                'order' => 40,
                'items' => [
                    [
                        'label' => 'Stock Balances',
                        'route' => 'admin.inventory.index',
                        'icon' => 'lucide-boxes',
                        'order' => 10,
                        'permission' => 'inventory.view',
                        'active' => ['admin.inventory.*'],
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Purchase',
                'order' => 50,
                'items' => [
                    [
                        'label' => 'Purchase Orders',
                        'route' => 'admin.purchase_orders.index',
                        'icon' => 'lucide-truck',
                        'order' => 10,
                        'permission' => 'purchase_orders.view',
                        'active' => ['admin.purchase_orders.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Vendors',
                        'route' => 'admin.vendors.index',
                        'icon' => 'lucide-users',
                        'order' => 20,
                        'permission' => 'vendors.view',
                        'active' => ['admin.vendors.*'],
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
                        'icon' => 'lucide-credit-card',
                        'order' => 10,
                        'permission' => 'payments.view',
                        'active' => ['admin.payments.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Refunds',
                        'route' => 'admin.refunds.index',
                        'icon' => 'lucide-corner-down-left',
                        'order' => 20,
                        'permission' => 'refunds.view',
                        'active' => ['admin.refunds.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Expenses',
                        'route' => 'admin.expenses.index',
                        'icon' => 'lucide-file-text',
                        'order' => 30,
                        'permission' => 'expenses.view',
                        'active' => ['admin.expenses.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Expense Categories',
                        'route' => 'admin.expense_categories.index',
                        'icon' => 'lucide-tag',
                        'order' => 40,
                        'permission' => 'expense_categories.view',
                        'active' => ['admin.expense_categories.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Customer Ledger',
                        'route' => 'admin.accounting.customer_ledger',
                        'icon' => 'lucide-users',
                        'order' => 50,
                        'permission' => 'finance.view_ledgers',
                        'active' => ['admin.accounting.customer_ledger'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Vendor Ledger',
                        'route' => 'admin.accounting.vendor_ledger',
                        'icon' => 'lucide-truck',
                        'order' => 60,
                        'permission' => 'finance.view_ledgers',
                        'active' => ['admin.accounting.vendor_ledger'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Business Ledger',
                        'route' => 'admin.accounting.business_ledger',
                        'icon' => 'lucide-book-open',
                        'order' => 70,
                        'permission' => 'finance.view_ledgers',
                        'active' => ['admin.accounting.business_ledger'],
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Reports',
                'order' => 90,
                'items' => [
                    [
                        'label' => 'Finance Reports',
                        'route' => 'admin.reports.finance.index',
                        'icon' => 'lucide-bar-chart-3',
                        'order' => 10,
                        'permission' => 'reports.finance.view',
                        'active' => ['admin.reports.finance.*'],
                        'children' => [],
                    ],
                ],
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
                        'icon' => 'lucide-clipboard',
                        'order' => 10,
                        'permission' => 'audit.view',
                        'active' => ['admin.audit_logs.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Notification Logs',
                        'route' => 'admin.notification_logs.index',
                        'icon' => 'lucide-bell',
                        'order' => 20,
                        'permission' => 'notifications.view',
                        'active' => ['admin.notification_logs.*'],
                        'children' => [],
                    ],
                    [
                        'label' => 'System Health',
                        'route' => 'admin.system_health.index',
                        'icon' => 'lucide-activity',
                        'order' => 30,
                        'permission' => 'system.health.view',
                        'active' => ['admin.system_health.*'],
                        'children' => [],
                    ],
                ],
            ],
            [
                'group' => 'Account',
                'order' => 115,
                'items' => [
                    [
                        'label' => 'My Profile',
                        'route' => 'admin.profile',
                        'icon' => 'lucide-user',
                        'order' => 10,
                        'permission' => null,
                        'active' => ['admin.profile'],
                        'children' => [],
                    ],
                    [
                        'label' => 'Security Settings',
                        'route' => 'admin.security',
                        'icon' => 'lucide-shield-check',
                        'order' => 20,
                        'permission' => null,
                        'active' => ['admin.security'],
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
                        'icon' => 'lucide-database',
                        'order' => 10,
                        'permission' => 'sheets.view',
                        'active' => ['admin.google_sheets.*'],
                        'children' => [],
                    ],
                ],
            ],
        ];
    }
}
