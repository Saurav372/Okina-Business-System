<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogRelatedRecord extends Model
{
    /**
     * Disable the default updated_at column — related records are append-only.
     */
    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'audit_log_id',
        'related_type',
        'related_id',
        'related_public_id',
        'relation',
    ];

    /**
     * Bootstrap the model and enforce strong append-only immutability.
     */
    protected static function booted(): void
    {
        static::saving(function (AuditLogRelatedRecord $model): void {
            if ($model->exists) {
                throw new \LogicException('Audit log related records are append-only and cannot be modified.');
            }
        });

        static::updating(function (AuditLogRelatedRecord $model): void {
            throw new \LogicException('Audit log related records are append-only and cannot be modified.');
        });

        static::deleting(function (AuditLogRelatedRecord $model): void {
            throw new \LogicException('Audit log related records are append-only and cannot be deleted.');
        });
    }

    // ---------------------------------------------------------------- Relationships

    /**
     * Get the parent audit log associated with this related record link.
     */
    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class, 'audit_log_id');
    }
}
