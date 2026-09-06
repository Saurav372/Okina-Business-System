<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention Period
    |--------------------------------------------------------------------------
    |
    | Define the number of days to retain audit log records. Logs older than
    | this threshold will be pruned.
    |
    */
    'retention_days' => env('AUDIT_RETENTION_DAYS', 365),
];
