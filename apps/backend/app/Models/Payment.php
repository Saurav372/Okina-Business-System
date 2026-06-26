<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'payment_attempt_id',
    'payment_schedule_id',
    'payment_type',
    'provider',
    'method',
    'status',
    'amount_minor',
    'currency',
    'provider_payment_id',
    'provider_order_id',
    'provider_reference',
    'receipt_number',
    'gateway_fee_minor',
    'net_amount_minor',
    'paid_at',
    'recorded_by_user_id',
    'verified_by_user_id',
    'notes',
    'metadata',
])]
class Payment extends Model
{
    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_VOIDED = 'voided';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_UPI = 'upi';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_OTHER = 'other';

    public const METHODS = [
        self::METHOD_CASH,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_UPI,
        self::METHOD_CHEQUE,
        self::METHOD_OTHER,
    ];

    public const TYPE_FULL = 'full';

    public const TYPE_ADVANCE = 'advance';

    public const TYPE_PARTIAL = 'partial';

    public const TYPE_INSTALLMENT = 'installment';

    public const TYPE_FINAL_BALANCE = 'final_balance';

    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    public const TYPES = [
        self::TYPE_FULL,
        self::TYPE_ADVANCE,
        self::TYPE_PARTIAL,
        self::TYPE_INSTALLMENT,
        self::TYPE_FINAL_BALANCE,
        self::TYPE_MANUAL_ADJUSTMENT,
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'gateway_fee_minor' => 'integer',
            'net_amount_minor' => 'integer',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
