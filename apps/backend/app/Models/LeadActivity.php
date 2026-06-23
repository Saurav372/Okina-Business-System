<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Approved lead activity types.
 */
const LEAD_ACTIVITY_TYPES = [
    'note',
    'call',
    'email',
    'whatsapp',
    'status_change',
    'assignment',
    'follow_up_created',
    'follow_up_completed',
    'quotation_created',
    'quotation_sent',
    'quotation_approved',
    'quotation_rejected',
];

#[Fillable([
    'lead_id',
    'activity_type',
    'subject',
    'body',
    'metadata',
    'customer_visible',
    'created_by_user_id',
    'occurred_at',
])]
class LeadActivity extends Model
{
    public const TYPES = LEAD_ACTIVITY_TYPES;

    /**
     * Disable the default `updated_at` column — this table uses only `created_at`.
     */
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'customer_visible' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    // --------------------------------------------------------------- relations

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // ----------------------------------------------------------- static helpers

    /**
     * Record a status-change activity on a lead.
     */
    public static function recordStatusChange(
        Lead $lead,
        string $fromStatus,
        string $toStatus,
        ?int $actorUserId = null,
    ): self {
        return self::create([
            'lead_id' => $lead->id,
            'activity_type' => 'status_change',
            'subject' => "Status changed: {$fromStatus} → {$toStatus}",
            'metadata' => [
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
            ],
            'customer_visible' => false,
            'created_by_user_id' => $actorUserId,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Record a lead-assignment activity.
     */
    public static function recordAssignment(
        Lead $lead,
        ?int $previousUserId,
        ?int $newUserId,
        ?int $actorUserId = null,
    ): self {
        return self::create([
            'lead_id' => $lead->id,
            'activity_type' => 'assignment',
            'subject' => 'Lead assignment changed',
            'metadata' => [
                'previous_assigned_to_user_id' => $previousUserId,
                'new_assigned_to_user_id' => $newUserId,
            ],
            'customer_visible' => false,
            'created_by_user_id' => $actorUserId,
            'occurred_at' => now(),
        ]);
    }
}
