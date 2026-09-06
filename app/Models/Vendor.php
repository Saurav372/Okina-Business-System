<?php

namespace App\Models;

use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'vendor_code',
    'name',
    'status',
    'contact_name',
    'email',
    'phone',
    'gstin',
    'address_line1',
    'address_line2',
    'city',
    'state',
    'postal_code',
    'country_code',
    'payment_terms',
    'notes',
    'created_by_user_id',
    'updated_by_user_id',
])]
#[Hidden(['deleted_at'])]
class Vendor extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
        ];
    }

    /**
     * Mutator for GSTIN: Trim and uppercase.
     */
    public function setGstinAttribute(?string $value): void
    {
        $this->attributes['gstin'] = $value ? Str::upper(trim($value)) : null;
    }

    /**
     * Mutator for country_code: Trim and uppercase, defaulting to 'IN'.
     */
    public function setCountryCodeAttribute(?string $value): void
    {
        $this->attributes['country_code'] = $value ? Str::upper(trim($value)) : 'IN';
    }

    /**
     * Scope active vendors.
     *
     * @param  Builder<Vendor>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', VendorStatus::ACTIVE);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class, 'vendor_id');
    }
}
