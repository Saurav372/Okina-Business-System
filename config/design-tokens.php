<?php

return [
    'colors' => is_file(__DIR__.'/design-tokens/colors.php') ? include __DIR__.'/design-tokens/colors.php' : [],
    'typography' => is_file(__DIR__.'/design-tokens/typography.php') ? include __DIR__.'/design-tokens/typography.php' : [],
    'spacing' => is_file(__DIR__.'/design-tokens/spacing.php') ? include __DIR__.'/design-tokens/spacing.php' : [],
    'elevation' => is_file(__DIR__.'/design-tokens/elevation.php') ? include __DIR__.'/design-tokens/elevation.php' : [],
    'motion' => is_file(__DIR__.'/design-tokens/motion.php') ? include __DIR__.'/design-tokens/motion.php' : [],
    'semantic' => is_file(__DIR__.'/design-tokens/semantic.php') ? include __DIR__.'/design-tokens/semantic.php' : [],
    'charts' => is_file(__DIR__.'/design-tokens/charts.php') ? include __DIR__.'/design-tokens/charts.php' : [],
    'guidelines' => [
        'index' => is_file(__DIR__.'/design-tokens/guidelines/index.php') ? include __DIR__.'/design-tokens/guidelines/index.php' : [],
        'principles' => is_file(__DIR__.'/design-tokens/guidelines/principles.php') ? include __DIR__.'/design-tokens/guidelines/principles.php' : [],
        'cro' => is_file(__DIR__.'/design-tokens/guidelines/cro.php') ? include __DIR__.'/design-tokens/guidelines/cro.php' : [],
        'forms' => is_file(__DIR__.'/design-tokens/guidelines/forms.php') ? include __DIR__.'/design-tokens/guidelines/forms.php' : [],
        'accessibility' => is_file(__DIR__.'/design-tokens/guidelines/accessibility.php') ? include __DIR__.'/design-tokens/guidelines/accessibility.php' : [],
        'mobile' => is_file(__DIR__.'/design-tokens/guidelines/mobile.php') ? include __DIR__.'/design-tokens/guidelines/mobile.php' : [],
        'search' => is_file(__DIR__.'/design-tokens/guidelines/search.php') ? include __DIR__.'/design-tokens/guidelines/search.php' : [],
        'loading' => is_file(__DIR__.'/design-tokens/guidelines/loading.php') ? include __DIR__.'/design-tokens/guidelines/loading.php' : [],
        'content' => is_file(__DIR__.'/design-tokens/guidelines/content.php') ? include __DIR__.'/design-tokens/guidelines/content.php' : [],
        'motion' => is_file(__DIR__.'/design-tokens/guidelines/motion.php') ? include __DIR__.'/design-tokens/guidelines/motion.php' : [],
        'icons' => is_file(__DIR__.'/design-tokens/guidelines/icons.php') ? include __DIR__.'/design-tokens/guidelines/icons.php' : [],
        'deprecation' => is_file(__DIR__.'/design-tokens/guidelines/deprecation.php') ? include __DIR__.'/design-tokens/guidelines/deprecation.php' : [],
    ],
];
