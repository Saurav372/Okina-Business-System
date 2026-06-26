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

    /**
     * Visual state graph:
     * requested
     *  ├── approved
     *  │     ├── processing
     *  │     │      ├── succeeded
     *  │     │      └── failed
     *  │     │             └── processing
     *  │     └── cancelled
     *  └── cancelled
     *
     * All refund lifecycle changes must be represented by updates to ALLOWED_TRANSITIONS.
     * Transition helpers and validation derive exclusively from this graph.
     *
     * Note on processed_at:
     * The processed_at timestamp represents the "latest processing milestone".
     * It is updated when starting processing, succeeding, or failing.
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_REQUESTED => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [
            self::STATUS_SUCCEEDED,
            self::STATUS_FAILED,
        ],
        // Retry after transient gateway failure
        self::STATUS_FAILED => [self::STATUS_PROCESSING],
    ];

    public const TRANSITION_ERRORS = [
        self::STATUS_APPROVED => 'Only requested refunds can be approved.',
        self::STATUS_PROCESSING => 'Only approved or failed refunds can start processing.',
        self::STATUS_SUCCEEDED => 'Only processing refunds can succeed.',
        self::STATUS_FAILED => 'Only processing refunds can fail.',
        self::STATUS_CANCELLED => 'Only requested or approved refunds can be cancelled.',
    ];

    public const ERROR_ONLY_REQUESTED_CAN_BE_APPROVED = 'Only requested refunds can be approved.';

    public function canTransitionTo(string $targetStatus): bool
    {
        return in_array($targetStatus, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function ensureCanTransitionTo(string $targetStatus): void
    {
        if (!$this->canTransitionTo($targetStatus)) {
            throw new \LogicException(self::TRANSITION_ERRORS[$targetStatus] ?? "Invalid status transition to {$targetStatus}");
        }
    }

    private function applyTransition(string $targetStatus): void
    {
        $this->ensureCanTransitionTo($targetStatus);
        $this->status = $targetStatus;
    }

    public function canBeApproved(): bool
    {
        return $this->canTransitionTo(self::STATUS_APPROVED);
    }

    public function ensureCanBeApproved(): void
    {
        $this->ensureCanTransitionTo(self::STATUS_APPROVED);
    }

    public function canBeProcessing(): bool
    {
        return $this->canTransitionTo(self::STATUS_PROCESSING);
    }

    public function ensureCanBeProcessing(): void
    {
        $this->ensureCanTransitionTo(self::STATUS_PROCESSING);
    }

    public function canBeSucceeded(): bool
    {
        return $this->canTransitionTo(self::STATUS_SUCCEEDED);
    }

    public function ensureCanBeSucceeded(): void
    {
        $this->ensureCanTransitionTo(self::STATUS_SUCCEEDED);
    }

    public function canBeFailed(): bool
    {
        return $this->canTransitionTo(self::STATUS_FAILED);
    }

    public function ensureCanBeFailed(): void
    {
        $this->ensureCanTransitionTo(self::STATUS_FAILED);
    }

    public function canBeCancelled(): bool
    {
        return $this->canTransitionTo(self::STATUS_CANCELLED);
    }

    public function ensureCanBeCancelled(): void
    {
        $this->ensureCanTransitionTo(self::STATUS_CANCELLED);
    }

    public function approve(User $user, ?\Illuminate\Support\Carbon $approvedAt = null): void
    {
        $this->applyTransition(self::STATUS_APPROVED);

        $this->approved_by_user_id = $user->id;
        $this->approved_at = $approvedAt ?? \Illuminate\Support\Carbon::now();
    }

    public function markProcessing(User $user, ?\Illuminate\Support\Carbon $startedAt = null, ?string $providerRefundId = null, ?string $providerPaymentId = null, ?string $providerReference = null): void
    {
        $this->applyTransition(self::STATUS_PROCESSING);

        $this->processed_by_user_id = $user->id;
        $this->processed_at = $startedAt ?? \Illuminate\Support\Carbon::now();
        $this->reason_code = null;
        $this->reason_note = null;

        $this->provider_refund_id = $providerRefundId ?? $this->provider_refund_id;
        $this->provider_payment_id = $providerPaymentId ?? $this->provider_payment_id;
        $this->provider_reference = $providerReference ?? $this->provider_reference;
    }

    public function markSucceeded(?\Illuminate\Support\Carbon $succeededAt = null, ?string $providerRefundId = null, ?string $providerPaymentId = null, ?string $providerReference = null): void
    {
        $this->applyTransition(self::STATUS_SUCCEEDED);

        $this->processed_at = $succeededAt ?? \Illuminate\Support\Carbon::now();

        $this->provider_refund_id = $providerRefundId ?? $this->provider_refund_id;
        $this->provider_payment_id = $providerPaymentId ?? $this->provider_payment_id;
        $this->provider_reference = $providerReference ?? $this->provider_reference;
    }

    public function markFailed(?\Illuminate\Support\Carbon $failedAt = null, ?string $reasonCode = null, ?string $reasonNote = null): void
    {
        $this->applyTransition(self::STATUS_FAILED);

        $this->processed_at = $failedAt ?? \Illuminate\Support\Carbon::now();
        $this->reason_code = $reasonCode;
        $this->reason_note = $reasonNote;
    }

    public function cancel(): void
    {
        $this->applyTransition(self::STATUS_CANCELLED);
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
