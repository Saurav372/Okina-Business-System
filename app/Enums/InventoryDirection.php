<?php

namespace App\Enums;

enum InventoryDirection: string
{
    case IN = 'in';
    case OUT = 'out';
    case RESERVE = 'reserve';
    case RELEASE = 'release';
    case ADJUST = 'adjust';

    public function label(): string
    {
        return match ($this) {
            self::IN => 'Inbound (+)',
            self::OUT => 'Outbound (-)',
            self::RESERVE => 'Reserved (+)',
            self::RELEASE => 'Released (-)',
            self::ADJUST => 'Adjustment (±)',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::IN => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            self::OUT => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
            self::RESERVE => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            self::RELEASE => 'bg-slate-800 text-slate-300 border-slate-700',
            self::ADJUST => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        };
    }
}
