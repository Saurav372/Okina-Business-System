<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Support\Notifications\Channels\NotificationChannel;
use App\Support\Notifications\NotificationChannelRegistry;
use App\Support\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new NotificationDispatcher;

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        // Clean tables
        DB::table('notification_delivery_attempts')->delete();
        DB::table('notification_logs')->delete();
        DB::table('notification_templates')->delete();
    }

    /**
     * Test successful notification delivery flow.
     */
    public function test_successful_notification_delivery(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'order.created',
            'channel' => 'email',
            'name' => 'Order Confirm',
            'subject_template' => 'Order {{ order.id }}',
            'body_template' => 'Hello {{ name }}',
            'locale' => 'en',
            'status' => NotificationTemplate::STATUS_ACTIVE,
            'version' => 1,
            'allowed_variables' => ['order.id', 'name'],
        ]);

        $log = $this->dispatcher->dispatch(
            'order.created',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'test@example.com'],
            ['order' => ['id' => 123], 'name' => 'Saurav']
        );

        $this->assertEquals(NotificationLog::STATUS_PENDING, $log->status);

        // Run the job manually
        $job = new SendNotificationJob($log->id);
        app()->call([$job, 'handle']);

        $log->refresh();
        $this->assertEquals(NotificationLog::STATUS_SENT, $log->status);
        $this->assertEquals('Order 123', $log->subject_rendered);
        $this->assertStringContainsString('Hello Saurav', $log->body_summary);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'success',
        ]);
    }

    /**
     * Test adapter throwing exception is handled and logged before rethrowing.
     */
    public function test_failed_notification_delivery_logs_attempt_and_marks_failed(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'order.failed',
            'channel' => 'sms',
            'name' => 'Order Failed',
            'body_template' => 'Failed',
            'status' => NotificationTemplate::STATUS_ACTIVE,
        ]);

        // Mock SMS Adapter to throw exception
        $mockAdapter = Mockery::mock(NotificationChannel::class);
        $mockAdapter->shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMS gateway timeout'));

        $this->app->make(NotificationChannelRegistry::class);
        Config::set('notifications.drivers.sms', get_class($mockAdapter));
        $this->app->instance(get_class($mockAdapter), $mockAdapter);

        $log = $this->dispatcher->dispatch(
            'order.failed',
            'sms',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => '9999999999'],
            []
        );

        $job = new SendNotificationJob($log->id);

        try {
            app()->call([$job, 'handle']);
            $this->fail('Job should have rethrown the exception.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('SMS gateway timeout', $e->getMessage());
        }

        $log->refresh();
        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->status);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'failed',
            'error_message' => 'SMS gateway timeout',
        ]);
    }

    /**
     * Test idempotency key deduplication guards against duplicates.
     */
    public function test_deduplication_returns_existing_log_and_prevents_duplicate_jobs(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'dup.test',
            'channel' => 'email',
            'name' => 'Dup Test',
            'body_template' => 'Dup',
            'status' => NotificationTemplate::STATUS_ACTIVE,
        ]);

        $dedupeKey = 'unique_dedupe_123';

        $log1 = $this->dispatcher->dispatch(
            'dup.test',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'test@example.com'],
            [],
            $dedupeKey
        );

        $log2 = $this->dispatcher->dispatch(
            'dup.test',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'test@example.com'],
            [],
            $dedupeKey
        );

        $this->assertEquals($log1->id, $log2->id);
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    /**
     * Test job afterCommit delays queueing until transaction is fully committed.
     */
    public function test_job_dispatched_after_transaction_commit(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'commit.test',
            'channel' => 'email',
            'name' => 'Commit Test',
            'body_template' => 'Commit',
            'status' => NotificationTemplate::STATUS_ACTIVE,
        ]);

        DB::beginTransaction();

        $this->dispatcher->dispatch(
            'commit.test',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'test@example.com'],
            []
        );

        // Verify not queued yet
        Queue::assertNotPushed(SendNotificationJob::class);

        DB::commit();

        // Verify queued now
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    /**
     * Test job handling an already sent notification exits immediately.
     */
    public function test_already_sent_exits_immediately(): void
    {
        $log = NotificationLog::create([
            'event_type' => 'test',
            'channel' => 'email',
            'recipient_type' => 'external',
            'status' => NotificationLog::STATUS_SENT,
        ]);

        // Create first dummy attempt
        NotificationDeliveryAttempt::create([
            'notification_log_id' => $log->id,
            'status' => 'success',
            'attempted_at' => now(),
        ]);

        $job = new SendNotificationJob($log->id);
        app()->call([$job, 'handle']);

        // Check no new attempt was created (count remains 1)
        $this->assertEquals(1, NotificationDeliveryAttempt::count());
    }

    /**
     * Test missing template workflow marks failed immediately and does not queue job.
     */
    public function test_missing_template_marks_failed_with_delivery_attempt(): void
    {
        Queue::fake();

        $log = $this->dispatcher->dispatch(
            'missing.template.event',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'test@example.com'],
            []
        );

        $log->refresh();
        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->status);

        // Verify no queued job is dispatched
        Queue::assertNotPushed(SendNotificationJob::class);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'failed',
        ]);

        $attempt = NotificationDeliveryAttempt::where('notification_log_id', $log->id)->first();
        $this->assertStringContainsString('Active template not found', $attempt->error_message);
    }

    /**
     * Test Database Channel persists mock logs behavior.
     */
    public function test_database_channel_stores_attempt(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'db.test',
            'channel' => 'database',
            'name' => 'DB Test',
            'body_template' => 'DB Content',
            'status' => NotificationTemplate::STATUS_ACTIVE,
        ]);

        $log = $this->dispatcher->dispatch(
            'db.test',
            'database',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'local_db'],
            []
        );

        $job = new SendNotificationJob($log->id);
        app()->call([$job, 'handle']);

        $log->refresh();
        $this->assertEquals(NotificationLog::STATUS_SENT, $log->status);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'success',
            'provider_reference' => 'db_ref_'.$log->id,
        ]);
    }
}
