<?php

namespace App\Exceptions;

class PurchaseOrderImmutableException extends PurchaseOrderException
{
    // Thrown when an update is attempted on immutable fields of an ordered purchase order
}
