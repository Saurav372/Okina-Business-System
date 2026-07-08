<?php

return [
    'title' => 'Search User Experience (UX)',
    'description' => 'Rules for search autocomplete, query parsing, and zero-result page designs to guide high-intent search traffic.',
    'items' => [
        [
            'pattern' => 'Autocomplete & Suggestions',
            'details' => 'As the user types, show live matches from the catalog. Pre-populate empty search states with data-driven Popular Searches and Recent Searches.'
        ],
        [
            'pattern' => 'Zero-Result Recovery',
            'details' => 'Never show a dead-end page. If search yields no results, outline alternative query corrections, showcase popular catalog categories, and recommend top products.'
        ],
        [
            'pattern' => 'Fault Tolerance & Typos',
            'details' => 'Search parsing should handle minor typos (fuzzy matching) and support synonym mappings to find the correct product even with alternative vocabulary.'
        ],
        [
            'pattern' => 'Keyboard Interaction defaults',
            'details' => 'Let users navigate the suggestion dropdown lists using arrow keys, select items with Enter, and dismiss search overlays with the Escape key.'
        ],
    ]
];
