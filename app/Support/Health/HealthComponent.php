<?php

namespace App\Support\Health;

final readonly class HealthComponent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $name,
        public string $status, // 'ok', 'degraded', 'error', 'unavailable'
        public ?float $latencyMs = null,
        public ?string $message = null,
        public array $metadata = []
    ) {}
}
