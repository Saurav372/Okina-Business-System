<?php

namespace App\Support\Dashboard;

class ChartPointDTO
{
    public function __construct(
        public readonly string $label,
        public readonly float $value,
        public readonly ?string $formattedValue = null,
        public readonly ?string $fullLabel = null,
        public readonly int $orderCount = 0,
        public readonly bool $partial = false
    ) {}
}
