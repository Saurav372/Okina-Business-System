<?php

namespace Tests\Feature;

use App\Support\Idempotency\IdempotencyKeyGenerator;
use App\Support\Idempotency\IdempotencyOperationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_idempotency_keys_are_stable_and_readable_when_possible(): void
    {
        $generator = app(IdempotencyKeyGenerator::class);

        $this->assertSame(
            'idempotency:checkout_submission:cart-123:customer-456',
            $generator->make('Checkout Submission', ['Cart/123', 'Customer 456']),
        );
    }

    public function test_long_idempotency_keys_fall_back_to_a_short_hash(): void
    {
        $generator = app(IdempotencyKeyGenerator::class);

        $key = $generator->make('payment_attempt', [str_repeat('a', 200), str_repeat('b', 200)]);

        $this->assertStringStartsWith('idempotency:payment_attempt:', $key);
        $this->assertLessThanOrEqual(120, strlen($key));
    }

    public function test_idempotency_catalog_defines_the_shared_duplicate_handling_rules(): void
    {
        $catalog = app(IdempotencyOperationCatalog::class);

        $this->assertSame(
            [
                'checkout_submission',
                'order_creation',
                'payment_attempt',
                'payment_webhook',
                'inventory_movement',
                'notification_send',
                'google_sheets_sync',
                'job_retry',
            ],
            $catalog->keys(),
        );

        $this->assertSame('reuse_existing', $catalog->duplicateHandling('checkout_submission'));
        $this->assertSame('reject_duplicate', $catalog->duplicateHandling('order_creation'));
        $this->assertSame('reuse_existing', $catalog->duplicateHandling('payment_attempt'));
        $this->assertSame('ignore_duplicate', $catalog->duplicateHandling('payment_webhook'));
        $this->assertSame('reject_duplicate', $catalog->duplicateHandling('inventory_movement'));
        $this->assertSame('ignore_duplicate', $catalog->duplicateHandling('notification_send'));
        $this->assertSame('ignore_duplicate', $catalog->duplicateHandling('google_sheets_sync'));
        $this->assertSame('reuse_existing', $catalog->duplicateHandling('job_retry'));
    }

    public function test_catalog_definitions_expose_the_expected_key_parts_and_references(): void
    {
        $catalog = app(IdempotencyOperationCatalog::class);
        $definition = $catalog->definition('payment_webhook');

        $this->assertNotNull($definition);
        $this->assertSame(['gateway', 'event_id'], $definition->keyParts);
        $this->assertSame(['B3.3.5', 'B3.3.6'], $definition->references);
        $this->assertSame(
            [
                'key' => 'payment_webhook',
                'name' => 'Payment webhook',
                'duplicate_handling' => 'ignore_duplicate',
                'key_parts' => ['gateway', 'event_id'],
                'references' => ['B3.3.5', 'B3.3.6'],
            ],
            $definition->toArray(),
        );
    }
}
