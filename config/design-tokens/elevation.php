<?php

/**
 * Elevation & Radius Token Configuration
 *
 * @schema
 * [
 *     'type' => 'shadow'|'radius',
 *     'token' => string,
 *     'value' => string,
 *     'used_by' => array<string>
 * ]
 */
return [
    [
        'type' => 'shadow',
        'token' => 'shadow-xs',
        'value' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
        'used_by' => ['Badges', 'Small action buttons', 'Mini tags'],
    ],
    [
        'type' => 'shadow',
        'token' => 'shadow-sm',
        'value' => '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)',
        'used_by' => ['Form Inputs', 'Secondary layout cards', 'Dropdown items lists'],
    ],
    [
        'type' => 'shadow',
        'token' => 'shadow-md',
        'value' => '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
        'used_by' => ['Standard cards', 'Action panels', 'Sidebar widgets'],
    ],
    [
        'type' => 'shadow',
        'token' => 'shadow-lg',
        'value' => '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
        'used_by' => ['Modal overlays dialogues', 'Drawers containers', 'Hover active panels shadow'],
    ],
    [
        'type' => 'shadow',
        'token' => 'shadow-xl',
        'value' => '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
        'used_by' => ['Global overlay models', 'Dropdown popups options panel'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-none',
        'value' => '0px',
        'used_by' => ['Table cells borders', 'Sharp alignment boxes'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-sm',
        'value' => '0.25rem (4px)',
        'used_by' => ['Checkbox borders', 'Badge status boxes'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-md',
        'value' => '0.375rem (6px)',
        'used_by' => ['Standard buttons', 'TextInput borders'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-lg',
        'value' => '0.5rem (8px)',
        'used_by' => ['Dropdown listings card', 'Standard files cards previews'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-xl',
        'value' => '0.75rem (12px)',
        'used_by' => ['Main cards dashboards', 'Accordion lists containers'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-2xl',
        'value' => '1.0rem (16px)',
        'used_by' => ['Standard Modal structures', 'Overlay side panels'],
    ],
    [
        'type' => 'radius',
        'token' => 'radius-full',
        'value' => '9999px',
        'used_by' => ['User avatar shapes', 'Badge rounded pill elements', 'Steppers progress rings'],
    ],
];
