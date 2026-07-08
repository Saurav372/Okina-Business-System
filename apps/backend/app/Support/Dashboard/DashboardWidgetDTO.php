<?php

namespace App\Support\Dashboard;

class DashboardWidgetDTO
{
    /**
     * @param string $label
     * @param string $value
     * @param string|null $trend
     * @param string $trendDirection
     * @param string|null $description
     * @param string|null $icon
     * @param string|null $href
     * @param string $variant
     * @param string|null $accessibilityLabel
     */
    public function __construct(
        public readonly string $label,
        public readonly string $value,
        public readonly ?string $trend = null,
        public readonly string $trendDirection = 'neutral',
        public readonly ?string $description = null,
        public readonly ?string $icon = null,
        public readonly ?string $href = null,
        public readonly string $variant = 'neutral',
        public readonly ?string $accessibilityLabel = null
    ) {}
}
