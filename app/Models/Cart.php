<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'customer_id',
    'cart_token',
    'status',
    'last_activity_at',
])]
class Cart extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CONVERTED = 'converted';

    public const STATUS_ABANDONED = 'abandoned';

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Cart $cart): void {
            $cart->public_id ??= 'CRT-'.Str::upper(Str::random(12));
            $cart->status ??= self::STATUS_ACTIVE;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
