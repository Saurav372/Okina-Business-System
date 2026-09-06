<?php

namespace App\Support\Navigation;

class NavigationGroup
{
    /**
     * @param  array<NavigationItem>  $items
     */
    public function __construct(
        public readonly string $group,
        public readonly int $order,
        public array $items = []
    ) {}
}
