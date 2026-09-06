<?php

namespace App\Exceptions;

use Exception;

class StaleInventoryBalanceException extends Exception
{
    public function __construct(
        public readonly int $expectedOnHand,
        public readonly int $currentOnHand
    ) {
        parent::__construct(
            "The stock balance for this SKU was updated by another process (Expected: {$expectedOnHand}, Current: {$currentOnHand}). Please review the updated balance and try again."
        );
    }
}
