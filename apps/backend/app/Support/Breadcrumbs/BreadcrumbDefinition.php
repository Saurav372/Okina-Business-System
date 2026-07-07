<?php

namespace App\Support\Breadcrumbs;

class BreadcrumbDefinition
{
    /**
     * Get the defined breadcrumb hierarchy.
     *
     * Keys should be the exact route name.
     *
     * Placeholders for dynamic models use dot-notation matching the route parameter
     * and the object property (e.g. '{order.number}' for $routeParameters['order']->number).
     *
     * @return array<string, array{parent: ?string, label: string, fallback?: string}>
     */
    public function items(): array
    {
        return [
            'admin.dashboard' => [
                'parent' => null,
                'label' => 'Dashboard',
            ],

            // Sales Orders
            'admin.sales_orders.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Sales Orders',
            ],
            'admin.sales_orders.create' => [
                'parent' => 'admin.sales_orders.index',
                'label' => 'Create Order',
            ],
            'admin.sales_orders.show' => [
                'parent' => 'admin.sales_orders.index',
                'label' => '{order.public_id}', // Assuming Okina uses public_id
                'fallback' => 'Order',
            ],

            // Purchase Orders
            'admin.purchase_orders.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Purchase Orders',
            ],

            // Vendors
            'admin.vendors.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Vendors',
            ],

            // Leads
            'admin.leads.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Leads',
            ],

            // Payments
            'admin.payments.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Payments',
            ],

            // Refunds
            'admin.refunds.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Refunds',
            ],

            // Expenses
            'admin.expenses.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Expenses',
            ],

            // Expense Categories
            'admin.expense_categories.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Expense Categories',
            ],

            // Audit Logs
            'admin.audit_logs.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Audit Logs',
            ],

            // Notification Logs
            'admin.notification_logs.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Notification Logs',
            ],

            // Google Sheets
            'admin.google_sheets.sync_logs.index' => [
                'parent' => 'admin.dashboard',
                'label' => 'Google Sheets',
            ],
        ];
    }
}
