<?php

namespace App\Models;

use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class ExpenseCategory
 *
 * Invariants:
 * - code: stable machine identifier and is immutable once created. It should never be changed by seeders, controllers, or CLI tools.
 * - public_id: external identifier and is immutable once generated.
 * - Expense categories are reference/master data. They are created rarely, updated infrequently, and should never be repurposed by changing their semantic meaning.
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $code
 * @property string? $description
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
    private static function generatePublicId(): string
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

        static::saving(function (ExpenseCategory $category) {
            if ($category->exists && $category->isDirty('code')) {
                $category->ensureCodeIsImmutable($category->code);
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
     * Check if this category is currently referenced by any expense.
     */
    public function isReferenced(): bool
    {
        if (self::$mockReferenced) {
            return true;
        }

        // Placeholder until Expense model exists in C5.3.2.
        // Categories cannot yet be referenced.
        return false;
    }
}
