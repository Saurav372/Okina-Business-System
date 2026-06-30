<?php

namespace App\Models;

use App\Enums\LeadFollowUpStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'lead_id',
    'assigned_to_user_id',
    'status',
    'due_at',
    'completed_at',
    'completed_by_user_id',
    'snoozed_until',
    'subject',
    'notes',
    'notification_key',
    'created_by_user_id',
])]
class LeadFollowUp extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => LeadFollowUpStatus::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    // --------------------------------------------------------------- relations

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    // ------------------------------------------------------------------ scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LeadFollowUpStatus::PENDING->value);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', LeadFollowUpStatus::COMPLETED->value);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->pending()->where('due_at', '<', now());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query
            ->pending()
            ->whereBetween('due_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ]);
    }
}
