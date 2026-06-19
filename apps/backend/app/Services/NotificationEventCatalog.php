<?php

namespace App\Services;

use App\Support\Notifications\NotificationEventDefinition;

class NotificationEventCatalog
{
    /**
     * @return array<int, NotificationEventDefinition>
     */
    public function all(): array
    {
        return [
            new NotificationEventDefinition(
                key: 'orders.created',
                name: 'Order Created',
                trigger: 'orders.created',
                recipients: ['customer', 'sales_staff', 'admin'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.order_emails_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:created', 1440),
                template: $this->templatePolicy(
                    'notifications.orders.created',
                    ['order_public_id', 'customer_name', 'order_total', 'currency'],
                ),
                references: ['orders.id', 'customers.id', 'order_items.id'],
            ),
            new NotificationEventDefinition(
                key: 'payments.received',
                name: 'Payment Received',
                trigger: 'payments.received',
                recipients: ['customer', 'finance_staff', 'admin'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.payment_emails_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:payment_received', 1440),
                template: $this->templatePolicy(
                    'notifications.payments.received',
                    ['order_public_id', 'payment_public_id', 'paid_amount', 'currency'],
                ),
                references: ['orders.id', 'payments.id'],
            ),
            new NotificationEventDefinition(
                key: 'quotations.sent',
                name: 'Quotation Sent',
                trigger: 'quotations.sent',
                recipients: ['customer', 'sales_staff'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('quotations:{quotation_public_id}:sent', 1440),
                template: $this->templatePolicy(
                    'notifications.quotations.sent',
                    ['quotation_public_id', 'customer_name', 'quotation_total', 'currency'],
                ),
                references: ['quotations.id', 'quotation_items.id'],
            ),
            new NotificationEventDefinition(
                key: 'quotations.approved',
                name: 'Quotation Approved',
                trigger: 'quotations.approved',
                recipients: ['sales_staff', 'admin'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.admin_alerts_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('quotations:{quotation_public_id}:approved', 1440),
                template: $this->templatePolicy(
                    'notifications.quotations.approved',
                    ['quotation_public_id', 'customer_name', 'sales_order_public_id'],
                ),
                references: ['quotations.id', 'orders.id'],
            ),
            new NotificationEventDefinition(
                key: 'orders.design_approval_requested',
                name: 'Design Approval Requested',
                trigger: 'orders.design_approval_requested',
                recipients: ['customer'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.order_emails_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:design_approval_requested', 1440),
                template: $this->templatePolicy(
                    'notifications.orders.design_approval_requested',
                    ['order_public_id', 'design_notes', 'approval_url'],
                ),
                references: ['orders.id'],
            ),
            new NotificationEventDefinition(
                key: 'production.started',
                name: 'Production Started',
                trigger: 'production.started',
                recipients: ['production_staff', 'admin'],
                channels: ['database', 'slack'],
                channelSettings: [
                    'database' => [],
                    'slack' => ['notification.slack_notifications_enabled', 'notification.admin_alerts_enabled'],
                ],
                retry: $this->retryPolicy(5, [60, 300, 900, 3600], 360),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:production_started', 720),
                template: $this->templatePolicy(
                    'notifications.production.started',
                    ['order_public_id', 'production_notes'],
                ),
                references: ['orders.id'],
            ),
            new NotificationEventDefinition(
                key: 'shipments.created',
                name: 'Shipment Created',
                trigger: 'shipments.created',
                recipients: ['customer', 'admin'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.order_emails_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(5, [60, 300, 900, 3600], 360),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:shipment_created', 720),
                template: $this->templatePolicy(
                    'notifications.shipments.created',
                    ['order_public_id', 'courier_name', 'tracking_number', 'tracking_url'],
                ),
                references: ['orders.id', 'shipments.id'],
            ),
            new NotificationEventDefinition(
                key: 'orders.delivered',
                name: 'Order Delivered',
                trigger: 'orders.delivered',
                recipients: ['customer'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.order_emails_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:delivered', 1440),
                template: $this->templatePolicy(
                    'notifications.orders.delivered',
                    ['order_public_id', 'delivered_at'],
                ),
                references: ['orders.id', 'shipments.id'],
            ),
            new NotificationEventDefinition(
                key: 'payments.pending',
                name: 'Payment Pending',
                trigger: 'payments.pending',
                recipients: ['customer', 'sales_staff', 'finance_staff'],
                channels: ['mail', 'database'],
                channelSettings: [
                    'mail' => ['notification.email_enabled', 'notification.payment_emails_enabled'],
                    'database' => [],
                ],
                retry: $this->retryPolicy(3, [60, 300, 900], 120),
                deduplication: $this->dedupePolicy('orders:{order_public_id}:payment_pending', 360),
                template: $this->templatePolicy(
                    'notifications.payments.pending',
                    ['order_public_id', 'payment_due_date', 'balance_amount', 'currency'],
                ),
                references: ['orders.id', 'payments.id'],
            ),
            new NotificationEventDefinition(
                key: 'crm.follow_ups_due',
                name: 'Follow-up Due',
                trigger: 'crm.follow_ups_due',
                recipients: ['sales_staff'],
                channels: ['database', 'slack'],
                channelSettings: [
                    'database' => [],
                    'slack' => ['notification.slack_notifications_enabled', 'notification.admin_alerts_enabled'],
                ],
                retry: $this->retryPolicy(5, [60, 300, 900, 3600], 360),
                deduplication: $this->dedupePolicy('lead_follow_ups:{follow_up_public_id}:due', 240),
                template: $this->templatePolicy(
                    'notifications.crm.follow_ups_due',
                    ['lead_public_id', 'follow_up_public_id', 'due_at', 'customer_name'],
                ),
                references: ['leads.id', 'lead_follow_ups.id'],
            ),
            new NotificationEventDefinition(
                key: 'inventory.low_stock',
                name: 'Low Stock',
                trigger: 'inventory.low_stock',
                recipients: ['inventory_staff', 'admin'],
                channels: ['database', 'slack'],
                channelSettings: [
                    'database' => [],
                    'slack' => ['notification.slack_notifications_enabled', 'notification.admin_alerts_enabled'],
                ],
                retry: $this->retryPolicy(5, [60, 300, 900, 3600], 360),
                deduplication: $this->dedupePolicy('product_skus:{sku_public_id}:low_stock', 360),
                template: $this->templatePolicy(
                    'notifications.inventory.low_stock',
                    ['sku_public_id', 'product_name', 'current_stock', 'threshold'],
                ),
                references: ['product_skus.id', 'products.id'],
            ),
            new NotificationEventDefinition(
                key: 'system.job_failed',
                name: 'Job Failed',
                trigger: 'system.job_failed',
                recipients: ['super_admin', 'admin'],
                channels: ['database', 'mail', 'slack'],
                channelSettings: [
                    'database' => [],
                    'mail' => ['notification.email_enabled', 'notification.admin_alerts_enabled'],
                    'slack' => ['notification.slack_notifications_enabled', 'notification.admin_alerts_enabled'],
                ],
                retry: $this->retryPolicy(1, [300], 60),
                deduplication: $this->dedupePolicy('jobs:{job_fingerprint}:failed', 60),
                template: $this->templatePolicy(
                    'notifications.system.job_failed',
                    ['job_name', 'failed_at', 'exception_class', 'queue_name'],
                ),
                references: ['jobs.id', 'failed_jobs.id'],
            ),
        ];
    }

    public function keys(): array
    {
        return array_map(static fn (NotificationEventDefinition $definition): string => $definition->key, $this->all());
    }

    public function definition(string $key): ?NotificationEventDefinition
    {
        foreach ($this->all() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function retryPolicy(int $maxAttempts, array $backoffSeconds, int $stopAfterMinutes): array
    {
        return [
            'enabled' => true,
            'max_attempts' => $maxAttempts,
            'backoff_seconds' => array_values($backoffSeconds),
            'stop_after_minutes' => $stopAfterMinutes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dedupePolicy(string $keyPattern, int $windowMinutes): array
    {
        return [
            'enabled' => true,
            'key_pattern' => $keyPattern,
            'window_minutes' => $windowMinutes,
        ];
    }

    /**
     * @param  array<int, string>  $requiredVariables
     * @return array<string, mixed>
     */
    private function templatePolicy(string $templateKey, array $requiredVariables): array
    {
        return [
            'key' => $templateKey,
            'version' => 'v1',
            'required_variables' => array_values($requiredVariables),
            'summary_only' => false,
        ];
    }
}
