<?php

namespace App\Support\Audit;

final class AuditEventCatalog
{
    /**
     * @return array<int, AuditEventDefinition>
     */
    public function definitions(): array
    {
        return [
            new AuditEventDefinition(
                key: 'orders.order_created',
                module: 'orders',
                action: 'order.created',
                subjectType: 'order',
                actorTypes: ['customer', 'user', 'system', 'job'],
                safeFields: ['order_public_id', 'order_type', 'customer_public_id', 'status', 'payment_status', 'total_amount', 'currency'],
                maskedFields: ['customer_email', 'customer_phone', 'payment_reference', 'gateway_payload', 'password', 'token'],
                relatedTypes: ['customer', 'payment', 'order_item'],
                references: ['A5.1.5', 'B3.1.6', 'C1.1'],
                summary: 'Order created and ready for payment or staff handling.',
            ),
            new AuditEventDefinition(
                key: 'payments.payment_recorded',
                module: 'payments',
                action: 'payment.recorded',
                subjectType: 'payment',
                actorTypes: ['user', 'system', 'job', 'provider'],
                safeFields: ['payment_public_id', 'order_public_id', 'payment_status', 'amount', 'currency', 'provider', 'attempt_public_id'],
                maskedFields: ['gateway_payload', 'payment_credentials', 'card_number', 'card_cvv', 'token', 'raw_payload'],
                relatedTypes: ['order', 'payment_attempt', 'refund'],
                references: ['A5.1.3', 'A5.3.4', 'B3.3.4'],
                summary: 'Payment record written or updated from a verified payment source.',
            ),
            new AuditEventDefinition(
                key: 'orders.order_cancelled',
                module: 'orders',
                action: 'order.cancelled',
                subjectType: 'order',
                actorTypes: ['user', 'system', 'job'],
                safeFields: ['order_public_id', 'order_type', 'customer_public_id', 'previous_status', 'status', 'cancelled_at', 'cancellation_reason_code'],
                maskedFields: ['cancellation_note', 'internal_reason', 'payment_reference', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment', 'refund', 'tracking_event'],
                references: ['A5.2.1', 'A5.2.2', 'C1.1', 'B4.2'],
                summary: 'Order cancellation was recorded with safe state and reason-code details only.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_requested',
                module: 'finance',
                action: 'refund.requested',
                subjectType: 'refund',
                actorTypes: ['user', 'system'],
                safeFields: ['refund_public_id', 'order_public_id', 'payment_public_id', 'refund_type', 'status', 'amount', 'currency', 'reason_code'],
                maskedFields: ['reason_note', 'provider_reference', 'provider_refund_id', 'payment_credentials', 'raw_payload'],
                relatedTypes: ['order', 'payment', 'payment_attempt'],
                references: ['A5.2.3', 'A5.2.4', 'C5.2.1'],
                summary: 'Refund request was created without exposing sensitive payment or provider data.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_approved',
                module: 'finance',
                action: 'refund.approved',
                subjectType: 'refund',
                actorTypes: ['user', 'system'],
                safeFields: ['refund_public_id', 'order_public_id', 'payment_public_id', 'status', 'approved_by_user_id', 'approved_at', 'old_status', 'new_status', 'actor_type', 'actor_id', 'occurred_at'],
                maskedFields: ['reason_note', 'provider_reference', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment'],
                references: ['C5.2.2'],
                summary: 'Refund request was approved by authorized staff.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_processing_started',
                module: 'finance',
                action: 'refund.processing_started',
                subjectType: 'refund',
                actorTypes: ['user', 'system'],
                safeFields: ['refund_public_id', 'payment_public_id', 'order_public_id', 'old_status', 'new_status', 'actor_type', 'actor_id', 'occurred_at'],
                maskedFields: ['reason_note', 'provider_reference', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment'],
                references: ['C5.2.3'],
                summary: 'Refund started processing.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_processing_succeeded',
                module: 'finance',
                action: 'refund.processing_succeeded',
                subjectType: 'refund',
                actorTypes: ['user', 'system', 'job', 'provider'],
                safeFields: ['refund_public_id', 'payment_public_id', 'order_public_id', 'old_status', 'new_status', 'actor_type', 'actor_id', 'occurred_at', 'refund_type', 'amount', 'currency', 'provider', 'provider_refund_id', 'processed_at'],
                maskedFields: ['provider_reference', 'reason_note', 'payment_credentials', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment', 'payment_attempt'],
                references: ['C5.2.3'],
                summary: 'Refund succeeded.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_processing_failed',
                module: 'finance',
                action: 'refund.processing_failed',
                subjectType: 'refund',
                actorTypes: ['user', 'system', 'job', 'provider'],
                safeFields: ['refund_public_id', 'payment_public_id', 'order_public_id', 'old_status', 'new_status', 'actor_type', 'actor_id', 'occurred_at', 'reason_code'],
                maskedFields: ['provider_reference', 'reason_note', 'payment_credentials', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment', 'payment_attempt'],
                references: ['C5.2.3'],
                summary: 'Refund processing failed.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_cancelled',
                module: 'finance',
                action: 'refund.cancelled',
                subjectType: 'refund',
                actorTypes: ['user', 'system'],
                safeFields: ['refund_public_id', 'payment_public_id', 'order_public_id', 'old_status', 'new_status', 'actor_type', 'actor_id', 'occurred_at'],
                maskedFields: ['reason_note', 'provider_reference', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment'],
                references: ['C5.2.3'],
                summary: 'Refund was cancelled.',
            ),
            new AuditEventDefinition(
                key: 'refunds.refund_recorded',
                module: 'finance',
                action: 'refund.recorded',
                subjectType: 'refund',
                actorTypes: ['user', 'system', 'job', 'provider'],
                safeFields: ['refund_public_id', 'order_public_id', 'payment_public_id', 'refund_type', 'status', 'amount', 'currency', 'provider', 'provider_refund_id', 'processed_at'],
                maskedFields: ['provider_reference', 'reason_note', 'payment_credentials', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['order', 'payment', 'payment_attempt'],
                references: ['A5.2.3', 'A5.2.4', 'C5.2.3', 'C5.2.4', 'C5.2.5'],
                summary: 'Refund was recorded or synchronized from a verified internal or provider source.',
            ),
            new AuditEventDefinition(
                key: 'inventory.stock_moved',
                module: 'inventory',
                action: 'stock.moved',
                subjectType: 'inventory_movement',
                actorTypes: ['user', 'system', 'job'],
                safeFields: ['movement_public_id', 'sku_public_id', 'movement_type', 'direction', 'quantity', 'before_on_hand', 'after_on_hand', 'before_reserved', 'after_reserved', 'reason', 'actor_user_id'],
                maskedFields: ['private_notes', 'raw_payload', 'token'],
                relatedTypes: ['sku', 'order', 'purchase_order'],
                references: ['C2.1.2', 'C2.1.3', 'C2.1.4', 'C2.1.5', 'C2.1.6'],
                summary: 'Stock balance changed through a tracked inventory movement.',
            ),
            new AuditEventDefinition(
                key: 'finance.expense_recorded',
                module: 'finance',
                action: 'expense.recorded',
                subjectType: 'expense',
                actorTypes: ['user', 'system'],
                safeFields: ['expense_public_id', 'category', 'amount', 'currency', 'expense_date', 'approval_status'],
                maskedFields: ['vendor_account_number', 'payment_credentials', 'token', 'raw_payload'],
                relatedTypes: ['vendor', 'order', 'payment'],
                references: ['C5.3.2', 'C5.3.3', 'C5.4'],
                summary: 'Business expense was created or updated for finance review.',
            ),
            new AuditEventDefinition(
                key: 'orders.order_edited',
                module: 'orders',
                action: 'order.edited',
                subjectType: 'order',
                actorTypes: ['user', 'system'],
                safeFields: ['order_public_id', 'order_type', 'customer_public_id', 'status', 'total_amount', 'currency', 'changes'],
                maskedFields: ['payment_reference', 'gateway_payload', 'raw_payload'],
                relatedTypes: ['customer', 'order_item'],
                references: ['C1.2.7'],
                summary: 'Sales order was updated by staff with validation checks and changes tracked.',
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_map(static fn (AuditEventDefinition $definition): string => $definition->key, $this->definitions());
    }

    public function definition(string $key): ?AuditEventDefinition
    {
        foreach ($this->definitions() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }
}
