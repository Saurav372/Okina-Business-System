<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'quotation_type',
    'status',
    'lead_id',
    'customer_id',
    'assigned_to_user_id',
    'created_by_user_id',
    'approved_by_user_id',
    'converted_by_user_id',
    'converted_order_id',
    'customer_snapshot',
    'subtotal_amount_minor',
    'discount_amount_minor',
    'shipping_amount_minor',
    'tax_amount_minor',
    'total_amount_minor',
    'currency',
    'current_revision_number',
    'valid_until',
    'sent_at',
    'approved_at',
    'rejected_at',
    'expired_at',
    'converted_at',
    'revised_at',
    'conversion_idempotency_key',
    'customer_note',
    'internal_notes',
])]
class Quotation extends Model
{
    use HasFactory;

    public const TYPE_BULK = 'bulk_quotation';

    public const TYPE_MANUAL = 'manual_quotation';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    public const STATUS_REVISED = 'revised';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CONVERTED = 'converted';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SENT,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_REVISION_REQUESTED,
        self::STATUS_REVISED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
        self::STATUS_CONVERTED,
    ];

    public const TYPES = [
        self::TYPE_BULK,
        self::TYPE_MANUAL,
    ];

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation): void {
            $quotation->public_id ??= 'QT-'.Str::upper(Str::random(12));
            $quotation->status ??= self::STATUS_DRAFT;
            $quotation->currency ??= 'INR';
            $quotation->current_revision_number ??= 1;
        });
    }

    protected function casts(): array
    {
        return [
            'customer_snapshot' => 'array',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'expired_at' => 'datetime',
            'converted_at' => 'datetime',
            'revised_at' => 'datetime',
            'subtotal_amount_minor' => 'integer',
            'discount_amount_minor' => 'integer',
            'shipping_amount_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'total_amount_minor' => 'integer',
            'current_revision_number' => 'integer',
        ];
    }

    // --------------------------------------------------------------- relations

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function converter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_user_id');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sort_order');
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        if ($this->status === $targetStatus) {
            return false;
        }

        // Terminal statuses cannot transition
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_CONVERTED], true)) {
            return false;
        }

        return match ($this->status) {
            self::STATUS_DRAFT => in_array($targetStatus, [
                self::STATUS_SENT,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_SENT => in_array($targetStatus, [
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_REVISION_REQUESTED,
                self::STATUS_EXPIRED,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_REVISION_REQUESTED => in_array($targetStatus, [
                self::STATUS_REVISED,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_REVISED => in_array($targetStatus, [
                self::STATUS_SENT,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_REJECTED => in_array($targetStatus, [
                self::STATUS_REVISION_REQUESTED,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_EXPIRED => in_array($targetStatus, [
                self::STATUS_REVISED,
                self::STATUS_CANCELLED,
            ], true),

            self::STATUS_APPROVED => in_array($targetStatus, [
                self::STATUS_CONVERTED,
                self::STATUS_CANCELLED,
            ], true),

            default => false,
        };
    }
}
