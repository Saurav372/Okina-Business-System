<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;

class ChartLayoutDTO
{
    public function __construct(
        public readonly Collection $coordinates,
        public readonly Collection $ticks,
        public readonly float $baselineY,
        public readonly float $maxY,
        public readonly float $minY
    ) {}
}
