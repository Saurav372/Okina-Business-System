<?php

namespace App\Support\Navigation;

class NavigationItem
{
    /**
     * @param string $label
     * @param string $route
     * @param string $icon
     * @param int $order
     * @param array<string> $active
     * @param array{value: int|string, variant: string}|null $badge
     * @param string|null $permission
     * @param array<NavigationItem> $children
     */
    public function __construct(
        public readonly string $label,
        public readonly string $route,
        public readonly string $icon,
        public readonly int $order = 10,
        public readonly array $active = [],
        public readonly ?array $badge = null,
        public readonly ?string $permission = null,
        public readonly array $children = []
    ) {}
}
