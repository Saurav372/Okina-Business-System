<?php

namespace App\Models;

use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id',
    'address_type',
    'label',
    'contact_name',
    'phone',
    'company_name',
    'gstin',
    'address_line_1',
    'address_line_2',
    'landmark',
    'city',
    'state',
    'postal_code',
    'country_code',
    'is_default_shipping',
    'is_default_billing',
    'delivery_notes',
    'created_by_user_id',
    'updated_by_user_id',
])]
class CustomerAddress extends Model
{
    /** @use HasFactory<CustomerAddressFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_SHIPPING = 'shipping';

    public const TYPE_BILLING = 'billing';

    public const TYPE_BOTH = 'both';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
