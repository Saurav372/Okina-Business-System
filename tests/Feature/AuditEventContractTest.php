<?php

namespace Tests\Feature;

use App\Contracts\AuditEventContract;
use App\Support\Audit\AuditEventCatalog;
use App\Support\Audit\AuditEventDefinition;
use App\Support\Audit\AuditPayloadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditEventContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_event_definition_implements_the_shared_contract(): void
    {
        $definition = app(AuditEventCatalog::class)->definition('orders.order_created');

        $this->assertInstanceOf(AuditEventContract::class, $definition);
        $this->assertSame(AuditEventDefinition::class, $definition::class);
    }

    public function test_audit_event_catalog_defines_the_core_module_guidance(): void
    {
        $catalog = app(AuditEventCatalog::class);

        $this->assertSame(
            [
                'orders.order_created',
                'payments.payment_recorded',
                'orders.order_cancelled',
                'refunds.refund_requested',
                'refunds.refund_approved',
                'refunds.refund_processing_started',
                'refunds.refund_processing_succeeded',
                'refunds.refund_processing_failed',
                'refunds.refund_cancelled',
                'refunds.refund_recorded',
                'inventory.stock_moved',
                'finance.expense_recorded',
                'orders.order_edited',
                'customers.customer_updated',
                'products.product_updated',
                'products.sku_updated',
                'products.seo_updated',
                'users.role_assigned',
                'users.permission_updated',
                'orders.pdf_generated',
            ],
            $catalog->keys(),
        );

        $this->assertSame('orders', $catalog->definition('orders.pdf_generated')->module);
        $this->assertSame('orders', $catalog->definition('orders.order_created')->module);
        $this->assertSame('payments', $catalog->definition('payments.payment_recorded')->module);
        $this->assertSame('orders', $catalog->definition('orders.order_cancelled')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_requested')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_approved')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_processing_started')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_processing_succeeded')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_processing_failed')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_cancelled')->module);
        $this->assertSame('finance', $catalog->definition('refunds.refund_recorded')->module);
        $this->assertSame('inventory', $catalog->definition('inventory.stock_moved')->module);
        $this->assertSame('finance', $catalog->definition('finance.expense_recorded')->module);
        $this->assertSame('orders', $catalog->definition('orders.order_edited')->module);
        // C6.1.3 additions
        $this->assertSame('customers', $catalog->definition('customers.customer_updated')->module);
        $this->assertSame('products', $catalog->definition('products.product_updated')->module);
        $this->assertSame('products', $catalog->definition('products.sku_updated')->module);
        $this->assertSame('products', $catalog->definition('products.seo_updated')->module);
        $this->assertSame('users', $catalog->definition('users.role_assigned')->module);
        $this->assertSame('users', $catalog->definition('users.permission_updated')->module);
    }

    public function test_audit_event_definitions_expose_safe_payload_shape_and_masked_fields(): void
    {
        $definition = app(AuditEventCatalog::class)->definition('payments.payment_recorded');

        $this->assertNotNull($definition);
        $this->assertSame(
            [
                'key' => 'payments.payment_recorded',
                'module' => 'payments',
                'action' => 'payment.recorded',
                'subject_type' => 'payment',
                'actor_types' => ['user', 'system', 'job', 'provider'],
                'safe_fields' => ['payment_public_id', 'order_public_id', 'payment_status', 'amount', 'currency', 'provider', 'attempt_public_id'],
                'masked_fields' => ['gateway_payload', 'payment_credentials', 'card_number', 'card_cvv', 'token', 'raw_payload'],
                'related_types' => ['order', 'payment_attempt', 'refund'],
                'references' => ['A5.1.3', 'A5.3.4', 'B3.3.4'],
                'summary' => 'Payment record written or updated from a verified payment source.',
            ],
            $definition->toArray(),
        );
    }

    public function test_refund_and_cancellation_audit_events_expose_safe_finance_and_order_fields(): void
    {
        $catalog = app(AuditEventCatalog::class);

        $cancelled = $catalog->definition('orders.order_cancelled');
        $requested = $catalog->definition('refunds.refund_requested');
        $recorded = $catalog->definition('refunds.refund_recorded');

        $this->assertNotNull($cancelled);
        $this->assertNotNull($requested);
        $this->assertNotNull($recorded);

        $this->assertSame(
            [
                'key' => 'orders.order_cancelled',
                'module' => 'orders',
                'action' => 'order.cancelled',
                'subject_type' => 'order',
                'actor_types' => ['user', 'system', 'job'],
                'safe_fields' => ['order_public_id', 'order_type', 'customer_public_id', 'previous_status', 'status', 'cancelled_at', 'cancellation_reason_code'],
                'masked_fields' => ['cancellation_note', 'internal_reason', 'payment_reference', 'gateway_payload', 'raw_payload'],
                'related_types' => ['order', 'payment', 'refund', 'tracking_event'],
                'references' => ['A5.2.1', 'A5.2.2', 'C1.1', 'B4.2'],
                'summary' => 'Order cancellation was recorded with safe state and reason-code details only.',
            ],
            $cancelled->toArray(),
        );

        $this->assertSame(
            [
                'key' => 'refunds.refund_requested',
                'module' => 'finance',
                'action' => 'refund.requested',
                'subject_type' => 'refund',
                'actor_types' => ['user', 'system'],
                'safe_fields' => ['refund_public_id', 'order_public_id', 'payment_public_id', 'refund_type', 'status', 'amount', 'currency', 'reason_code'],
                'masked_fields' => ['reason_note', 'provider_reference', 'provider_refund_id', 'payment_credentials', 'raw_payload'],
                'related_types' => ['order', 'payment', 'payment_attempt'],
                'references' => ['A5.2.3', 'A5.2.4', 'C5.2.1'],
                'summary' => 'Refund request was created without exposing sensitive payment or provider data.',
            ],
            $requested->toArray(),
        );

        $this->assertSame(
            [
                'key' => 'refunds.refund_recorded',
                'module' => 'finance',
                'action' => 'refund.recorded',
                'subject_type' => 'refund',
                'actor_types' => ['user', 'system', 'job', 'provider'],
                'safe_fields' => ['refund_public_id', 'order_public_id', 'payment_public_id', 'refund_type', 'status', 'amount', 'currency', 'provider', 'provider_refund_id', 'processed_at'],
                'masked_fields' => ['provider_reference', 'reason_note', 'payment_credentials', 'gateway_payload', 'raw_payload'],
                'related_types' => ['order', 'payment', 'payment_attempt'],
                'references' => ['A5.2.3', 'A5.2.4', 'C5.2.3', 'C5.2.4', 'C5.2.5'],
                'summary' => 'Refund was recorded or synchronized from a verified internal or provider source.',
            ],
            $recorded->toArray(),
        );
    }

    public function test_sensitive_audit_payload_values_are_redacted_recursively(): void
    {
        $policy = app(AuditPayloadPolicy::class);

        $sanitized = $policy->sanitize([
            'order_public_id' => 'ord_123',
            'gateway_payload' => [
                'token' => 'secret-token',
                'card_number' => '4111111111111111',
                'details' => [
                    'password' => 'top-secret',
                    'note' => 'safe note',
                ],
            ],
            'metadata' => [
                'raw_payload' => 'unsafe body',
                'customer_name' => 'Alex',
            ],
        ]);

        $this->assertSame('ord_123', $sanitized['order_public_id']);
        $this->assertSame('[redacted]', $sanitized['gateway_payload']);
        $this->assertSame('[redacted]', $sanitized['metadata']['raw_payload']);
        $this->assertSame('Alex', $sanitized['metadata']['customer_name']);
        $this->assertArrayNotHasKey('token', $sanitized);
    }
}
