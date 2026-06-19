<?php

namespace App\Services;

use App\Support\Queue\QueueFoundation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QueueDispatchDeduplicator
{
    public function __construct(
        private readonly QueueFoundation $foundation,
    ) {}

    public function claim(string $key, ?int $seconds = null): bool
    {
        return Cache::add(
            $this->normalizeKey($key),
            now()->toIso8601String(),
            max(1, $seconds ?? $this->foundation->uniqueForSeconds()),
        );
    }

    public function release(string $key): void
    {
        Cache::forget($this->normalizeKey($key));
    }

    public function exists(string $key): bool
    {
        return Cache::has($this->normalizeKey($key));
    }

    public function normalizeKey(string $key): string
    {
        return 'queue:dedupe:'.Str::of($key)
            ->replace(['|', ':', '/', '\\', ' '], '-')
            ->squish()
            ->lower()
            ->toString();
    }
}
