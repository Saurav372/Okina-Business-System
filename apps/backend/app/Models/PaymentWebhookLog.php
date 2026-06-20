<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider',
    'provider_event_id',
    'event_type',
    'provider_order_id',
    'provider_payment_id',
    'provider_refund_id',
    'payment_attempt_id',
    'payment_id',
    'refund_id',
    'processing_status',
    'signature_verified',
    'payload_summary',
    'error_message',
    'received_at',
    'processed_at',
])]
class PaymentWebhookLog extends Model
{
    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'payload_summary' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function paymentAttempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }
}
