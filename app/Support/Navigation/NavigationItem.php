<?php

namespace App\Support\Navigation;

class NavigationItem
{
    /**
     * @param  array<string>  $active
     * @param  array{value: int|string, variant: string}|null  $badge
     * @param  array<NavigationItem>  $children
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
