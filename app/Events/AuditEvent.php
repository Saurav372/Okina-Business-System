<?php

namespace App\Events;

use App\Contracts\AuditEventContract;
use App\Support\Audit\AuditPayloadPolicy;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditEvent implements AuditEventContract
{
    use Dispatchable, SerializesModels;

    public array $payload;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $key,
        public mixed $actor,
        array $payload
    ) {
        $policy = app(AuditPayloadPolicy::class);
        $this->payload = $policy->sanitize($payload);
    }

    /**
     * Get the payload array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'actor_id' => $this->actor?->id ?? null,
            'actor_type' => $this->actor ? get_class($this->actor) : null,
            'payload' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
