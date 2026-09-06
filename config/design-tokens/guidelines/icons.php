<?php

return [
    'title' => 'Iconography Guidelines',
    'description' => 'Guidelines for consistent rendering, stroke sizing, and decorative icons placement.',
    'items' => [
        [
            'rule' => 'Consistent stroke',
            'details' => 'Use 2px stroke width on all standard utility icons to maintain a clean outline weight.',
        ],
        [
            'rule' => 'Alignment',
            'details' => 'Align icons to baseline text heights using flex or inline-flex containers so symbols don\'t drift.',
        ],
        [
            'rule' => 'Informative vs Decorative',
            'details' => 'Add aria-hidden="true" to decorative icons. Use explicit labels for interactive icons.',
        ],
        [
            'rule' => 'Icon + Text spacing',
            'details' => 'Maintain consistent spacing (usually gap-2 or gap-2.5) when presenting icons alongside text labels.',
        ],
    ],
];
