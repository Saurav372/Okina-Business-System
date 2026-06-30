<?php

namespace App\Listeners;

use App\Enums\AuditActorType;
use App\Events\AuditEvent;
use App\Models\AuditLog;
use App\Models\AuditLogRelatedRecord;
use App\Models\Customer;
use App\Models\User;
use App\Support\Audit\AuditEventCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuditEventListener
{
    public function __construct(
        private readonly AuditEventCatalog $catalog,
    ) {}

    public function handle(AuditEvent $event): void
    {
        $payload = $event->payload;

        // Resolve the catalog definition first so we can use it as a fallback for subject_type
        $definition = $this->catalog->definition($event->key);

        // Validate required fields — subject_type may be omitted if the catalog definition supplies it
        $subjectType = $payload['subject_type'] ?? $definition?->subjectType ?? null;

        if (empty($subjectType)) {
            // Log a warning for unregistered events that omit subject_type rather than
            // throwing, to preserve backward compatibility with existing dispatchers.
            Log::warning('AuditEvent missing subject_type — falling back to unknown.', [
                'event_key' => $event->key,
            ]);
            $subjectType = 'unknown';
        }

        if (isset($payload['old_values']) && ! is_array($payload['old_values'])) {
            throw new \InvalidArgumentException(
                "AuditEvent payload 'old_values' must be an array. Event key: [{$event->key}]."
            );
        }

        if (isset($payload['new_values']) && ! is_array($payload['new_values'])) {
            throw new \InvalidArgumentException(
                "AuditEvent payload 'new_values' must be an array. Event key: [{$event->key}]."
            );
        }

        if (isset($payload['related_records'])) {
            if (! is_array($payload['related_records'])) {
                throw new \InvalidArgumentException(
                    "AuditEvent payload 'related_records' must be an array. Event key: [{$event->key}]."
                );
            }

            foreach ($payload['related_records'] as $index => $record) {
                if (empty($record['related_type'])) {
                    throw new \InvalidArgumentException(
                        "AuditEvent related_records[{$index}] must include 'related_type'. Event key: [{$event->key}]."
                    );
                }

                if (empty($record['relation'])) {
                    throw new \InvalidArgumentException(
                        "AuditEvent related_records[{$index}] must include 'relation'. Event key: [{$event->key}]."
                    );
                }
            }
        }

        $idempotencyKey = $payload['idempotency_key'] ?? null;

        // Early exit check — avoid unnecessary transaction if already persisted
        if ($idempotencyKey !== null && AuditLog::where('idempotency_key', $idempotencyKey)->exists()) {
            return;
        }

        // Determine actor fields
        $actorType = AuditActorType::SYSTEM;
        $actorUserId = null;
        $actorCustomerId = null;
        $actorLabelSnapshot = null;

        if ($event->actor instanceof User) {
            $actorType = AuditActorType::USER;
            $actorUserId = $event->actor->id;
            $actorLabelSnapshot = $event->actor->name ?? null;
        } elseif ($event->actor instanceof Customer) {
            $actorType = AuditActorType::CUSTOMER;
            $actorCustomerId = $event->actor->id;
            $actorLabelSnapshot = $event->actor->display_name ?? $event->actor->name ?? null;
        } elseif (isset($payload['actor_type'])) {
            $actorType = AuditActorType::tryFrom((string) $payload['actor_type']) ?? AuditActorType::SYSTEM;
        }

        // Parse occurred_at as immutable timestamp
        $occurredAt = isset($payload['occurred_at'])
            ? CarbonImmutable::parse($payload['occurred_at'])
            : now()->toImmutable();

        DB::transaction(function () use (
            $event,
            $payload,
            $definition,
            $subjectType,
            $idempotencyKey,
            $actorType,
            $actorUserId,
            $actorCustomerId,
            $actorLabelSnapshot,
            $occurredAt,
        ): void {
            // Double check inside transaction to guard against race conditions
            if ($idempotencyKey !== null && AuditLog::where('idempotency_key', $idempotencyKey)->exists()) {
                return;
            }

            $auditLog = AuditLog::create([
                'event_id' => (string) Str::uuid(),
                'action' => $definition !== null ? $definition->action : $event->key,
                'module' => $definition !== null ? $definition->module : explode('.', $event->key)[0],
                'actor_type' => $actorType,
                'actor_user_id' => $actorUserId,
                'actor_customer_id' => $actorCustomerId,
                'actor_label_snapshot' => $actorLabelSnapshot,
                'subject_type' => $subjectType,
                'subject_id' => $payload['subject_id'] ?? null,
                'subject_public_id' => $payload['subject_public_id'] ?? null,
                'summary' => $payload['summary'] ?? ($definition !== null ? $definition->summary : null),
                'old_values' => $payload['old_values'] ?? null,
                'new_values' => $payload['new_values'] ?? null,
                'metadata' => $payload['metadata'] ?? null,
                'request_id' => $payload['request_id'] ?? null,
                'idempotency_key' => $idempotencyKey,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => $occurredAt,
            ]);

            // Collect related records candidates
            $candidates = [];

            // 1. Add subject itself as relation 'subject'
            if (! empty($payload['subject_id']) || ! empty($payload['subject_public_id'])) {
                $candidates[] = [
                    'related_type' => $payload['subject_type'],
                    'related_id' => $payload['subject_id'] ?? null,
                    'related_public_id' => $payload['subject_public_id'] ?? null,
                    'relation' => 'subject',
                ];
            }

            // 2. Dynamically map from relatedTypes definition
            if ($definition !== null) {
                foreach ($definition->relatedTypes as $relatedType) {
                    $relatedId = $payload[$relatedType.'_id'] ?? null;
                    $relatedPublicId = $payload[$relatedType.'_public_id'] ?? null;

                    if ($relatedId !== null || $relatedPublicId !== null) {
                        $candidates[] = [
                            'related_type' => $relatedType,
                            'related_id' => $relatedId,
                            'related_public_id' => $relatedPublicId,
                            'relation' => $relatedType,
                        ];
                    }
                }
            }

            // 3. Explicitly passed related_records
            foreach ($payload['related_records'] ?? [] as $record) {
                $candidates[] = [
                    'related_type' => $record['related_type'],
                    'related_id' => $record['related_id'] ?? null,
                    'related_public_id' => $record['related_public_id'] ?? null,
                    'relation' => $record['relation'],
                ];
            }

            // Deduplicate by composite key (related_type, related_id, related_public_id, relation)
            $seen = [];
            $deduped = [];

            foreach ($candidates as $candidate) {
                $key = implode('|', [
                    $candidate['related_type'],
                    $candidate['related_id'] ?? '',
                    $candidate['related_public_id'] ?? '',
                    $candidate['relation'],
                ]);

                if (! isset($seen[$key])) {
                    $seen[$key] = true;
                    $deduped[] = $candidate;
                }
            }

            foreach ($deduped as $record) {
                AuditLogRelatedRecord::create([
                    'audit_log_id' => $auditLog->id,
                    'related_type' => $record['related_type'],
                    'related_id' => $record['related_id'],
                    'related_public_id' => $record['related_public_id'],
                    'relation' => $record['relation'],
                ]);
            }
        });
    }
}
