<?php

return [
    'title' => 'Accessibility (A11y / WCAG)',
    'description' => 'Mandatory standards to ensure the Okina Craft administration panel is accessible to all users, including keyboard-only, screen-reader, and color-blind users.',
    'items' => [
        [
            'requirement' => 'Color-Alone Rule (WCAG 1.4.1)',
            'details' => 'Never rely on color alone to communicate state. All alert banners, validation statuses, and badged lists must pair color changes with text labels or explicit icons.'
        ],
        [
            'requirement' => 'Always-Visible Focus Rings',
            'details' => 'Focus rings must never be removed for aesthetic preferences. Keep keyboard focus highlights enabled and styled consistently.'
        ],
        [
            'requirement' => 'Motion Accessibility',
            'details' => 'Listen to the prefers-reduced-motion media query. Always override animations, pulse shimmers, and scale transformations to instant transitions for reduced-motion profiles.'
        ],
        [
            'requirement' => 'Touch Targets Sizes',
            'details' => 'Interactive targets on mobile viewports must meet minimum size specs: 44px (WCAG minimum), 48px preferred for form controls, and 56px for floating action buttons.'
        ],
    ]
];
