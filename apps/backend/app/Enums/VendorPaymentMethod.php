<?php

namespace App\Enums;

enum VendorPaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case UPI = 'upi';
    case CASH = 'cash';
    case CHEQUE = 'cheque';
    case OTHER = 'other';
}
