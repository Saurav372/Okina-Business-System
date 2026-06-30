<?php

namespace App\Enums;

enum LeadFollowUpStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case SNOOZED = 'snoozed';
    case CANCELLED = 'cancelled';
}
