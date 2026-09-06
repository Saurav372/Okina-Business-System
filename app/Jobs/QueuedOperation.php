<?php

namespace App\Jobs;

use App\Services\QueueFailureLogger;
use App\Support\Queue\QueueFoundation;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

abstract class QueuedOperation implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public int $maxExceptions = 1;

    public int $uniqueFor = 300;

    public function __construct()
    {
        $foundation = app(QueueFoundation::class);

        $this->onConnection($foundation->connection());
        $this->onQueue($foundation->queue());
        $this->tries = $foundation->tries();
        $this->backoff = $foundation->backoff();
        $this->uniqueFor = $foundation->uniqueForSeconds();
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(app(QueueFoundation::class)->retryUntilMinutes());
    }

    public function uniqueId(): string
    {
        return app(QueueFoundation::class)->dedupeKey(static::class, $this->dedupeKeyParts());
    }

    public function failed(Throwable $exception): void
    {
        app(QueueFailureLogger::class)->log($this, $exception);
    }

    /**
     * Return the key parts that should make this job unique.
     *
     * @return array<int, string|int|null>
     */
    abstract protected function dedupeKeyParts(): array;
}
