<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class QuotationRevision
 *
 * This model represents an archived, immutable historical version of a quotation.
 * It stores a snapshot of the quotation's state (including totals, line items, and customer snapshot)
 * before a new revision is made active. It does NOT store security-related metadata like approval tokens.
 */
#[Fillable([
    'quotation_id',
    'revision_number',
    'previous_status',
    'quotation_type',
    'valid_until',
    'customer_note',
    'subtotal_amount_minor',
    'discount_amount_minor',
    'shipping_amount_minor',
    'tax_amount_minor',
    'total_amount_minor',
    'currency',
    'items_snapshot',
    'customer_snapshot',
    'reason',
    'created_by_user_id',
])]
class QuotationRevision extends Model
{
    use HasFactory;

    // This is an immutable archive table; only track created_at
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'valid_until' => 'date',
            'subtotal_amount_minor' => 'integer',
            'discount_amount_minor' => 'integer',
            'shipping_amount_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'total_amount_minor' => 'integer',
            'items_snapshot' => 'array',
            'customer_snapshot' => 'array',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
