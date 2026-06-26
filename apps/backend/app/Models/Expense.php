<?php

namespace App\Models;

use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class Expense
 *
 * Invariants:
 * - public_id: external identifier and is immutable once generated.
 * - recorded_by_user_id: immutable once recorded.
 * - TODO: Status transitions rules will be enforced in C5.3.3.
 *
 * @property int $id
 * @property string $public_id
 * @property int $expense_category_id
 * @property int $amount_minor
 * @property string $currency
 * @property string|null $notes
 * @property int $recorded_by_user_id
 * @property string|null $reference
 * @property string $status
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    private const HISTORY_SCHEMA_VERSION = 1;

    protected $fillable = [
        'expense_category_id',
        'amount_minor',
        'currency',
        'notes',
        'recorded_by_user_id',
        'reference',
        'status',
        'occurred_at',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'recorded_by_user_id' => 'integer',
        'expense_category_id' => 'integer',
        'occurred_at' => 'date',
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Generate a unique external public ID.
     */
    protected static function generatePublicId(): string
    {
        return 'EXP-'.Str::upper(Str::random(12));
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense) {
            if (! $expense->public_id) {
                do {
                    $publicId = self::generatePublicId();
                } while (self::query()->where('public_id', $publicId)->exists());
                $expense->public_id = $publicId;
            }
        });

        static::saving(function (Expense $expense) {
            if ($expense->exists && $expense->isDirty('public_id')) {
                throw new \LogicException('Public ID is immutable.');
            }
            if ($expense->exists && $expense->isDirty('recorded_by_user_id')) {
                throw new \LogicException('Recorded by user is immutable.');
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id')->withTrashed();
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $current = $this->status;

        if ($current === self::STATUS_APPROVED) {
            return false;
        }

        if ($targetStatus === self::STATUS_PENDING_APPROVAL) {
            return in_array($current, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
        }

        if ($current === self::STATUS_PENDING_APPROVAL) {
            return in_array($targetStatus, [self::STATUS_APPROVED, self::STATUS_REJECTED], true);
        }

        return false;
    }

    public function ensureCanTransitionTo(string $targetStatus): void
    {
        if (! $this->canTransitionTo($targetStatus)) {
            throw new \LogicException("Cannot transition expense from {$this->status} to {$targetStatus}.");
        }
    }

    public function submit(User $user): void
    {
        $this->ensureCanTransitionTo(self::STATUS_PENDING_APPROVAL);
        $oldStatus = $this->status;
        $this->status = self::STATUS_PENDING_APPROVAL;
        $this->appendHistoryEntry('submit', $oldStatus, self::STATUS_PENDING_APPROVAL, $user->id, now());
        $this->save();
    }

    public function approve(User $user): void
    {
        $this->ensureCanTransitionTo(self::STATUS_APPROVED);
        $oldStatus = $this->status;
        $transitionedAt = now();
        $this->status = self::STATUS_APPROVED;
        $this->approved_at = $transitionedAt;
        $this->appendHistoryEntry('approve', $oldStatus, self::STATUS_APPROVED, $user->id, $transitionedAt);
        $this->save();
    }

    public function reject(User $user, string $reason): void
    {
        $this->ensureCanTransitionTo(self::STATUS_REJECTED);
        $oldStatus = $this->status;
        $this->status = self::STATUS_REJECTED;
        $this->appendHistoryEntry('reject', $oldStatus, self::STATUS_REJECTED, $user->id, now(), $reason);
        $this->save();
    }

    private function appendHistoryEntry(string $action, string $from, string $to, int $userId, \DateTimeInterface $transitionedAt, ?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['version'] ??= self::HISTORY_SCHEMA_VERSION;
        $history = $metadata['history'] ?? [];

        $history[] = [
            'action' => $action,
            'from' => $from,
            'to' => $to,
            'performed_by_user_id' => $userId,
            'performed_at' => $transitionedAt->format('c'),
            'reason' => $reason,
        ];

        $metadata['history'] = $history;
        $this->metadata = $metadata;
    }
}
