<?php

namespace App\Enums;

enum WarehouseTransferStatus: string
{
    case DRAFT = 'draft';
    case IN_TRANSIT = 'in_transit';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::IN_TRANSIT => 'In Transit',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-800 text-slate-400 border-slate-700',
            self::IN_TRANSIT => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::COMPLETED => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::CANCELLED => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        };
    }
}
