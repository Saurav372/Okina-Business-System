<?php

namespace App\Services;

use App\Support\Queue\QueueFoundation;
use Illuminate\Support\Facades\Log;
use Throwable;

class QueueFailureLogger
{
    public function __construct(
        private readonly QueueFoundation $foundation,
    ) {}

    public function log(object $job, Throwable $exception): void
    {
        Log::channel($this->foundation->failureLogChannel())->warning(
            'Queued job failed.',
            $this->context($job, $exception),
        );
    }

    public function context(object $job, Throwable $exception): array
    {
        $context = [
            'job' => $job::class,
            'connection' => property_exists($job, 'connection') ? $job->connection : null,
            'queue' => property_exists($job, 'queue') ? $job->queue : null,
            'tries' => property_exists($job, 'tries') ? $job->tries : null,
            'unique_id' => method_exists($job, 'uniqueId') ? $job->uniqueId() : null,
            'exception_class' => $exception::class,
            'exception_code' => $exception->getCode(),
        ];

        return array_filter($context, static fn ($value): bool => $value !== null && $value !== '');
    }
}
