<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationLog extends Model
{
    // Status Constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SKIPPED = 'skipped';

    // Recipient Type Constants
    public const RECIPIENT_CUSTOMER = 'customer';

    public const RECIPIENT_USER = 'user';

    public const RECIPIENT_EXTERNAL = 'external';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_type',
        'template_id',
        'template_key',
        'template_version',
        'channel',
        'status',
        'recipient_type',
        'recipient_user_id',
        'recipient_customer_id',
        'recipient_address',
        'subject_rendered',
        'body_summary',
        'payload',
        'related_type',
        'related_id',
        'dedupe_key',
        'scheduled_at',
        'sent_at',
        'failed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template_version' => 'integer',
            'payload' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    // ---------------------------------------------------------------- Relationships

    /**
     * Get the template associated with this notification log.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Get the recipient user.
     */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Get the recipient customer.
     */
    public function recipientCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'recipient_customer_id');
    }

    /**
     * Get the delivery attempts for this notification log.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationDeliveryAttempt::class, 'notification_log_id');
    }
}
