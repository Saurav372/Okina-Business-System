<?php

namespace App\Exceptions;

use App\Models\ProductSku;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly ProductSku $sku,
        public readonly int $requested,
        public readonly int $available,
        string $message = 'Insufficient stock for the requested operation.'
    ) {
        parent::__construct($message);
    }
}
