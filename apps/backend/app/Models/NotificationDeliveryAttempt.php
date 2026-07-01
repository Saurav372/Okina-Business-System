<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDeliveryAttempt extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'notification_log_id',
        'status',
        'provider_reference',
        'error_message',
        'response_payload',
        'attempted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- Relationships

    /**
     * Get the parent notification log.
     */
    public function notificationLog(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class, 'notification_log_id');
    }
}
