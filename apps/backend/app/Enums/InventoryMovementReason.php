<?php

namespace App\Enums;

enum InventoryMovementReason: string
{
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case PURCHASE_RECEIPT = 'purchase_receipt';
    case MIGRATION = 'migration';
    case INVENTORY_CORRECTION = 'inventory_correction';
    case ORDER_FULFILLMENT = 'order_fulfillment';
    case INVENTORY_LOSS = 'inventory_loss';
    case DAMAGED_GOODS = 'damaged_goods';
    case EXPIRED_STOCK = 'expired_stock';
    case THEFT = 'theft';
    case WAREHOUSE_ADJUSTMENT = 'warehouse_adjustment';
}
