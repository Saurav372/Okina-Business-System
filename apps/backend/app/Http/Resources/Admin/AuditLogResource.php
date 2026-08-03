<?php

namespace App\Http\Resources\Admin;

use App\Support\Audit\AuditPayloadSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $actorName = 'System';
        if ($this->actorUser) {
            $actorName = $this->actorUser->name;
        } elseif ($this->actorCustomer) {
            $actorName = $this->actorCustomer->name ?? 'Customer';
        } elseif (! empty($this->actor_label_snapshot)) {
            $actorName = $this->actor_label_snapshot;
        }

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'action' => $this->action,
            'module' => $this->module,
            'actor' => [
                'type' => $this->actor_type?->value ?? 'system',
                'name' => $actorName,
                'user_id' => $this->actor_user_id,
            ],
            'subject' => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
                'public_id' => $this->subject_public_id,
            ],
            'summary' => $this->summary,
            'old_values' => AuditPayloadSanitizer::sanitize($this->old_values),
            'new_values' => AuditPayloadSanitizer::sanitize($this->new_values),
            'metadata' => AuditPayloadSanitizer::sanitize($this->metadata),
            'ip_address' => $this->ip_address,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}
