<?php

return [
    'colors' => is_file(__DIR__.'/design-tokens/colors.php') ? include __DIR__.'/design-tokens/colors.php' : [],
    'typography' => is_file(__DIR__.'/design-tokens/typography.php') ? include __DIR__.'/design-tokens/typography.php' : [],
    'spacing' => is_file(__DIR__.'/design-tokens/spacing.php') ? include __DIR__.'/design-tokens/spacing.php' : [],
    'elevation' => is_file(__DIR__.'/design-tokens/elevation.php') ? include __DIR__.'/design-tokens/elevation.php' : [],
    'motion' => is_file(__DIR__.'/design-tokens/motion.php') ? include __DIR__.'/design-tokens/motion.php' : [],
];
