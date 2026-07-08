<?php

/**
 * Colors Token Configuration
 * 
 * @schema
 * [
 *     'name' => string,
 *     'variable' => string,
 *     'hex' => string,
 *     'aliases' => array<string>,
 *     'used_by' => array<string>,
 *     'contrast' => string,
 *     'shades' => array<int, array{shade: string, variable: string, hex: string}>
 * ]
 */
return [
    [
        'name' => 'Primary Brand',
        'variable' => 'var(--color-primary-600)',
        'hex' => '#4f46e5',
        'aliases' => ['brand', 'indigo', 'main', 'accent'],
        'used_by' => ['Buttons', 'Progress indicators', 'Active states', 'Links', 'Checkboxes'],
        'contrast' => 'AA Compliant (4.5:1+) on White/Light backgrounds',
        'shades' => [
            ['shade' => '50', 'variable' => 'var(--color-primary-50)', 'hex' => '#eeebff'],
            ['shade' => '100', 'variable' => 'var(--color-primary-100)', 'hex' => '#e0dbff'],
            ['shade' => '200', 'variable' => 'var(--color-primary-200)', 'hex' => '#c7beff'],
            ['shade' => '300', 'variable' => 'var(--color-primary-300)', 'hex' => '#ac9cff'],
            ['shade' => '400', 'variable' => 'var(--color-primary-400)', 'hex' => '#9076ff'],
            ['shade' => '500', 'variable' => 'var(--color-primary-500)', 'hex' => '#704cff'],
            ['shade' => '600', 'variable' => 'var(--color-primary-600)', 'hex' => '#4f46e5'],
            ['shade' => '700', 'variable' => 'var(--color-primary-700)', 'hex' => '#3b34b8'],
            ['shade' => '800', 'variable' => 'var(--color-primary-800)', 'hex' => '#2b268f'],
            ['shade' => '900', 'variable' => 'var(--color-primary-900)', 'hex' => '#1e1a66'],
            ['shade' => '950', 'variable' => 'var(--color-primary-950)', 'hex' => '#120f40'],
        ]
    ],
    [
        'name' => 'Secondary Brand',
        'variable' => 'var(--color-secondary-600)',
        'hex' => '#e11d48',
        'aliases' => ['logo-red', 'accent-secondary', 'rose'],
        'used_by' => ['Promo tags', 'Highlight elements', 'Interactive hover accents'],
        'contrast' => 'AA Compliant (4.5:1+) on White',
        'shades' => [
            ['shade' => '50', 'variable' => 'var(--color-secondary-50)', 'hex' => '#fff1f2'],
            ['shade' => '100', 'variable' => 'var(--color-secondary-100)', 'hex' => '#ffe4e6'],
            ['shade' => '200', 'variable' => 'var(--color-secondary-200)', 'hex' => '#fecdd3'],
            ['shade' => '300', 'variable' => 'var(--color-secondary-300)', 'hex' => '#fda4af'],
            ['shade' => '400', 'variable' => 'var(--color-secondary-400)', 'hex' => '#fb7185'],
            ['shade' => '500', 'variable' => 'var(--color-secondary-500)', 'hex' => '#f43f5e'],
            ['shade' => '600', 'variable' => 'var(--color-secondary-600)', 'hex' => '#e11d48'],
            ['shade' => '700', 'variable' => 'var(--color-secondary-700)', 'hex' => '#be123c'],
            ['shade' => '800', 'variable' => 'var(--color-secondary-800)', 'hex' => '#9f1239'],
            ['shade' => '900', 'variable' => 'var(--color-secondary-900)', 'hex' => '#881337'],
            ['shade' => '950', 'variable' => 'var(--color-secondary-950)', 'hex' => '#4c0519'],
        ]
    ],
    [
        'name' => 'Neutral Grays',
        'variable' => 'var(--color-neutral-600)',
        'hex' => '#4b5563',
        'aliases' => ['gray', 'slate', 'text', 'border', 'background'],
        'used_by' => ['Body text', 'Borders', 'Layout backgrounds', 'Card containers', 'Input borders'],
        'contrast' => 'AA/AAA Compliant dependent on shade selection (e.g. 700+ for text)',
        'shades' => [
            ['shade' => '50', 'variable' => 'var(--color-neutral-50)', 'hex' => '#f9fafb'],
            ['shade' => '100', 'variable' => 'var(--color-neutral-100)', 'hex' => '#f3f4f6'],
            ['shade' => '200', 'variable' => 'var(--color-neutral-200)', 'hex' => '#e5e7eb'],
            ['shade' => '300', 'variable' => 'var(--color-neutral-300)', 'hex' => '#d1d5db'],
            ['shade' => '400', 'variable' => 'var(--color-neutral-400)', 'hex' => '#9ca3af'],
            ['shade' => '500', 'variable' => 'var(--color-neutral-500)', 'hex' => '#6b7280'],
            ['shade' => '600', 'variable' => 'var(--color-neutral-600)', 'hex' => '#4b5563'],
            ['shade' => '700', 'variable' => 'var(--color-neutral-700)', 'hex' => '#374151'],
            ['shade' => '800', 'variable' => 'var(--color-neutral-800)', 'hex' => '#1f2937'],
            ['shade' => '900', 'variable' => 'var(--color-neutral-900)', 'hex' => '#111827'],
            ['shade' => '950', 'variable' => 'var(--color-neutral-950)', 'hex' => '#030712'],
        ]
    ],
    [
        'name' => 'Success Alert',
        'variable' => 'var(--color-success-600)',
        'hex' => '#059669',
        'aliases' => ['green', 'alert-success', 'emerald'],
        'used_by' => ['Success indicators', 'Completed states', 'Positive feedback banners', 'Valid validations'],
        'contrast' => 'AA Compliant (4.5:1+) on White',
        'shades' => [
            ['shade' => '50', 'variable' => 'var(--color-success-50)', 'hex' => '#ecfdf5'],
            ['shade' => '100', 'variable' => 'var(--color-success-100)', 'hex' => '#d1fae5'],
            ['shade' => '200', 'variable' => 'var(--color-success-200)', 'hex' => '#a7f3d0'],
            ['shade' => '300', 'variable' => 'var(--color-success-300)', 'hex' => '#6ee7b7'],
            ['shade' => '400', 'variable' => 'var(--color-success-400)', 'hex' => '#34d399'],
            ['shade' => '500', 'variable' => 'var(--color-success-500)', 'hex' => '#10b981'],
            ['shade' => '600', 'variable' => 'var(--color-success-600)', 'hex' => '#059669'],
            ['shade' => '700', 'variable' => 'var(--color-success-700)', 'hex' => '#047857'],
            ['shade' => '800', 'variable' => 'var(--color-success-800)', 'hex' => '#065f46'],
            ['shade' => '900', 'variable' => 'var(--color-success-900)', 'hex' => '#064e3b'],
            ['shade' => '950', 'variable' => 'var(--color-success-950)', 'hex' => '#022c22'],
        ]
    ],
    [
        'name' => 'Warning Alert',
        'variable' => 'var(--color-warning-600)',
        'hex' => '#d97706',
        'aliases' => ['amber', 'yellow', 'alert-warning'],
        'used_by' => ['Warning notices', 'Pending states', 'Neutral alerts'],
        'contrast' => 'AA Compliant (3.0:1+) for graphical boundaries',
        'shades' => [
            ['shade' => '50', 'variable' => 'var(--color-warning-50)', 'hex' => '#fffbeb'],
            ['shade' => '100', 'variable' => 'var(--color-warning-100)', 'hex' => '#fef3c7'],
            ['shade' => '200', 'variable' => 'var(--color-warning-200)', 'hex' => '#fde68a'],
            ['shade' => '300', 'variable' => 'var(--color-warning-300)', 'hex' => '#fcd34d'],
            ['shade' => '400', 'variable' => 'var(--color-warning-400)', 'hex' => '#fbbf24'],
            ['shade' => '500', 'variable' => 'var(--color-warning-500)', 'hex' => '#f59e0b'],
            ['shade' => '600', 'variable' => 'var(--color-warning-600)', 'hex' => '#d97706'],
            ['shade' => '700', 'variable' => 'var(--color-warning-700)', 'hex' => '#b45309'],
            ['shade' => '800', 'variable' => 'var(--color-warning-800)', 'hex' => '#92400e'],
            ['shade' => '900', 'variable' => 'var(--color-warning-900)', 'hex' => '#78350f'],
            ['shade' => '950', 'variable' => 'var(--color-warning-950)', 'hex' => '#451a03'],
        ]
    ],
    [
        'name' => 'Danger Alert',
        'variable' => 'var(--color-danger-600)',
        'hex' => '#dc2626',
        'aliases' => ['red', 'error', 'destructive', 'alert-danger'],
        'used_by' => ['Error states', 'Destructive actions', 'Severe alerts', 'Invalid states'],
        'contrast' => 'AA Compliant (4.5:1+) on White',
        'shades' => [
            ['shade' => '50', 'variable' => 'var(--color-danger-50)', 'hex' => '#fef2f2'],
            ['shade' => '100', 'variable' => 'var(--color-danger-100)', 'hex' => '#fee2e2'],
            ['shade' => '200', 'variable' => 'var(--color-danger-200)', 'hex' => '#fecaca'],
            ['shade' => '300', 'variable' => 'var(--color-danger-300)', 'hex' => '#fca5a5'],
            ['shade' => '400', 'variable' => 'var(--color-danger-400)', 'hex' => '#f87171'],
            ['shade' => '500', 'variable' => 'var(--color-danger-500)', 'hex' => '#ef4444'],
            ['shade' => '600', 'variable' => 'var(--color-danger-600)', 'hex' => '#dc2626'],
            ['shade' => '700', 'variable' => 'var(--color-danger-700)', 'hex' => '#b91c1c'],
            ['shade' => '800', 'variable' => 'var(--color-danger-800)', 'hex' => '#991b1b'],
            ['shade' => '900', 'variable' => 'var(--color-danger-900)', 'hex' => '#7f1d1d'],
            ['shade' => '950', 'variable' => 'var(--color-danger-950)', 'hex' => '#450a0a'],
        ]
    ],
];
