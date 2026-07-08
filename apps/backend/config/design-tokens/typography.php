<?php

/**
 * Typography Token Configuration
 * 
 * @schema
 * [
 *     'name' => string,
 *     'variable' => string,
 *     'weight' => string,
 *     'letter_spacing' => string,
 *     'line_height' => string,
 *     'clamp' => string,
 *     'aliases' => array<string>,
 *     'used_by' => array<string>,
 *     'accessibility' => string
 * ]
 */
return [
    [
        'name' => 'Heading 1',
        'variable' => '--text-h1',
        'weight' => '700 (Bold)',
        'letter_spacing' => '-0.025em (tightest)',
        'line_height' => '1.2',
        'clamp' => 'clamp(2.25rem, 4vw + 1rem, 3.5rem)',
        'aliases' => ['h1', 'page-title', 'hero'],
        'used_by' => ['Page main titles', 'Hero highlights'],
        'accessibility' => 'Clear distinction above other content. Must be single H1 per page.'
    ],
    [
        'name' => 'Heading 2',
        'variable' => '--text-h2',
        'weight' => '600 (Semibold)',
        'letter_spacing' => '-0.02em (tight)',
        'line_height' => '1.25',
        'clamp' => 'clamp(1.75rem, 3vw + 0.75rem, 2.5rem)',
        'aliases' => ['h2', 'section-title', 'subtitle'],
        'used_by' => ['Section subheadings', 'Dashboard widgets major headers'],
        'accessibility' => 'Logical semantic header level. Must follow h1 hierarchy.'
    ],
    [
        'name' => 'Heading 3',
        'variable' => '--text-h3',
        'weight' => '600 (Semibold)',
        'letter_spacing' => '-0.015em',
        'line_height' => '1.3',
        'clamp' => 'clamp(1.5rem, 2vw + 0.5rem, 2.0rem)',
        'aliases' => ['h3', 'panel-title'],
        'used_by' => ['Card titles', 'Accordion headings', 'Modal panel headers'],
        'accessibility' => 'Clean reading structure for panels and overlays.'
    ],
    [
        'name' => 'Heading 4',
        'variable' => '--text-h4',
        'weight' => '600 (Semibold)',
        'letter_spacing' => '-0.01em',
        'line_height' => '1.35',
        'clamp' => 'clamp(1.25rem, 1.5vw + 0.5rem, 1.6rem)',
        'aliases' => ['h4', 'widget-title'],
        'used_by' => ['Secondary widget headers', 'Mini data charts headings'],
        'accessibility' => 'Provides visual structure without competing with major layout titles.'
    ],
    [
        'name' => 'Body Large',
        'variable' => '--text-body-lg',
        'weight' => '400 (Regular)',
        'letter_spacing' => '0',
        'line_height' => '1.5',
        'clamp' => 'clamp(1.05rem, 0.5vw + 0.95rem, 1.15rem)',
        'aliases' => ['body-lg', 'intro'],
        'used_by' => ['Intro paragraphs', 'Explanatory header text blocks'],
        'accessibility' => 'Ideal font readability for descriptive copy text blocks.'
    ],
    [
        'name' => 'Body Regular',
        'variable' => '--text-body',
        'weight' => '400 (Regular)',
        'letter_spacing' => '0',
        'line_height' => '1.5',
        'clamp' => 'clamp(0.875rem, 0.2vw + 0.85rem, 1.0rem)',
        'aliases' => ['body', 'standard', 'normal'],
        'used_by' => ['Default descriptions', 'Table cells', 'Forms inputs text'],
        'accessibility' => 'Minimum recommended reading size (14px–16px) for basic reading content.'
    ],
    [
        'name' => 'Body Small',
        'variable' => '--text-body-sm',
        'weight' => '400 (Regular)',
        'letter_spacing' => '0.01em',
        'line_height' => '1.4',
        'clamp' => 'clamp(0.75rem, 0.1vw + 0.725rem, 0.875rem)',
        'aliases' => ['body-sm', 'caption-large', 'secondary-text'],
        'used_by' => ['Secondary field notes', 'Status indicators tags', 'Helper texts'],
        'accessibility' => 'Sufficiently readable for auxiliary details.'
    ],
    [
        'name' => 'Caption',
        'variable' => '--text-caption',
        'weight' => '500 (Medium)',
        'letter_spacing' => '0.02em',
        'line_height' => '1.3',
        'clamp' => 'clamp(0.65rem, 0.05vw + 0.625rem, 0.75rem)',
        'aliases' => ['caption', 'mini', 'micro', 'label'],
        'used_by' => ['Small tag titles', 'Metadata footnotes', 'Table header labels'],
        'accessibility' => 'Ensure text contrast meets AA guidelines due to smaller font container sizing.'
    ],
];
