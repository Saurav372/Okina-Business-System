<?php

namespace App\Support\Audit;

use App\Contracts\AuditEventContract;

readonly class AuditEventDefinition implements AuditEventContract
{
    /**
     * @param  array<int, string>  $actorTypes
     * @param  array<int, string>  $safeFields
     * @param  array<int, string>  $maskedFields
     * @param  array<int, string>  $relatedTypes
     * @param  array<int, string>  $references
     */
    public function __construct(
        public string $key,
        public string $module,
        public string $action,
        public string $subjectType,
        public array $actorTypes,
        public array $safeFields,
        public array $maskedFields,
        public array $relatedTypes = [],
        public array $references = [],
        public ?string $summary = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'module' => $this->module,
            'action' => $this->action,
            'subject_type' => $this->subjectType,
            'actor_types' => $this->actorTypes,
            'safe_fields' => $this->safeFields,
            'masked_fields' => $this->maskedFields,
            'related_types' => $this->relatedTypes,
            'references' => $this->references,
            'summary' => $this->summary,
        ];
    }
}
