<?php

namespace App\Enums;

enum VendorPaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case UPI = 'upi';
    case CASH = 'cash';
    case CHEQUE = 'cheque';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'Bank Transfer',
            self::UPI => 'UPI',
            self::CASH => 'Cash',
            self::CHEQUE => 'Cheque',
            self::OTHER => 'Other',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'bg-blue-50 text-blue-700 border-blue-200',
            self::UPI => 'bg-purple-50 text-purple-700 border-purple-200',
            self::CASH => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::CHEQUE => 'bg-amber-50 text-amber-700 border-amber-200',
            self::OTHER => 'bg-neutral-100 text-neutral-700 border-neutral-200',
        };
    }
}
