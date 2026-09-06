<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'previous_status',
    'reason_code',
    'reason_note',
    'cancelled_by_user_id',
    'material_consumed',
    'customization_applied',
    'scrap_incurred',
    'affected_quantity',
    'scrap_quantity',
    'scrap_amount_minor',
    'production_impact_notes',
    'physical_interception_confirmed',
    'shipping_intercept_confirmed_by_user_id',
    'shipping_intercept_confirmed_at',
    'shipping_status_at_cancellation',
])]
class OrderCancellation extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'material_consumed' => 'boolean',
            'customization_applied' => 'boolean',
            'scrap_incurred' => 'boolean',
            'affected_quantity' => 'integer',
            'scrap_quantity' => 'integer',
            'scrap_amount_minor' => 'integer',
            'physical_interception_confirmed' => 'boolean',
            'shipping_intercept_confirmed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function shippingInterceptConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipping_intercept_confirmed_by_user_id');
    }
}
