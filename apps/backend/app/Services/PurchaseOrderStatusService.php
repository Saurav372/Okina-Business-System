<?php

namespace App\Services;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Events\AuditEvent;
use App\Exceptions\InvalidPurchaseOrderPaymentStatusTransitionException;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderStatusService
{
    /**
     * Allowed manual status transitions initiated via UI or API endpoints.
     */
    public const MANUAL_TRANSITIONS = [
        'draft' => ['ordered', 'cancelled'],
        'ordered' => ['cancelled'],
    ];

    /**
     * Transition a Purchase Order status or payment status via manual action.
     */
    public function transition(
        VendorOrder $order,
        VendorOrderStatus|string|null $targetStatus = null,
        ?User $actor = null,
        VendorOrderPaymentStatus|string|null $targetPaymentStatus = null
    ): VendorOrder {
        $actor = $actor ?: Auth::user();

        $targetEnum = null;
        if ($targetStatus !== null && $targetStatus !== '') {
            $targetEnum = $targetStatus instanceof VendorOrderStatus
                ? $targetStatus
                : VendorOrderStatus::from($targetStatus);
        }

        $targetPaymentEnum = null;
        if ($targetPaymentStatus !== null && $targetPaymentStatus !== '') {
            $targetPaymentEnum = $targetPaymentStatus instanceof VendorOrderPaymentStatus
                ? $targetPaymentStatus
                : VendorOrderPaymentStatus::from($targetPaymentStatus);
        }

        $currentStatusStr = $order->status->value;
        $previousPaymentStatusStr = $order->payment_status ? $order->payment_status->value : 'unpaid';

        // 1. Prohibit manual transitions to partially_received or received
        if ($targetEnum !== null && in_array($targetEnum, [VendorOrderStatus::PARTIALLY_RECEIVED, VendorOrderStatus::RECEIVED], true)) {
            throw new InvalidPurchaseOrderStatusTransitionException(
                "Status [{$targetEnum->value}] cannot be set manually. Orders automatically transition to received statuses via Stock Receiving."
            );
        }

        // 2. Reject self-transitions or unpermitted manual status transitions
        if ($targetEnum !== null && $targetEnum !== $order->status) {
            $allowed = self::MANUAL_TRANSITIONS[$currentStatusStr] ?? [];
            if (! in_array($targetEnum->value, $allowed, true)) {
                throw new InvalidPurchaseOrderStatusTransitionException(
                    "Cannot transition purchase order from [{$currentStatusStr}] to [{$targetEnum->value}]."
                );
            }
        } elseif ($targetEnum !== null && $targetEnum === $order->status) {
            throw new InvalidPurchaseOrderStatusTransitionException(
                "Cannot transition purchase order from {$currentStatusStr} to {$targetEnum->value}."
            );
        }

        // 3. Reject self-transitions on payment status
        if ($targetPaymentEnum !== null && $order->payment_status === $targetPaymentEnum) {
            throw new InvalidPurchaseOrderPaymentStatusTransitionException(
                "Cannot transition purchase order payment status from {$previousPaymentStatusStr} to {$targetPaymentEnum->value}."
            );
        }

        // 4. Safeguard cancellation rules: reject if received units > 0 or net payments > 0
        if ($targetEnum === VendorOrderStatus::CANCELLED) {
            $totalReceived = $order->items()->sum('quantity_received');
            $totalPaid = $order->payments()->sum('amount_minor');

            if ($totalReceived > 0) {
                throw ValidationException::withMessages([
                    'status' => "Cannot cancel purchase order [{$order->public_id}] because goods receipts have already been processed ({$totalReceived} units received).",
                ]);
            }

            if ($totalPaid > 0) {
                throw ValidationException::withMessages([
                    'status' => "Cannot cancel purchase order [{$order->public_id}] because recorded payments exist. Please refund or reconcile payments prior to cancellation.",
                ]);
            }
        }

        return DB::transaction(function () use ($order, $targetEnum, $targetPaymentEnum, $currentStatusStr, $previousPaymentStatusStr, $actor) {
            if ($targetEnum !== null) {
                $order->status = $targetEnum;

                if ($targetEnum === VendorOrderStatus::ORDERED) {
                    $order->ordered_at = $order->ordered_at ?: now();
                } elseif ($targetEnum === VendorOrderStatus::CANCELLED) {
                    $order->cancelled_at = now();
                }
            }

            if ($targetPaymentEnum !== null) {
                $order->payment_status = $targetPaymentEnum;
            }

            $order->updated_by_user_id = $actor?->id;
            $order->save();

            DB::afterCommit(function () use ($order, $currentStatusStr, $previousPaymentStatusStr, $actor) {
                event(new AuditEvent('purchase_orders.status_updated', $actor, [
                    'vendor_order_id' => $order->id,
                    'public_id' => $order->public_id,
                    'previous_status' => $currentStatusStr,
                    'status' => $order->status->value,
                    'previous_payment_status' => $previousPaymentStatusStr,
                    'payment_status' => $order->payment_status ? $order->payment_status->value : 'unpaid',
                    'ordered_at' => $order->ordered_at?->toIso8601String(),
                    'received_at' => $order->received_at?->toIso8601String(),
                    'cancelled_at' => $order->cancelled_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            });

            return $order;
        });
    }
}
