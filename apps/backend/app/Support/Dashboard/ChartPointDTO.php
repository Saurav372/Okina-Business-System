<?php

namespace App\Support\Dashboard;

class ChartPointDTO
{
    /**
     * @param string $label
     * @param float $value
     * @param string|null $formattedValue
     */
    public function __construct(
        public readonly string $label,
        public readonly float $value,
        public readonly ?string $formattedValue = null
    ) {}
}
