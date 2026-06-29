<?php

namespace App\Exceptions;

class PurchaseOrderPaymentLimitExceededException extends PurchaseOrderException
{
    // Thrown when a payment amount exceeds the remaining payable balance of the purchase order
}
