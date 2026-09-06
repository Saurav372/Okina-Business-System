<?php

/**
 * Motion Token Configuration
 *
 * @schema
 * [
 *     'type' => 'duration'|'easing',
 *     'token' => string,
 *     'value' => string,
 *     'used_by' => array<string>
 * ]
 */
return [
    [
        'type' => 'duration',
        'token' => 'duration-75',
        'value' => '75ms',
        'used_by' => ['Micro-interactions', 'Tooltip fades', 'Active scale updates'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-100',
        'value' => '100ms',
        'used_by' => ['Badge colors updates', 'Checkbox triggers checked state'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-150',
        'value' => '150ms',
        'used_by' => ['Dropdowns options listings', 'Interactive hovering button highlights'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-200',
        'value' => '200ms',
        'used_by' => ['Accordion panels dynamic collapse expansions'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-300',
        'value' => '300ms',
        'used_by' => ['Page-level content entry fades', 'Overlay models sliding entries'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-500',
        'value' => '500ms',
        'used_by' => ['Progress bars loads updates', 'Scroll reveals viewport entrance animations'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-700',
        'value' => '700ms',
        'used_by' => ['Major landing animations', 'Hero intro fades'],
    ],
    [
        'type' => 'duration',
        'token' => 'duration-1000',
        'value' => '1000ms',
        'used_by' => ['Slow loading animations', 'Simulated loading overrides'],
    ],
    [
        'type' => 'easing',
        'token' => 'ease-linear',
        'value' => 'linear',
        'used_by' => ['Progress indicators loader increments', 'Background colors fade updates'],
    ],
    [
        'type' => 'easing',
        'token' => 'ease-in',
        'value' => 'cubic-bezier(0.4, 0, 1, 1)',
        'used_by' => ['Modal layout exit fades', 'Overlay panels closing actions'],
    ],
    [
        'type' => 'easing',
        'token' => 'ease-out',
        'value' => 'cubic-bezier(0, 0, 0.2, 1)',
        'used_by' => ['Modal layout entrance transitions', 'Dropdown list pops alerts animations'],
    ],
    [
        'type' => 'easing',
        'token' => 'ease-in-out',
        'value' => 'cubic-bezier(0.4, 0, 0.2, 1)',
        'used_by' => ['Collapse panel grids height transforms', 'General slide movements animations'],
    ],
    [
        'type' => 'easing',
        'token' => 'ease-bounce',
        'value' => 'cubic-bezier(0.34, 1.56, 0.64, 1)',
        'used_by' => ['Interactive hover scale bounces', 'Toasts notification entrance hops'],
    ],
];
