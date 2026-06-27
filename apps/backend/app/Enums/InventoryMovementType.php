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
}
