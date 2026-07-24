<?php

/**
 * Semantic Design Token Mappings
 *
 * Maps interface roles/intents to design token properties.
 */
return [
    // Action intent
    ['group' => 'Action Intent', 'token' => '--color-primary', 'maps_to' => '--color-brand-500', 'used_by' => ['Primary standard buttons', 'Confirm modals', 'Form submissions']],
    ['group' => 'Action Intent', 'token' => '--color-cta', 'maps_to' => '--color-brand-500', 'used_by' => ['Buy Now button', 'Checkout CTA', 'High-intent acquisition triggers']],
    ['group' => 'Action Intent', 'token' => '--color-secondary', 'maps_to' => '--color-ink-700', 'used_by' => ['Cancel actions', 'Neutral buttons', 'Auxiliary options']],

    // Surface hierarchy
    ['group' => 'Surface Hierarchy', 'token' => '--color-surface-page', 'maps_to' => '--color-ink-50', 'used_by' => ['Page outermost background']],
    ['group' => 'Surface Hierarchy', 'token' => '--color-surface-card', 'maps_to' => '--color-surface (#ffffff)', 'used_by' => ['Content card background']],
    ['group' => 'Surface Hierarchy', 'token' => '--color-surface-elevated', 'maps_to' => '--color-surface (#ffffff)', 'used_by' => ['Headers', 'Tabs', 'Sub-navigation bars']],
    ['group' => 'Surface Hierarchy', 'token' => '--color-surface-overlay', 'maps_to' => 'rgba(10, 10, 10, 0.50)', 'used_by' => ['Backdrop scrims behind modals']],
    ['group' => 'Surface Hierarchy', 'token' => '--color-surface-modal', 'maps_to' => '--color-surface (#ffffff)', 'used_by' => ['Modal layout dialogues']],
    ['group' => 'Surface Hierarchy', 'token' => '--color-surface-sidebar', 'maps_to' => '--color-ink-900', 'used_by' => ['Admin primary sidebar background']],

    // Text hierarchy
    ['group' => 'Text Hierarchy', 'token' => '--color-text-heading', 'maps_to' => '--color-ink-900', 'used_by' => ['H1, H2, H3, H4 section titles']],
    ['group' => 'Text Hierarchy', 'token' => '--color-text-body', 'maps_to' => '--color-ink-800', 'used_by' => ['Standard paragraphs', 'Default copy']],
    ['group' => 'Text Hierarchy', 'token' => '--color-text-caption', 'maps_to' => '--color-ink-600', 'used_by' => ['Small metadata label descriptions']],
    ['group' => 'Text Hierarchy', 'token' => '--color-text-placeholder', 'maps_to' => '--color-ink-400', 'used_by' => ['Forms text inputs placeholders']],
    ['group' => 'Text Hierarchy', 'token' => '--color-text-disabled', 'maps_to' => '--color-ink-300', 'used_by' => ['Grayed out disabled control text']],
    ['group' => 'Text Hierarchy', 'token' => '--color-text-inverse', 'maps_to' => '#ffffff', 'used_by' => ['Inverse text contrasting dark panels (e.g. sidebar)']],

    // Links
    ['group' => 'Link Mappings', 'token' => '--color-link', 'maps_to' => '--color-brand-600', 'used_by' => ['Anchor tags standard links']],
    ['group' => 'Link Mappings', 'token' => '--color-link-hover', 'maps_to' => '--color-brand-700', 'used_by' => ['Hovered anchor tags']],
    ['group' => 'Link Mappings', 'token' => '--color-link-visited', 'maps_to' => '--color-brand-800', 'used_by' => ['Previously visited paths']],
    ['group' => 'Link Mappings', 'token' => '--color-link-active', 'maps_to' => '--color-brand-500', 'used_by' => ['Active state clicks']],

    // Border & Focus
    ['group' => 'Border & Focus', 'token' => '--color-border', 'maps_to' => '--color-ink-200', 'used_by' => ['Containers split lines', 'Input outer boundaries']],
    ['group' => 'Border & Focus', 'token' => '--color-border-hover', 'maps_to' => '--color-ink-300', 'used_by' => ['Hovered elements outline border']],
    ['group' => 'Border & Focus', 'token' => '--focus-ring-color', 'maps_to' => '--color-primary', 'used_by' => ['Focus ring border active glow']],

    // Empty state
    ['group' => 'Empty State', 'token' => '--color-empty-icon', 'maps_to' => '--color-ink-300', 'used_by' => ['No-data indicators standard vector icons']],
    ['group' => 'Empty State', 'token' => '--color-empty-illustration', 'maps_to' => '--color-ink-200', 'used_by' => ['Illustrative placeholders paths colors']],

    // Interaction
    ['group' => 'Interaction & Opacity', 'token' => '--hover-opacity', 'maps_to' => '0.85', 'used_by' => ['Hover opacity filters', 'Image buttons hover highlights']],
    ['group' => 'Interaction & Opacity', 'token' => '--disabled-opacity', 'maps_to' => '0.45', 'used_by' => ['Buttons overlays disabled active masks']],

    // Layout
    ['group' => 'Layout Shell', 'token' => '--sidebar-width', 'maps_to' => '16rem (256px)', 'used_by' => ['Primary sidebar expand limits']],
    ['group' => 'Layout Shell', 'token' => '--header-height', 'maps_to' => '4rem (64px)', 'used_by' => ['Main content sticky navigation bar header']],
    ['group' => 'Layout Shell', 'token' => '--reading-width', 'maps_to' => '65ch', 'used_by' => ['Optimal lines length limitations on text components']],

    // Density
    ['group' => 'Density Base', 'token' => '--control-height', 'maps_to' => '2.5rem (40px)', 'used_by' => ['Standard inputs, buttons vertical container height']],
    ['group' => 'Density Base', 'token' => '--table-row-height', 'maps_to' => '3rem (48px)', 'used_by' => ['Standard tables row lines heights']],

    // Typography
    ['group' => 'Semantic Typography', 'token' => '--font-display', 'maps_to' => '--font-sans (Geist)', 'used_by' => ['Title displays', 'Marketing headers']],
    ['group' => 'Semantic Typography', 'token' => '--font-heading', 'maps_to' => '--font-sans (Geist)', 'used_by' => ['Section sub-titles', 'Standard widget headers']],
    ['group' => 'Semantic Typography', 'token' => '--font-body', 'maps_to' => '--font-sans (Geist)', 'used_by' => ['Basic descriptions', 'Standard form fields content text']],
];
