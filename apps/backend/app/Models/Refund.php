<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'order_id',
    'payment_id',
    'provider',
    'refund_type',
    'status',
    'amount_minor',
    'currency',
    'reason_code',
    'reason_note',
    'provider_refund_id',
    'provider_payment_id',
    'provider_reference',
    'requested_by_user_id',
    'approved_by_user_id',
    'processed_by_user_id',
    'requested_at',
    'approved_at',
    'processed_at',
    'metadata',
])]
class Refund extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_FULL = 'full';

    public const TYPE_PARTIAL = 'partial';

    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    public function scopeReservesBalance($query)
    {
        return $query->whereNotIn('status', [self::STATUS_FAILED, self::STATUS_CANCELLED]);
    }

    protected static function booted(): void
    {
        static::saving(function (Refund $refund) {
            if ($refund->status === 'succeeded' && is_null($refund->payment_id)) {
                throw new \InvalidArgumentException('A succeeded refund must reference a valid recorded payment.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function webhookLog(): HasOne
    {
        return $this->hasOne(PaymentWebhookLog::class);
    }
}
