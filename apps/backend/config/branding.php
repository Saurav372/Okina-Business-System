<?php

/**
 * Brand Configuration for Okina Business System
 *
 * Central configuration for brand variables, colors, assets, seo meta tags, and contact information.
 */
return [
    'name' => 'Okina Craft',
    'short_name' => 'Okina',
    'tagline' => null,

    'company' => [
        'legal_name' => null,
        'founded' => null,
        'country' => 'India',
        'timezone' => 'Asia/Kolkata',
    ],

    'colors' => [
        'brand' => '#e83535',  // Logo flame/leaf — primary brand accent
        'ink' => '#1A1A1A',  // Logo brushstroke — dark brand neutral (pure black equivalent for graphics)
        'theme' => '#e83535',  // Browser theme-color meta tag color
    ],

    'logo' => [
        'primary' => '/brand/logo.svg',
        'icon' => '/brand/icon.svg',
        'light' => '/brand/logo-light.svg',
        'dark' => '/brand/logo-dark.svg',
    ],

    'seo' => [
        'title_suffix' => '| Okina Craft',
        'description' => 'Enterprise grade craft and customization business dashboard and logistics manager.',
        'keywords' => ['okina', 'craft', 'manufacturing', 'customization', 'order-tracking'],
        'canonical_base' => null,
        'robots' => 'index, follow',
    ],

    'social' => [
        'twitter' => null,
        'instagram' => null,
        'facebook' => null,
        'linkedin' => null,
    ],

    'contact' => [
        'email' => null,
        'phone' => null,
        'address' => null,
    ],
];
