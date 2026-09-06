<?php

return [
    'title' => 'Mobile Optimisation',
    'description' => 'Guidelines for mobile-first responsiveness, touch interactions, and thumb-friendly checkout UI components.',
    'items' => [
        [
            'pattern' => 'Thumb Zone UI Mapping',
            'details' => 'Position key navigation selectors and CTA triggers within the bottom 60% of the screen. This area represents the natural range of motion for mobile thumbs.',
        ],
        [
            'pattern' => 'Touch Target Densities',
            'details' => 'Map target dimensions clearly: 44px minimum, 48px preferred, 56px floating. Keep touchable areas separated by standard margins to prevent accidental taps.',
        ],
        [
            'pattern' => 'Sticky CTA Footers',
            'details' => 'When viewing product listings or checkout flows, pin a sticky CTA panel to the bottom viewport. Safe-area insets must be set for screen notch offsets.',
        ],
        [
            'pattern' => 'Safe Area Insets integration',
            'details' => 'Always apply CSS env(safe-area-inset-bottom) and safe-area-inset-top on pinned wrappers to support modern bezel-less viewports.',
        ],
    ],
];
