<?php

namespace App\Support\Idempotency;

readonly class IdempotencyOperationDefinition
{
    /**
     * @param  array<int, string>  $keyParts
     * @param  array<int, string>  $references
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $duplicateHandling,
        public array $keyParts,
        public array $references = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'duplicate_handling' => $this->duplicateHandling,
            'key_parts' => $this->keyParts,
            'references' => $this->references,
        ];
    }
}
