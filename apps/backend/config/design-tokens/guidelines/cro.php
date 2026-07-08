<?php

return [
    'title' => 'Conversion Rate Optimisation (CRO)',
    'description' => 'Visual rules designed to direct attention, reduce friction, and maximize business task completion across all conversion surfaces.',
    'items' => [
        [
            'rule' => 'One CTA Isolation Rule',
            'details' => 'Maximum of ONE dominant filled conversion CTA (--color-cta) per viewport screen. Secondary options must use outlines, ghost styles, or inline links.'
        ],
        [
            'rule' => 'Benefit-Oriented Actions',
            'details' => 'Avoid generic action labels. Use verb + benefit to outline what the user gets when clicking. E.g. "Get My Quote" instead of "Submit", "Start Printing" instead of "Continue", "Request a Sample" instead of "Send".'
        ],
        [
            'rule' => 'Visual Weight Scale',
            'details' => 'Enforce relative weight: CTA (100% presence) -> Primary Button (80%) -> Secondary Button (60%) -> Ghost Button (40%) -> Inline Link (20%).'
        ],
        [
            'rule' => 'Intent Hierarchy',
            'details' => 'Enforce layout flows: Checkout / Request Quote -> --color-cta; Save / Confirm -> --color-primary; Draft / Edit -> Ghost variant; Cancel / Reset -> --color-secondary; Delete / Remove -> --color-danger.'
        ],
        [
            'rule' => 'Trust Tokens Injection',
            'details' => 'Include trust assets at core decision checkouts (e.g. Star ratings, GST Invoice flags, Secure Payment badges, Authorized Distributor labels, Client logs).'
        ],
        [
            'rule' => 'Fear Reduction Callouts',
            'details' => 'Near purchase triggers, always place 3-5 trust markers (e.g. "GST Invoice Available", "Secure 256-Bit SSL", "1-Year Warranty", "Dedicated Phone Support") to minimize purchase anxiety.'
        ],
    ]
];
