<?php

return [
    'title' => 'Design Token Deprecation Strategy',
    'description' => 'A clean lifecycle flow for retiring old tokens and migrating components to modern semantic standards without breaking layout structures.',
    'items' => [
        [
            'process' => 'Deprecated State definitions',
            'details' => 'A deprecated token remains functional but triggers warnings in compiler code. Developers should avoid using these in new features.',
        ],
        [
            'process' => 'Sunset / Removal process',
            'details' => 'Retire old CSS properties and variables during major version releases. Document exact migration paths to modern semantic alternatives.',
        ],
        [
            'process' => 'Mismatched overrides review',
            'details' => 'Audit hardcoded fallbacks and legacy color references regularly. Point components directly to Tier 2 semantic variables.',
        ],
    ],
];
