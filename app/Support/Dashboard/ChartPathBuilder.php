<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Collection;

class ChartPathBuilder
{
    /**
     * Build an SVG line path command string from coordinates.
     */
    public static function toLinePath(Collection $coords): string
    {
        if ($coords->isEmpty()) {
            return '';
        }

        $path = '';
        foreach ($coords as $index => $point) {
            $x = $point['x'];
            $y = $point['y'];
            $path .= ($index === 0 ? 'M' : ' L')." {$x},{$y}";
        }

        return $path;
    }

    /**
     * Build an SVG area fill path command string from coordinates down to the zero baseline.
     */
    public static function toAreaPath(Collection $coords, float $baselineY): string
    {
        if ($coords->isEmpty()) {
            return '';
        }

        $linePath = self::toLinePath($coords);

        $first = $coords->first();
        $last = $coords->last();

        $firstX = $first['x'];
        $lastX = $last['x'];

        return "{$linePath} L {$lastX},{$baselineY} L {$firstX},{$baselineY} Z";
    }
}
