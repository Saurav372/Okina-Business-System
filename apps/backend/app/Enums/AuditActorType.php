<?php

namespace App\Enums;

enum AuditActorType: string
{
    case USER = 'user';
    case CUSTOMER = 'customer';
    case SYSTEM = 'system';
    case JOB = 'job';
    case PROVIDER = 'provider';
}
