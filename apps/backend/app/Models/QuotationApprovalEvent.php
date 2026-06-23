<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quotation_id',
    'event_type',
    'revision_number',
    'actor_type',
    'actor_user_id',
    'actor_customer_id',
    'actor_name_snapshot',
    'actor_email_snapshot',
    'note',
    'idempotency_key',
    'occurred_at',
])]
class QuotationApprovalEvent extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'actor_customer_id');
    }
}
