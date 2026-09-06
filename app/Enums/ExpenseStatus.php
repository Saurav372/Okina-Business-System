<?php

namespace App\Enums;

enum ExpenseStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::APPROVED => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PENDING_APPROVAL => 'bg-amber-50 text-amber-700 border-amber-200',
            self::REJECTED => 'bg-red-50 text-red-700 border-red-200',
            self::DRAFT => 'bg-neutral-100 text-neutral-600 border-neutral-200',
        };
    }
}
