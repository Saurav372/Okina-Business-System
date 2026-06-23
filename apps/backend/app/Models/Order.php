<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'public_id',
    'order_type',
    'order_source',
    'status',
    'customer_id',
    'shipping_address_id',
    'billing_address_id',
    'customer_snapshot',
    'shipping_address_snapshot',
    'billing_address_snapshot',
    'subtotal_amount_minor',
    'discount_amount_minor',
    'shipping_amount_minor',
    'tax_amount_minor',
    'total_amount_minor',
    'currency',
    'design_approved',
    'design_approved_at',
    'design_approved_by_user_id',
    'design_notes',
    'customer_notes',
    'internal_notes',
    'placed_at',
    'confirmed_at',
    'cancelled_at',
    'refunded_at',
    'created_by_user_id',
    'updated_by_user_id',
    'idempotency_key',
    'design_status',
    'design_issue_message',
    'production_status',
    'shipping_status',
    'ready_to_ship_at',
    'shipped_at',
    'delivered_at',
    'courier_name',
    'tracking_number',
    'tracking_url',
    'estimated_delivery_at',
    'cancellation_reason',
])]
#[Hidden(['deleted_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->public_id ??= 'OD-'.Str::upper(Str::random(12));
            $order->order_type ??= OrderType::WebsiteOrder->value();
            $order->order_source ??= 'website';
            $order->status ??= OrderStatus::PendingPayment->value();
            $order->currency ??= 'INR';
            $order->design_approved ??= false;
            $order->subtotal_amount_minor ??= 0;
            $order->discount_amount_minor ??= 0;
            $order->shipping_amount_minor ??= 0;
            $order->tax_amount_minor ??= 0;
            $order->total_amount_minor ??= 0;
            $order->placed_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'customer_snapshot' => 'array',
            'shipping_address_snapshot' => 'array',
            'billing_address_snapshot' => 'array',
            'subtotal_amount_minor' => 'integer',
            'discount_amount_minor' => 'integer',
            'shipping_amount_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'total_amount_minor' => 'integer',
            'design_approved' => 'boolean',
            'design_approved_at' => 'datetime',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'ready_to_ship_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
        ];
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            'pending_payment',
            'confirmed',
            'in_production',
            'ready_to_ship',
        ], true);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function scopeWebsiteOrders(Builder $query): Builder
    {
        return $query
            ->where('order_type', OrderType::WebsiteOrder->value())
            ->where('order_source', 'website');
    }

    public function scopePlacedFrom(Builder $query, string $date): Builder
    {
        return $query->whereDate('placed_at', '>=', $date);
    }

    public function scopePlacedUntil(Builder $query, string $date): Builder
    {
        return $query->whereDate('placed_at', '<=', $date);
    }

    public function scopeDesignApproved(Builder $query, bool $approved = true): Builder
    {
        return $query->where('design_approved', $approved);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'billing_address_id');
    }

    public function designApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'design_approved_by_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
