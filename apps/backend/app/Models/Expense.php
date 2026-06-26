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

    protected $fillable = [
        'expense_category_id',
        'amount_minor',
        'currency',
        'notes',
        'recorded_by_user_id',
        'reference',
        'status',
        'occurred_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'recorded_by_user_id' => 'integer',
        'expense_category_id' => 'integer',
        'occurred_at' => 'date',
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
}
