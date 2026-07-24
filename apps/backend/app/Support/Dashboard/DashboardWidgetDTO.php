<?php

namespace App\Support\Dashboard;

class DashboardWidgetDTO
{
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
