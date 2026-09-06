<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Support\Notifications\Channels\NotificationChannel;
use App\Support\Notifications\NotificationChannelRegistry;
use App\Support\Notifications\NotificationDispatcher;
use App\Support\Notifications\NotificationRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class NotificationIsolationTest extends TestCase
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

        // Clean tables to ensure complete isolation
        NotificationDeliveryAttempt::truncate();
        NotificationLog::truncate();
        NotificationTemplate::truncate();
    }

    /**
     * Verify that when a business transaction updates a record and dispatches
     * a notification, but the template is missing, the business transaction still commits successfully.
     */
    public function test_source_transaction_isolation_missing_template(): void
    {
        Queue::fake();

        // Start a transaction, do a business database action, dispatch, and commit
        DB::transaction(function () {
            User::factory()->create(['email' => 'transactional@example.com']);

            // Dispatches to a non-existent template
            $this->dispatcher->dispatch(
                'missing.event',
                'email',
                NotificationLog::RECIPIENT_EXTERNAL,
                ['address' => 'recipient@example.com'],
                ['foo' => 'bar']
            );
        });

        // Assert the user was created successfully (transaction committed)
        $this->assertDatabaseHas('users', ['email' => 'transactional@example.com']);

        // Assert a failed log and failed attempt record were created for the missing template
        $this->assertDatabaseHas('notification_logs', [
            'event_type' => 'missing.event',
            'channel' => 'email',
            'status' => NotificationLog::STATUS_FAILED,
        ]);

        $log = NotificationLog::where('event_type', 'missing.event')->first();
        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'failed',
            'error_message' => 'Active template not found for event: missing.event on channel: email',
        ]);

        // Assert nothing was queued
        Queue::assertNothingPushed();
    }

    /**
     * Verify that when a template has rendering errors, the business transaction commits successfully
     * and the notification status is updated to failed/skipped.
     */
    public function test_source_transaction_isolation_rendering_failure(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'render.failed',
            'channel' => 'email',
            'name' => 'Render Fail Template',
            'body_template' => 'Hello {{ name }}',
            'status' => NotificationTemplate::STATUS_ACTIVE,
            'version' => 1,
        ]);

        // Start transaction, do a business action, dispatch, and commit
        DB::transaction(function () {
            User::factory()->create(['email' => 'render-tx@example.com']);

            $this->dispatcher->dispatch(
                'render.failed',
                'email',
                NotificationLog::RECIPIENT_EXTERNAL,
                ['address' => 'recipient@example.com'],
                ['name' => 'Saurav']
            );
        });

        // Assert the user was created successfully (transaction committed)
        $this->assertDatabaseHas('users', ['email' => 'render-tx@example.com']);

        // Assert the log is created in pending status
        $log = NotificationLog::where('event_type', 'render.failed')->first();
        $this->assertEquals(NotificationLog::STATUS_PENDING, $log->status);

        // Mock the renderer to throw an exception
        $mockRenderer = Mockery::mock(NotificationRenderer::class);
        $mockRenderer->shouldReceive('processPayload')
            ->andThrow(new RuntimeException('Mocked rendering exception'));

        $this->app->instance(NotificationRenderer::class, $mockRenderer);

        // Execute the job manually
        $job = new SendNotificationJob($log->id);
        app()->call([$job, 'handle']);

        // Verify the log status has been set to failed
        $log->refresh();
        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->status);

        // Verify that the failure is documented in the delivery attempts
        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'failed',
            'error_message' => 'Rendering failed: Mocked rendering exception',
        ]);
    }

    /**
     * Verify that when an adapter throws a transport exception during queued delivery execution,
     * the job records the failure, updates log status, and respects retries without rolling back the DB attempt state.
     */
    public function test_queue_job_transport_exception_resilience(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'transport.failed',
            'channel' => 'sms',
            'name' => 'SMS Fail',
            'body_template' => 'SMS Body',
            'status' => NotificationTemplate::STATUS_ACTIVE,
            'version' => 1,
        ]);

        $log = $this->dispatcher->dispatch(
            'transport.failed',
            'sms',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => '1234567890'],
            []
        );

        // Mock SMS Adapter to throw a transport exception
        $mockAdapter = Mockery::mock(NotificationChannel::class);
        $mockAdapter->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('SMS connection failed'));

        $this->app->make(NotificationChannelRegistry::class);
        Config::set('notifications.drivers.sms', get_class($mockAdapter));
        $this->app->instance(get_class($mockAdapter), $mockAdapter);

        // Execute the job manually
        $job = new SendNotificationJob($log->id);

        try {
            app()->call([$job, 'handle']);
            $this->fail('Job should have rethrown the exception for the queue worker to retry.');
        } catch (RuntimeException $e) {
            $this->assertEquals('SMS connection failed', $e->getMessage());
        }

        // Verify the attempt log was saved and not rolled back
        $log->refresh();
        $this->assertEquals(NotificationLog::STATUS_FAILED, $log->status);

        $this->assertDatabaseHas('notification_delivery_attempts', [
            'notification_log_id' => $log->id,
            'status' => 'failed',
            'error_message' => 'SMS connection failed',
        ]);
    }

    /**
     * Verify that the afterCommit dispatcher hook prevents notifications from being queued
     * if the business transaction rolls back, and queues them properly if it commits.
     */
    public function test_transaction_safe_queueing_after_commit_and_rollback(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'tx.safe',
            'channel' => 'email',
            'name' => 'Safe Queue Template',
            'body_template' => 'Test Body',
            'status' => NotificationTemplate::STATUS_ACTIVE,
            'version' => 1,
        ]);

        // Scenario A: Rollback
        DB::beginTransaction();
        $logRollback = $this->dispatcher->dispatch(
            'tx.safe',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'rolledback@example.com'],
            []
        );
        DB::rollBack();

        // Assert job is not queued
        Queue::assertNotPushed(SendNotificationJob::class);

        // Scenario B: Commit
        DB::beginTransaction();
        $logCommit = $this->dispatcher->dispatch(
            'tx.safe',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'committed@example.com'],
            []
        );
        DB::commit();

        // Assert job is queued and type/payload is verified
        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) use ($logCommit) {
            // Access protected/public property of job to verify payload
            $reflection = new \ReflectionClass($job);
            $property = $reflection->getProperty('notificationLogId');
            $property->setAccessible(true);
            $logId = $property->getValue($job);

            return $logId === $logCommit->id;
        });
    }

    /**
     * Verify that repeated dispatches with the same dedupe_key result in only one
     * NotificationLog record and only one queued job.
     */
    public function test_deduplication_prevents_duplicate_logs_and_queued_jobs(): void
    {
        Queue::fake();

        $template = NotificationTemplate::create([
            'template_key' => 'dedupe.event',
            'channel' => 'email',
            'name' => 'Dedupe Template',
            'body_template' => 'Dedupe Body',
            'status' => NotificationTemplate::STATUS_ACTIVE,
            'version' => 1,
        ]);

        $dedupeKey = 'unique_dedupe_key_123';

        // Dispatch first time
        $log1 = $this->dispatcher->dispatch(
            'dedupe.event',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'dedupe@example.com'],
            [],
            $dedupeKey
        );

        // Dispatch second time with same dedupe key
        $log2 = $this->dispatcher->dispatch(
            'dedupe.event',
            'email',
            NotificationLog::RECIPIENT_EXTERNAL,
            ['address' => 'dedupe@example.com'],
            [],
            $dedupeKey
        );

        // Assert the second dispatch returned the same log instance
        $this->assertEquals($log1->id, $log2->id);

        // Assert only one log record exists in the database
        $this->assertEquals(1, NotificationLog::where('dedupe_key', $dedupeKey)->count());

        // Assert exactly one job was queued
        Queue::assertPushed(SendNotificationJob::class, 1);
    }
}
