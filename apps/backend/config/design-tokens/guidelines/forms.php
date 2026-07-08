<?php

return [
    'title' => 'Form User Experience (UX)',
    'description' => 'Rules for text inputs, select lists, and control layouts to make forms feel responsive, fast, and easy to complete.',
    'items' => [
        [
            'state' => 'Required Fields',
            'details' => 'Indicate required inputs with a clean visual marker (e.g. asterisk) and highlight missing fields with a primary border color var(--color-primary).'
        ],
        [
            'state' => 'Focused Inputs',
            'details' => 'Apply active focus rings using var(--focus-ring-color) and outline offsets to clearly map keyboard positioning without breaking input text flow.'
        ],
        [
            'state' => 'Valid State feedback',
            'details' => 'Use success colors var(--color-success) on borders or validation ticks only after client validation is successful. Avoid premature feedback while typing.'
        ],
        [
            'state' => 'Invalid State warnings',
            'details' => 'Apply danger red var(--color-danger) on border highlights and pair with validation text describing what to correct.'
        ],
        [
            'state' => 'Disabled State states',
            'details' => 'Apply opacity scale var(--disabled-opacity) and set cursor to var(--cursor-disabled) on interactive inputs.'
        ],
    ]
];
