<?php

return [
    'title' => 'Content & Copy Guidelines',
    'description' => 'Guidelines for tone, spelling case, error messages, and system alerts to maintain language consistency.',
    'items' => [
        [
            'pattern' => 'Heading Cases',
            'details' => 'Use title case (capitalizing primary words) for top navigation links and sidebars. Use sentence case (capitalizing first word only) for page headings, card titles, and field labels.'
        ],
        [
            'pattern' => 'Sentence Constraints',
            'details' => 'Keep system feedback copy and instructional texts concise. sentences should not exceed 20 words per line.'
        ],
        [
            'pattern' => 'Active Copy Actions',
            'details' => 'All CTA triggers must use active verb phrases (e.g. "Start Printing", "Get a Quote") rather than passive navigation nouns ("Proceed", "Continue").'
        ],
        [
            'pattern' => 'Actionable Error Texts',
            'details' => 'Errors must describe what went wrong and provide a corrective path. E.g. "Email already in use — Try signing in instead" instead of "Invalid credentials".'
        ],
        [
            'pattern' => 'Empty States Prompting',
            'details' => 'Every empty layout must explain why the screen has no data and offer a primary action button to populate it.'
        ],
    ]
];
