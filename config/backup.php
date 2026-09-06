<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */

    'keep_copies' => env('SYSTEM_BACKUP_KEEP_COPIES', 5),

    'backup_disk' => env('SYSTEM_BACKUP_DISK', 'private'),

    'backup_directory' => env('SYSTEM_BACKUP_DIRECTORY', 'backups'),

    'private_storage_path' => env('SYSTEM_BACKUP_PRIVATE_STORAGE_PATH', storage_path('app/private')),
];
