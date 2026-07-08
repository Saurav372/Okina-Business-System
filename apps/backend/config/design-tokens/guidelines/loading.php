<?php

return [
    'title' => 'Loading & Progress States',
    'description' => 'Rules for choosing between loading spinners and content skeleton loaders to reduce perceived latency.',
    'items' => [
        [
            'pattern' => 'Skeleton Loaders Preference',
            'details' => 'Prefer shape-matched content skeletons over spinners for major page loads. Skeletons indicate the structure of upcoming data and feel faster to the user.'
        ],
        [
            'pattern' => 'Spinners & Indeterminate Indicators',
            'details' => 'Use loading spinners only for short operations (<500ms) or when the final content shape cannot be predicted (e.g. background saving states).'
        ],
        [
            'pattern' => 'Progress Indicators Mappings',
            'details' => 'Map progress steps clearly: Current Step (var(--color-primary)), Inactive Steps (var(--color-ink-200)), Completed Steps (var(--color-success)), and Errors (var(--color-danger)).'
        ],
    ]
];
