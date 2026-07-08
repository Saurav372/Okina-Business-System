<?php

return [
    'title' => 'Core Design Principles',
    'description' => 'Fundamental visual and interactive pillars that anchor the Okina Craft design system. Every layout and styling choice is evaluated against these principles.',
    'items' => [
        [
            'principle' => 'Clarity over decoration',
            'details' => 'Every visual element must serve an information hierarchy role. Avoid borders when layout spacing suffices. Avoid decorative icons that lack interactive meaning.'
        ],
        [
            'principle' => 'One primary action per context',
            'details' => 'Every viewport or panel should have exactly one clear primary path. Multiple call-to-actions divide user attention and reduce conversion rates.'
        ],
        [
            'principle' => 'Whitespace before borders',
            'details' => 'Rely on generous structural margins to establish group containment. Borders divide layouts and add visual noise; whitespace organizes page regions seamlessly.'
        ],
        [
            'principle' => 'Motion communicates state',
            'details' => 'Use transitional animations (transitions, fades, collapses) to express context changes or data loading. Never apply animation purely as a decorative background effect.'
        ],
        [
            'principle' => 'Accessibility first',
            'details' => 'Never compromise standard keyboard navigation, focus indicators, or color contrast values. Accessible design is high-performance design.'
        ],
        [
            'principle' => 'Consistency before creativity',
            'details' => 'Adhere strictly to design tokens. Inventing custom shadows, custom colors, or bespoke padding sizes dilutes brand trust and slows engineering.'
        ],
        [
            'principle' => 'Content before chrome',
            'details' => 'A visually stunning UI is invisible. Users visit to read, purchase, or track data. Keep buttons, cards, and navbars clean so user content takes center stage.'
        ],
        [
            'principle' => 'Progressive disclosure',
            'details' => 'Show only key info required for the current action. Expose detailed advanced fields or secondary tables in drawer transitions, scroll lists, or popups.'
        ],
    ]
];
