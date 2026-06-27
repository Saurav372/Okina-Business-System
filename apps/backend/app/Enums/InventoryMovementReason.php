<?php

namespace App\Enums;

enum InventoryMovementReason: string
{
    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case PURCHASE_RECEIPT = 'purchase_receipt';
    case MIGRATION = 'migration';
    case INVENTORY_CORRECTION = 'inventory_correction';
}
