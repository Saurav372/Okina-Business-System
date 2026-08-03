<?php

namespace App\Models;

use App\Support\Expenses\ExpenseAttachmentCodeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class ExpenseAttachment
 *
 * @property int $id
 * @property string $public_id
 * @property int $expense_id
 * @property string $original_name
 * @property string $storage_path
 * @property string $disk
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $checksum
 * @property int|null $uploaded_by_user_id
 * @property Carbon $uploaded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ExpenseAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'original_name',
        'storage_path',
        'disk',
        'mime_type',
        'size_bytes',
        'checksum',
        'uploaded_by_user_id',
        'uploaded_at',
    ];

    protected $casts = [
        'expense_id' => 'integer',
        'size_bytes' => 'integer',
        'uploaded_by_user_id' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ExpenseAttachment $attachment) {
            if (! $attachment->public_id) {
                $attachment->public_id = ExpenseAttachmentCodeGenerator::generate();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
