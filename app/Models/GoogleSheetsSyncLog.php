<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoogleSheetsSyncLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'model_class',
        'model_id',
        'unique_key',
        'unique_value',
        'status',
        'attempts',
        'payload_hash',
        'payload',
        'error_message',
        'triggered_by',
        'user_id',
        'job_uuid',
        'connection',
        'queue',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'completed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Get the parent model (polymorphic).
     */
    public function model(): MorphTo
    {
        return $this->morphTo(null, 'model_class', 'model_id');
    }

    /**
     * Get the user who triggered the sync (manual/retry).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
