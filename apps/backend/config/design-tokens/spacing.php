<?php

/**
 * Spacing Token Configuration
 *
 * @schema
 * [
 *     'token' => string,
 *     'rem' => string,
 *     'px' => string,
 *     'used_by' => array<string>
 * ]
 */
return [
    [
        'token' => 'spacing-0',
        'rem' => '0rem',
        'px' => '0px',
        'used_by' => ['Reset styles', 'No-padding containers'],
    ],
    [
        'token' => 'spacing-1',
        'rem' => '0.25rem',
        'px' => '4px',
        'used_by' => ['Inline tag gap', 'Micro layout dividers', 'Nested bullet indentations'],
    ],
    [
        'token' => 'spacing-2',
        'rem' => '0.5rem',
        'px' => '8px',
        'used_by' => ['Small checkbox label gap', 'Input vertical padding', 'Badge items gap'],
    ],
    [
        'token' => 'spacing-3',
        'rem' => '0.75rem',
        'px' => '12px',
        'used_by' => ['Toast message paddings', 'Dropdown lists items spacing', 'Stepper icons gaps'],
    ],
    [
        'token' => 'spacing-4',
        'rem' => '1.0rem',
        'px' => '16px',
        'used_by' => ['Card inner paddings', 'Sidebar navigation links padding', 'Default input gutters'],
    ],
    [
        'token' => 'spacing-6',
        'rem' => '1.5rem',
        'px' => '24px',
        'used_by' => ['Main content side margins', 'Table column cells padding', 'Section gap headers'],
    ],
    [
        'token' => 'spacing-8',
        'rem' => '2.0rem',
        'px' => '32px',
        'used_by' => ['Card layout spacing margins', 'Dashboard grid gap elements', 'Footer spacings'],
    ],
    [
        'token' => 'spacing-12',
        'rem' => '3.0rem',
        'px' => '48px',
        'used_by' => ['Section blocks margins', 'Page vertical sections gap', 'Hero layouts padding'],
    ],
    [
        'token' => 'spacing-16',
        'rem' => '4.0rem',
        'px' => '64px',
        'used_by' => ['Admin sections bottom spacer', 'Welcome pages layouts', 'Login dialogs top offset'],
    ],
];
