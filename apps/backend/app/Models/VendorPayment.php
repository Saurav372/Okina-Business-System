<?php

namespace App\Models;

use App\Enums\VendorPaymentMethod;
use App\Enums\VendorPaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vendor_order_id',
    'status',
    'payment_method',
    'amount_minor',
    'currency',
    'reference',
    'paid_at',
    'recorded_by_user_id',
    'notes',
])]
class VendorPayment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => VendorPaymentStatus::class,
            'payment_method' => VendorPaymentMethod::class,
            'amount_minor' => 'integer',
            'recorded_by_user_id' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
