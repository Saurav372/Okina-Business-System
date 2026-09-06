<?php

namespace App\Support\Health;

final readonly class SystemHealthSummary
{
    /**
     * @param  array<string, HealthComponent>  $components
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public string $overallStatus, // 'ok', 'degraded', 'error'
        public string $checkedAt,
        public bool $isCached,
        public int $cacheAgeSeconds,
        public array $warnings,
        public array $components
    ) {}
}
