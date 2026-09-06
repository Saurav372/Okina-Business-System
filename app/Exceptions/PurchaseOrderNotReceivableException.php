<?php

namespace App\Exceptions;

class PurchaseOrderNotReceivableException extends PurchaseOrderException
{
    // Thrown when stock is received against a purchase order in an invalid status
}
