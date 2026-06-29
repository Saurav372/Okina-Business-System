<?php

namespace App\Enums;

enum VendorPaymentStatus: string
{
    case PENDING = 'pending'; // Reserved for future workflows (e.g. pending bank transfers)
    case PAID = 'paid';
    case CANCELLED = 'cancelled'; // Reserved for future voids/reversals
    case VOIDED = 'voided'; // Reserved for future voids/reversals
}
