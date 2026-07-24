<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case STOCK_IN = 'stock_in';
    case STOCK_OUT = 'stock_out';
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case ORDER_RESERVATION = 'order_reservation';
    case ORDER_DEDUCTION = 'order_deduction';
    case CANCELLATION_RELEASE = 'cancellation_release';
    case CANCELLATION_REVERSAL = 'cancellation_reversal';
    case PURCHASE_RECEIPT = 'purchase_receipt';
    case RETURN_RESTOCK = 'return_restock';
    case CORRECTION = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::STOCK_IN => 'Stock Inbound',
            self::STOCK_OUT => 'Stock Outbound',
            self::MANUAL_ADJUSTMENT => 'Manual Stock Adjustment',
            self::ORDER_RESERVATION => 'Order Stock Reservation',
            self::ORDER_DEDUCTION => 'Order Stock Deduction',
            self::CANCELLATION_RELEASE => 'Order Cancellation Release',
            self::CANCELLATION_REVERSAL => 'Order Cancellation Restock',
            self::PURCHASE_RECEIPT => 'Purchase Order Receipt',
            self::RETURN_RESTOCK => 'Customer Return Restock',
            self::CORRECTION => 'Audit Balance Correction',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::STOCK_IN, self::PURCHASE_RECEIPT, self::CANCELLATION_REVERSAL, self::RETURN_RESTOCK => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::STOCK_OUT, self::ORDER_DEDUCTION => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
            self::ORDER_RESERVATION, self::CANCELLATION_RELEASE => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::MANUAL_ADJUSTMENT, self::CORRECTION => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        };
    }
}
