<?php

namespace Tests\Feature;

use App\Services\NotificationEventCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationEventCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_events_are_defined_for_the_approved_domain_workflow(): void
    {
        $catalog = app(NotificationEventCatalog::class);

        $this->assertSame([
            'orders.created',
            'payments.received',
            'quotations.sent',
            'quotations.approved',
            'orders.design_approval_requested',
            'production.started',
            'shipments.created',
            'orders.delivered',
            'payments.pending',
            'crm.follow_ups_due',
            'inventory.low_stock',
            'system.job_failed',
        ], $catalog->keys());

        $this->assertCount(12, $catalog->all());
    }

    public function test_notification_contracts_define_recipients_channels_retry_rules_and_templates(): void
    {
        $catalog = app(NotificationEventCatalog::class);
        $definition = $catalog->definition('orders.created');

        $this->assertNotNull($definition);
        $this->assertSame('Order Created', $definition->name);
        $this->assertSame(['customer', 'sales_staff', 'admin'], $definition->recipients);
        $this->assertSame(['mail', 'database'], $definition->channels);
        $this->assertSame(['notification.email_enabled', 'notification.order_emails_enabled'], $definition->channelSettings['mail']);
        $this->assertSame(3, $definition->retry['max_attempts']);
        $this->assertSame(1440, $definition->deduplication['window_minutes']);
        $this->assertSame('v1', $definition->template['version']);
        $this->assertContains('orders.id', $definition->references);
    }

    public function test_operational_notifications_use_staff_channels_and_retry_rules(): void
    {
        $catalog = app(NotificationEventCatalog::class);

        $lowStock = $catalog->definition('inventory.low_stock');
        $jobFailed = $catalog->definition('system.job_failed');

        $this->assertSame(['database', 'slack'], $lowStock->channels);
        $this->assertSame(['inventory_staff', 'admin'], $lowStock->recipients);
        $this->assertSame(5, $lowStock->retry['max_attempts']);
        $this->assertSame(['database', 'mail', 'slack'], $jobFailed->channels);
        $this->assertSame(['super_admin', 'admin'], $jobFailed->recipients);
        $this->assertSame('jobs:{job_fingerprint}:failed', $jobFailed->deduplication['key_pattern']);
    }
}
