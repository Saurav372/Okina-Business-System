<?php

namespace App\Support\Dashboard;

class ChartPointDTO
{
    public function __construct(
        public readonly string $label,
        public readonly float $value,
        public readonly ?string $formattedValue = null
    ) {}
}
