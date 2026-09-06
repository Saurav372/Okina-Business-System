<?php

namespace App\Support\Breadcrumbs;

readonly class BreadcrumbItem
{
    public function __construct(
        public string $label,
        public ?string $url,
        public bool $active,
    ) {}
}
