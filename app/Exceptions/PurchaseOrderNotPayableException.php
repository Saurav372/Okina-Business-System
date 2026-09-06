<?php

namespace App\Exceptions;

class PurchaseOrderNotPayableException extends PurchaseOrderException
{
    // Thrown when trying to record a payment on a purchase order in a non-payable state (e.g. draft, cancelled)
}
