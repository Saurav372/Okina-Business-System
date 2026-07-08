<?php

namespace App\Support\Navigation;

class NavigationGroup
{
    /**
     * @param string $group
     * @param int $order
     * @param array<NavigationItem> $items
     */
    public function __construct(
        public readonly string $group,
        public readonly int $order,
        public array $items = []
    ) {}
}
