<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'order_id',
    'public_id',
    'provider',
    'attempt_type',
    'status',
    'amount_minor',
    'currency',
    'idempotency_key',
    'gateway_order_id',
    'gateway_payment_id',
    'gateway_reference',
    'checkout_url',
    'initiated_at',
    'completed_at',
])]
class PaymentAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PaymentAttempt $attempt): void {
            $attempt->public_id ??= 'PATT-'.Str::upper(Str::random(12));
            $attempt->provider ??= 'cashfree';
            $attempt->attempt_type ??= 'website_checkout';
            $attempt->status ??= 'created';
            $attempt->currency ??= 'INR';
            $attempt->amount_minor ??= 0;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
