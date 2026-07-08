<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;

class ChartSeriesDTO
{
    /**
     * @param string $title
     * @param Collection<ChartPointDTO> $points
     * @param string $color
     * @param string $unit
     * @param float|null $currentValue
     * @param float|null $previousValue
     * @param float|null $changePercent
     * @param string $changeDirection
     */
    public function __construct(
        public readonly string $title,
        public readonly Collection $points,
        public readonly string $color = 'chart-1',
        public readonly string $unit = '₹',
        public readonly ?float $currentValue = null,
        public readonly ?float $previousValue = null,
        public readonly ?float $changePercent = null,
        public readonly string $changeDirection = 'neutral'
    ) {}
}
