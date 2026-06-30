<?php

namespace App\Models;

use App\Enums\AuditActorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditLog extends Model
{
    /**
     * Disable the default updated_at column — audit logs are append-only.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'action',
        'module',
        'actor_type',
        'actor_user_id',
        'actor_customer_id',
        'actor_label_snapshot',
        'subject_type',
        'subject_id',
        'subject_public_id',
        'summary',
        'old_values',
        'new_values',
        'metadata',
        'request_id',
        'idempotency_key',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_type' => AuditActorType::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model and enforce strong append-only immutability.
     */
    protected static function booted(): void
    {
        static::saving(function (AuditLog $model): void {
            if ($model->exists) {
                throw new \LogicException('Audit logs are append-only and cannot be modified.');
            }
        });

        static::updating(function (AuditLog $model): void {
            throw new \LogicException('Audit logs are append-only and cannot be modified.');
        });

        static::deleting(function (AuditLog $model): void {
            throw new \LogicException('Audit logs are append-only and cannot be deleted.');
        });
    }

    // ---------------------------------------------------------------- Relationships

    /**
     * Get the actor user (staff) associated with the audit log.
     */
    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Get the actor customer associated with the audit log.
     */
    public function actorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'actor_customer_id');
    }

    /**
     * Get the related records associated with the audit log.
     */
    public function relatedRecords(): HasMany
    {
        return $this->hasMany(AuditLogRelatedRecord::class, 'audit_log_id');
    }
}
