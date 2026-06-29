<?php

namespace App\Models;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidPurchaseOrderExpectedDateException;
use App\Exceptions\InvalidPurchaseOrderPaymentStatusTransitionException;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'vendor_id',
    'public_id',
    'status',
    'payment_status',
    'ordered_at',
    'expected_at',
    'received_at',
    'cancelled_at',
    'subtotal_amount_minor',
    'tax_amount_minor',
    'shipping_amount_minor',
    'discount_amount_minor',
    'total_amount_minor',
    'currency',
    'notes',
    'created_by_user_id',
    'updated_by_user_id',
])]
class VendorOrder extends Model
{
    public const STATUS_TRANSITIONS = [
        'draft' => ['ordered', 'cancelled'],
        'ordered' => ['partially_received', 'received', 'cancelled'],
        'partially_received' => ['received'],
        'received' => ['closed'],
        'cancelled' => [],
        'closed' => [],
    ];

    public const PAYMENT_STATUS_TRANSITIONS = [
        'unpaid' => ['partially_paid', 'paid', 'cancelled'],
        'partially_paid' => ['paid'],
        'paid' => [],
        'cancelled' => [],
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorOrderStatus::class,
            'payment_status' => VendorOrderPaymentStatus::class,
            'ordered_at' => 'datetime',
            'expected_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal_amount_minor' => 'integer',
            'tax_amount_minor' => 'integer',
            'shipping_amount_minor' => 'integer',
            'discount_amount_minor' => 'integer',
            'total_amount_minor' => 'integer',
        ];
    }

    /**
     * Mutator for currency: Trim and uppercase, defaulting to 'INR'.
     */
    public function setCurrencyAttribute(?string $value): void
    {
        $this->attributes['currency'] = $value ? Str::upper(trim($value)) : 'INR';
    }

    /**
     * Calculate PO total amount (pure method, does not mutate model attributes).
     */
    public function calculateTotalAmount(): int
    {
        $subtotal = $this->subtotal_amount_minor ?? 0;
        $tax = $this->tax_amount_minor ?? 0;
        $shipping = $this->shipping_amount_minor ?? 0;
        $discount = $this->discount_amount_minor ?? 0;

        return max(0, $subtotal + $tax + $shipping - $discount);
    }

    /**
     * Check if status transition is allowed.
     */
    public function canTransitionStatusTo(VendorOrderStatus $target): bool
    {
        if ($this->status === $target) {
            return false;
        }

        $current = $this->status ? $this->status->value : 'draft';
        $allowed = self::STATUS_TRANSITIONS[$current] ?? [];

        return in_array($target->value, $allowed, true);
    }

    /**
     * Check if payment status transition is allowed.
     */
    public function canTransitionPaymentStatusTo(VendorOrderPaymentStatus $target): bool
    {
        if ($this->payment_status === $target) {
            return false;
        }

        $current = $this->payment_status ? $this->payment_status->value : 'unpaid';
        $allowed = self::PAYMENT_STATUS_TRANSITIONS[$current] ?? [];

        return in_array($target->value, $allowed, true);
    }

    /**
     * Transition the purchase order status.
     * Mutates in memory only.
     */
    public function transitionStatusTo(VendorOrderStatus $target): void
    {
        if ($this->status === $target) {
            throw new InvalidPurchaseOrderStatusTransitionException(
                "Cannot transition purchase order from {$this->status->value} to {$target->value}."
            );
        }

        if (! $this->canTransitionStatusTo($target)) {
            throw new InvalidPurchaseOrderStatusTransitionException(
                "Cannot transition purchase order from {$this->status->value} to {$target->value}."
            );
        }

        $this->status = $target;

        if ($target === VendorOrderStatus::ORDERED) {
            $this->ordered_at ??= Carbon::now();
        } elseif ($target === VendorOrderStatus::RECEIVED) {
            $this->received_at ??= Carbon::now();
        } elseif ($target === VendorOrderStatus::CANCELLED) {
            $this->cancelled_at ??= Carbon::now();
        }
    }

    /**
     * Transition the purchase order payment status.
     * Mutates in memory only.
     */
    public function transitionPaymentStatusTo(VendorOrderPaymentStatus $target): void
    {
        if ($this->payment_status === $target) {
            throw new InvalidPurchaseOrderPaymentStatusTransitionException(
                "Cannot transition purchase order payment status from {$this->payment_status->value} to {$target->value}."
            );
        }

        if (! $this->canTransitionPaymentStatusTo($target)) {
            throw new InvalidPurchaseOrderPaymentStatusTransitionException(
                "Cannot transition purchase order payment status from {$this->payment_status->value} to {$target->value}."
            );
        }

        $this->payment_status = $target;
    }

    /**
     * Set the expected delivery date explicitly.
     */
    public function changeExpectedAt(?Carbon $expectedAt): void
    {
        if ($expectedAt && $this->ordered_at && $expectedAt->lt($this->ordered_at)) {
            throw new InvalidPurchaseOrderExpectedDateException(
                'Expected delivery date cannot be prior to ordered date.'
            );
        }

        $this->expected_at = $expectedAt;
    }

    /**
     * Determine if financial fields can be edited (only in draft status).
     */
    public function isEditable(): bool
    {
        return $this->status === VendorOrderStatus::DRAFT;
    }

    public function items(): HasMany
    {
        return $this->hasMany(VendorOrderItem::class, 'vendor_order_id');
    }

    /**
     * Recalculate purchase order totals based on current line items in database.
     * Mutates in memory only.
     */
    public function recalculateTotals(): void
    {
        $this->load('items');

        $subtotal = 0;
        $tax = 0;

        foreach ($this->items as $item) {
            $subtotal += ($item->quantity_ordered * $item->unit_cost_minor);
            $tax += $item->tax_amount_minor;
        }

        $this->subtotal_amount_minor = $subtotal;
        $this->tax_amount_minor = $tax;
        $this->total_amount_minor = $this->calculateTotalAmount();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
