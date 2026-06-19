<?php

namespace App\Support\Idempotency;

final class IdempotencyOperationCatalog
{
    public function __construct(
        private readonly IdempotencyKeyGenerator $generator,
    ) {}

    /**
     * @return array<int, IdempotencyOperationDefinition>
     */
    public function definitions(): array
    {
        return [
            new IdempotencyOperationDefinition(
                key: 'checkout_submission',
                name: 'Checkout submission',
                duplicateHandling: 'reuse_existing',
                keyParts: ['cart_id', 'customer_id', 'shipping_address_id'],
                references: ['B3.1.6', 'B3.1.9'],
            ),
            new IdempotencyOperationDefinition(
                key: 'order_creation',
                name: 'Order creation',
                duplicateHandling: 'reject_duplicate',
                keyParts: ['source', 'source_reference'],
                references: ['A5.1.5', 'B3.1.6', 'C1.2.6'],
            ),
            new IdempotencyOperationDefinition(
                key: 'payment_attempt',
                name: 'Payment attempt',
                duplicateHandling: 'reuse_existing',
                keyParts: ['order_public_id', 'gateway', 'gateway_reference'],
                references: ['A5.3.3', 'B3.1.8'],
            ),
            new IdempotencyOperationDefinition(
                key: 'payment_webhook',
                name: 'Payment webhook',
                duplicateHandling: 'ignore_duplicate',
                keyParts: ['gateway', 'event_id'],
                references: ['B3.3.5', 'B3.3.6'],
            ),
            new IdempotencyOperationDefinition(
                key: 'inventory_movement',
                name: 'Inventory movement',
                duplicateHandling: 'reject_duplicate',
                keyParts: ['sku_public_id', 'movement_type', 'source_public_id', 'quantity'],
                references: ['C2.1.2', 'C2.1.4', 'C2.1.6'],
            ),
            new IdempotencyOperationDefinition(
                key: 'notification_send',
                name: 'Notification send',
                duplicateHandling: 'ignore_duplicate',
                keyParts: ['notification_event', 'recipient_type', 'recipient_id', 'channel', 'source_public_id'],
                references: ['A4.4', 'C6.2'],
            ),
            new IdempotencyOperationDefinition(
                key: 'google_sheets_sync',
                name: 'Google Sheets sync',
                duplicateHandling: 'ignore_duplicate',
                keyParts: ['dataset', 'record_public_id', 'sync_batch_id'],
                references: ['C6.3'],
            ),
            new IdempotencyOperationDefinition(
                key: 'job_retry',
                name: 'Job retry dispatch',
                duplicateHandling: 'reuse_existing',
                keyParts: ['job_class', 'unique_id'],
                references: ['A4.3', 'C6.3'],
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_map(static fn (IdempotencyOperationDefinition $definition): string => $definition->key, $this->definitions());
    }

    public function definition(string $key): ?IdempotencyOperationDefinition
    {
        foreach ($this->definitions() as $definition) {
            if ($definition->key === $key) {
                return $definition;
            }
        }

        return null;
    }

    public function duplicateHandling(string $key): ?string
    {
        return $this->definition($key)?->duplicateHandling;
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    public function keyFor(string $operation, array $parts = []): string
    {
        return $this->generator->make($operation, $parts);
    }
}
