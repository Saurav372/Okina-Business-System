<?php

namespace Tests\Feature;

use App\Jobs\QueuedOperation;
use App\Services\QueueDispatchDeduplicator;
use App\Support\Queue\QueueFoundation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class QueueFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_queue_defaults_are_applied_to_queued_operations(): void
    {
        $foundation = app(QueueFoundation::class);
        $job = new QueueFoundationTestJob('orders', 'order-123');

        $this->assertSame($foundation->connection(), $job->connection);
        $this->assertSame($foundation->queue(), $job->queue);
        $this->assertSame($foundation->tries(), $job->tries);
        $this->assertSame($foundation->backoff(), $job->backoff);
        $this->assertSame($foundation->uniqueForSeconds(), $job->uniqueFor);
        $this->assertSame($foundation->failedDriver(), config('queue.failed.driver'));
        $this->assertSame($foundation->failedTable(), config('queue.failed.table'));
        $this->assertTrue($job->retryUntil()->isFuture());
        $this->assertStringContainsString('orders:order-123', $job->uniqueId());
    }

    public function test_queued_operations_can_be_dispatched_on_the_shared_queue(): void
    {
        Queue::fake();

        $foundation = app(QueueFoundation::class);

        QueueFoundationTestJob::dispatch('orders', 'order-123');

        Queue::assertPushed(QueueFoundationTestJob::class, function (QueueFoundationTestJob $job) use ($foundation): bool {
            return $job->connection === $foundation->connection()
                && $job->queue === $foundation->queue()
                && str_contains($job->uniqueId(), 'orders:order-123');
        });
    }

    public function test_duplicate_queue_claims_are_blocked_until_released(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $deduplicator = app(QueueDispatchDeduplicator::class);
        $key = 'orders:order-123';

        $this->assertTrue($deduplicator->claim($key, 60));
        $this->assertFalse($deduplicator->claim($key, 60));

        $deduplicator->release($key);

        $this->assertTrue($deduplicator->claim($key, 60));
    }

    public function test_failed_jobs_are_logged_with_safe_context(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Queued job failed.'
                    && $context['job'] === QueueFoundationTestJob::class
                    && $context['exception_class'] === RuntimeException::class
                    && isset($context['unique_id'])
                    && ! str_contains(json_encode($context), 'abc123');
            });

        $job = new QueueFoundationTestJob('orders', 'order-123');

        $job->failed(new RuntimeException('contains secret token abc123'));
    }
}

class QueueFoundationTestJob extends QueuedOperation
{
    public function __construct(
        public string $scope,
        public string $reference,
    ) {
        parent::__construct();
    }

    public function handle(): void {}

    protected function dedupeKeyParts(): array
    {
        return [$this->scope, $this->reference];
    }
}
