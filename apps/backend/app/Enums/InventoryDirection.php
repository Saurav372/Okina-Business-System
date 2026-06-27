<?php

namespace App\Enums;

enum InventoryDirection: string
{
    case IN = 'in';
    case OUT = 'out';
    case RESERVE = 'reserve';
    case RELEASE = 'release';
    case ADJUST = 'adjust';
}
