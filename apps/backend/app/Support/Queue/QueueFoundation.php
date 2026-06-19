<?php

namespace App\Support\Queue;

use Illuminate\Support\Str;

class QueueFoundation
{
    public function connection(): string
    {
        return (string) config('queue.okina.connection', config('queue.default', 'database'));
    }

    public function queue(): string
    {
        return (string) config('queue.okina.queue', 'default');
    }

    public function tries(): int
    {
        return max(1, (int) config('queue.okina.tries', 3));
    }

    public function backoff(): array
    {
        $raw = config('queue.okina.backoff_seconds', '60,300,900');

        $values = is_array($raw)
            ? $raw
            : explode(',', (string) $raw);

        $values = array_map(static fn ($value): int => max(1, (int) trim((string) $value)), $values);

        return array_values(array_filter($values, static fn (int $value): bool => $value > 0)) ?: [60, 300, 900];
    }

    public function retryUntilMinutes(): int
    {
        return max(1, (int) config('queue.okina.retry_until_minutes', 120));
    }

    public function uniqueForSeconds(): int
    {
        return max(1, (int) config('queue.okina.unique_for_seconds', 300));
    }

    public function failureLogChannel(): string
    {
        return (string) config('queue.okina.failure_log_channel', 'stack');
    }

    public function failedDriver(): string
    {
        return (string) config('queue.failed.driver', 'database-uuids');
    }

    public function failedTable(): string
    {
        return (string) config('queue.failed.table', 'failed_jobs');
    }

    public function dedupeKey(string $jobClass, array $parts = []): string
    {
        $jobName = Str::of($jobClass)->afterLast('\\')->snake()->lower()->toString();

        $normalizedParts = collect($parts)
            ->filter(static fn ($part): bool => $part !== null && $part !== '')
            ->map(static function ($part): string {
                return Str::of((string) $part)
                    ->replace(['|', ':', '/', '\\'], '-')
                    ->squish()
                    ->lower()
                    ->toString();
            })
            ->implode(':');

        return $normalizedParts === '' ? $jobName : $jobName.':'.$normalizedParts;
    }
}
