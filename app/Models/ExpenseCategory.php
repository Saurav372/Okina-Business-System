<?php

namespace App\Models;

use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Class ExpenseCategory
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Generate a unique external public ID.
     */
    protected static function generatePublicId(): string
    {
        return 'EXPCAT-'.Str::upper(Str::random(12));
    }

    protected static function booted(): void
    {
        static::creating(function (ExpenseCategory $category) {
            if (! $category->public_id) {
                do {
                    $publicId = self::generatePublicId();
                } while (self::query()->where('public_id', $publicId)->exists());
                $category->public_id = $publicId;
            }
        });

        // Guard: code is immutable after persistence — reject any dirty write to it.
        static::saving(function (ExpenseCategory $category): void {
            if ($category->exists && $category->isDirty('code')) {
                throw new \LogicException('Expense category code is immutable.');
            }
        });
    }

    /**
     * Enforce domain-level immutability of the category code.
     *
     * @throws \LogicException
     */
    public function ensureCodeIsImmutable(?string $newCode): void
    {
        if ($this->exists && $newCode !== null && $this->getOriginal('code') !== $newCode) {
            throw new \LogicException('Expense category code is immutable.');
        }
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public static bool $mockReferenced = false;

    /**
     * Check if this category is currently referenced by any expense (active or soft-deleted).
     */
    public function isReferenced(): bool
    {
        if (self::$mockReferenced) {
            return true;
        }

        return $this->expenses()->withTrashed()->exists();
    }

    /**
     * Enforce Option A deletion rule: block soft deletion if referenced by any expense.
     *
     * @throws ValidationException
     */
    public function ensureNotReferenced(): void
    {
        if ($this->isReferenced()) {
            throw ValidationException::withMessages([
                'category' => 'Expense category is referenced by existing expenses.',
            ]);
        }
    }

    /**
     * Check if a category can be assigned to a new or resubmitted expense.
     *
     * @throws \LogicException
     */
    public function ensureCanAssignToExpense(): void
    {
        if (! $this->is_active) {
            throw new \LogicException('Expense category is inactive.');
        }
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }
}
