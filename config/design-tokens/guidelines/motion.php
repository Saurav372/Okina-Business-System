<?php

return [
    'title' => 'Motion Guidelines',
    'description' => 'Animation must explain state changes, guide user attention, and respect preference configurations.',
    'items' => [
        [
            'rule' => 'Motion explains state change',
            'details' => 'Never animate layout changes unexpectedly. Animations should transition dynamically between explicit UI states.',
        ],
        [
            'rule' => 'Keep feedback fast',
            'details' => 'UI interactions (button clicks, hover glimmers) must transition in under 300ms to preserve responsiveness.',
        ],
        [
            'rule' => 'Respect accessibility',
            'details' => 'Implement prefers-reduced-motion triggers to disable screen movements instantly for users with vestibular preferences.',
        ],
        [
            'rule' => 'Use standard easing',
            'details' => 'Standard bezier functions keep dynamic curves feeling physical, premium, and unified.',
        ],
    ],
];
