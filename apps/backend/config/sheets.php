<?php

return [
    'enabled' => env('GOOGLE_SHEETS_ENABLED', false),
    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
    'credentials' => [
        'client_email' => env('GOOGLE_SHEETS_CLIENT_EMAIL'),
        'private_key' => env('GOOGLE_SHEETS_PRIVATE_KEY'),
        'project_id' => env('GOOGLE_SHEETS_PROJECT_ID'),
        'client_id' => env('GOOGLE_SHEETS_CLIENT_ID'),
    ],
    'tabs' => [
        // Tab names mapping will go here in later subtasks
    ],
];
