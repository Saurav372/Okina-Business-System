<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;

class ChartLayoutDTO
{
    /**
     * @param Collection $coordinates
     * @param Collection $ticks
     * @param float $baselineY
     * @param float $maxY
     * @param float $minY
     */
    public function __construct(
        public readonly Collection $coordinates,
        public readonly Collection $ticks,
        public readonly float $baselineY,
        public readonly float $maxY,
        public readonly float $minY
    ) {}
}
