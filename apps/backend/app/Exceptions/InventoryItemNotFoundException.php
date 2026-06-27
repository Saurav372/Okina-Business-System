<?php

namespace App\Exceptions;

use App\Models\ProductSku;
use Exception;

class InventoryItemNotFoundException extends Exception
{
    public function __construct(
        public readonly ProductSku $sku,
        string $message = 'Inventory item record not found for SKU.'
    ) {
        parent::__construct($message);
    }
}
