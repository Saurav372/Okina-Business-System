<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * Test migrations run and roll back cleanly.
     */
    public function test_migrations_run_and_roll_back(): void
    {
        $this->assertTrue(Schema::hasTable('notification_templates'));
        $this->assertTrue(Schema::hasTable('notification_logs'));
        $this->assertTrue(Schema::hasTable('notification_delivery_attempts'));

        // Roll back the last migration step
        $this->artisan('migrate:rollback', ['--step' => 1]);

        $this->assertFalse(Schema::hasTable('notification_delivery_attempts'));
        $this->assertFalse(Schema::hasTable('notification_logs'));
        $this->assertFalse(Schema::hasTable('notification_templates'));
    }

    /**
     * Test that creating model instances works correctly with relationships.
     */
    public function test_models_can_be_created_with_relationships(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        // 1. Create Template
        $template = NotificationTemplate::create([
            'template_key' => 'order_created.customer',
            'channel' => NotificationTemplate::CHANNEL_EMAIL,
            'name' => 'Order Created Customer Notification',
            'subject_template' => 'Your order has been created',
            'body_template' => 'Hello {{ name }}, your order is confirmed.',
            'locale' => 'en',
            'status' => NotificationTemplate::STATUS_ACTIVE,
            'version' => 1,
            'allowed_variables' => ['name', 'order_id'],
            'created_by_user_id' => $user->id,
            'updated_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('notification_templates', [
            'id' => $template->id,
            'template_key' => 'order_created.customer',
        ]);
        $this->assertEquals($user->id, $template->createdByUser->id);

        // 2. Create Log
        $log = NotificationLog::create([
            'event_type' => 'order.created',
            'template_id' => $template->id,
            'template_key' => $template->template_key,
            'template_version' => $template->version,
            'channel' => NotificationTemplate::CHANNEL_EMAIL,
            'status' => NotificationLog::STATUS_PENDING,
            'recipient_type' => NotificationLog::RECIPIENT_CUSTOMER,
            'recipient_user_id' => null,
            'recipient_customer_id' => $customer->id,
            'recipient_address' => 'customer@example.com',
            'subject_rendered' => 'Your order has been created',
            'body_summary' => 'Order confirmation sent to customer',
            'payload' => ['name' => 'Saurav', 'order_id' => 101],
            'related_type' => 'orders',
            'related_id' => 101,
            'dedupe_key' => 'order_created_101_email',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'dedupe_key' => 'order_created_101_email',
        ]);
        $this->assertEquals($template->id, $log->template->id);
        $this->assertEquals($customer->id, $log->recipientCustomer->id);

        // 3. Create Delivery Attempt
        $attempt = NotificationDeliveryAttempt::create([
            'notification_log_id' => $log->id,
            'status' => 'success',
            'provider_reference' => 'msg_ref_9988',
            'error_message' => null,
            'response_payload' => ['provider' => 'ses', 'status' => 'delivered'],
            'attempted_at' => now(),
        ]);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'id' => $attempt->id,
            'notification_log_id' => $log->id,
        ]);
        $this->assertEquals($log->id, $attempt->notificationLog->id);
    }

    /**
     * Test JSON casts return arrays.
     */
    public function test_json_casting_returns_arrays(): void
    {
        $template = NotificationTemplate::create([
            'template_key' => 'cast_test',
            'channel' => 'email',
            'name' => 'Cast Test',
            'body_template' => 'Hello',
            'allowed_variables' => ['var1', 'var2'],
        ]);

        $log = NotificationLog::create([
            'event_type' => 'test.event',
            'channel' => 'email',
            'recipient_type' => 'external',
            'payload' => ['key1' => 'value1'],
        ]);

        $attempt = NotificationDeliveryAttempt::create([
            'notification_log_id' => $log->id,
            'status' => 'failed',
            'response_payload' => ['error' => 'Rate limit'],
            'attempted_at' => now(),
        ]);

        $this->assertIsArray($template->allowed_variables);
        $this->assertEquals(['var1', 'var2'], $template->allowed_variables);

        $this->assertIsArray($log->payload);
        $this->assertEquals(['key1' => 'value1'], $log->payload);

        $this->assertIsArray($attempt->response_payload);
        $this->assertEquals(['error' => 'Rate limit'], $attempt->response_payload);
    }

    /**
     * Test unique constraints on notification_templates.
     */
    public function test_notification_templates_unique_constraints(): void
    {
        NotificationTemplate::create([
            'template_key' => 'unique.test',
            'channel' => 'email',
            'name' => 'First Template',
            'body_template' => 'First',
            'locale' => 'en',
            'version' => 1,
        ]);

        $this->expectException(QueryException::class);

        // Attempting duplicate (key, channel, locale, version)
        NotificationTemplate::create([
            'template_key' => 'unique.test',
            'channel' => 'email',
            'name' => 'Second Template',
            'body_template' => 'Second',
            'locale' => 'en',
            'version' => 1,
        ]);
    }

    /**
     * Test unique constraints on notification_logs dedupe_key.
     */
    public function test_notification_logs_dedupe_key_unique_constraint(): void
    {
        NotificationLog::create([
            'event_type' => 'test',
            'channel' => 'email',
            'recipient_type' => 'external',
            'dedupe_key' => 'key_123',
        ]);

        $this->expectException(QueryException::class);

        // Attempting duplicate dedupe_key
        NotificationLog::create([
            'event_type' => 'test',
            'channel' => 'email',
            'recipient_type' => 'external',
            'dedupe_key' => 'key_123',
        ]);
    }

    /**
     * Test cascade deletion works on notification_delivery_attempts.
     */
    public function test_cascade_deletion_on_delivery_attempts(): void
    {
        $log = NotificationLog::create([
            'event_type' => 'test.event',
            'channel' => 'email',
            'recipient_type' => 'external',
        ]);

        $attempt = NotificationDeliveryAttempt::create([
            'notification_log_id' => $log->id,
            'status' => 'sent',
            'attempted_at' => now(),
        ]);

        $this->assertDatabaseHas('notification_delivery_attempts', ['id' => $attempt->id]);

        // Delete parent log
        $log->delete();

        // Check cascade deletion
        $this->assertDatabaseMissing('notification_delivery_attempts', ['id' => $attempt->id]);
    }
}
