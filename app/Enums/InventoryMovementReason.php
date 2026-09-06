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
    case ORDER_CANCELLATION = 'order_cancellation';
    case STOCK_TRANSFER_OUT = 'stock_transfer_out';
    case STOCK_TRANSFER_IN = 'stock_transfer_in';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL_ADJUSTMENT => 'Manual Adjustment',
            self::PURCHASE_RECEIPT => 'Purchase Receipt',
            self::MIGRATION => 'Data Migration',
            self::INVENTORY_CORRECTION => 'Stock Audit Correction',
            self::ORDER_FULFILLMENT => 'Order Fulfillment',
            self::INVENTORY_LOSS => 'Inventory Loss',
            self::DAMAGED_GOODS => 'Damaged Goods',
            self::EXPIRED_STOCK => 'Expired Stock',
            self::THEFT => 'Stolen / Missing',
            self::WAREHOUSE_ADJUSTMENT => 'Warehouse Relocation Adjustment',
            self::ORDER_CANCELLATION => 'Order Cancellation Restock',
            self::STOCK_TRANSFER_OUT => 'Warehouse Transfer Out',
            self::STOCK_TRANSFER_IN => 'Warehouse Transfer In',
        };
    }
}
