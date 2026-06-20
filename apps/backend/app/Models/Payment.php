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
