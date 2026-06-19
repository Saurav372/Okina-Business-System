<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'customer_type',
    'first_name',
    'last_name',
    'display_name',
    'company_name',
    'name',
    'email',
    'phone',
    'whatsapp_phone',
    'status',
    'source',
    'accepts_marketing',
    'email_verified_at',
    'phone_verified_at',
    'last_login_at',
    'created_by_user_id',
    'updated_by_user_id',
    'merged_into_customer_id',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Customer $customer): void {
            $customer->public_id ??= 'CUS-'.strtoupper(Str::random(10));
            $customer->customer_type ??= 'individual';
            $customer->display_name ??= $customer->name ?? trim(
                collect([$customer->first_name, $customer->last_name])->filter()->join(' ')
            );
            $customer->status ??= 'active';
            $customer->source ??= 'website';
            $customer->accepts_marketing ??= false;
        });
    }

    public function account(): HasOne
    {
        return $this->hasOne(CustomerAccount::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
