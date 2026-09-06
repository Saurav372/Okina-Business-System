<?php

namespace App\Presenters;

use App\Support\Dashboard\ChartLayoutDTO;
use App\Support\Dashboard\ChartSeriesDTO;

class ChartGeometryPresenter
{
    /**
     * Map a ChartSeriesDTO to a ChartLayoutDTO inside the bounding box.
     */
    public static function present(
        ChartSeriesDTO $series,
        float $width = 500,
        float $height = 200,
        float $paddingX = 40,
        float $paddingY = 20
    ): ChartLayoutDTO {
        $points = $series->points;
        $n = $points->count();

        // 1. Resolve raw min and max values in the series
        $rawMin = $points->min('value') ?? 0.0;
        $rawMax = $points->max('value') ?? 0.0;

        // Force zero baseline visibility rule
        if ($rawMin >= 0) {
            $rawMin = 0.0;
        }
        if ($rawMax <= 0) {
            $rawMax = 0.0;
        }

        // Avoid division-by-zero on identical datasets
        if ($rawMax == $rawMin) {
            $rawMax += 100.0;
            $rawMin -= 100.0;
        }

        // 2. Execute standard nice tick algorithm
        $tickResult = self::calculateNiceTicks($rawMin, $rawMax);
        $graphMin = $tickResult['min'];
        $graphMax = $tickResult['max'];
        $ticks = collect($tickResult['ticks']);

        // 3. Map X and Y coordinates inside viewBox
        $coordinates = collect();
        $valRange = max(0.0001, $graphMax - $graphMin);

        foreach ($points as $index => $point) {
            $x = $paddingX + ($index / max(1, $n - 1)) * ($width - 2 * $paddingX);
            $y = $height - $paddingY - (($point->value - $graphMin) / $valRange) * ($height - 2 * $paddingY);

            $coordinates->push([
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $point->label,
                'value' => $point->value,
                'formatted' => $point->formattedValue ?? $series->unit.number_format($point->value, 2),
            ]);
        }

        // 4. Resolve baseline Y coordinate (where Y value = 0)
        $baselineY = $height - $paddingY - ((0 - $graphMin) / $valRange) * ($height - 2 * $paddingY);

        return new ChartLayoutDTO(
            coordinates: $coordinates,
            ticks: $ticks->map(fn ($t) => [
                'value' => $t,
                'y' => round($height - $paddingY - (($t - $graphMin) / $valRange) * ($height - 2 * $paddingY), 2),
                'label' => $series->unit.self::formatTickLabel($t),
            ]),
            baselineY: round($baselineY, 2),
            maxY: $graphMax,
            minY: $graphMin
        );
    }

    /**
     * Compute clean tick steps using Nice Numbers.
     */
    protected static function calculateNiceTicks(float $min, float $max, int $maxTicks = 5): array
    {
        $range = self::niceNum($max - $min, false);
        $d = self::niceNum($range / max(1, $maxTicks - 1), true);

        $graphMin = floor($min / $d) * $d;
        $graphMax = ceil($max / $d) * $d;

        $ticks = [];
        for ($x = $graphMin; $x <= $graphMax + 0.5 * $d; $x += $d) {
            $ticks[] = $x;
        }

        return [
            'ticks' => $ticks,
            'min' => $graphMin,
            'max' => $graphMax,
        ];
    }

    protected static function niceNum(float $range, bool $round): float
    {
        if ($range <= 0) {
            return 10.0;
        }

        $exponent = floor(log10($range));
        $fraction = $range / pow(10, $exponent);

        if ($round) {
            if ($fraction < 1.5) {
                $niceFraction = 1;
            } elseif ($fraction < 3) {
                $niceFraction = 2;
            } elseif ($fraction < 7) {
                $niceFraction = 5;
            } else {
                $niceFraction = 10;
            }
        } else {
            if ($fraction <= 1) {
                $niceFraction = 1;
            } elseif ($fraction <= 2) {
                $niceFraction = 2;
            } elseif ($fraction <= 5) {
                $niceFraction = 5;
            } else {
                $niceFraction = 10;
            }
        }

        return $niceFraction * pow(10, $exponent);
    }

    protected static function formatTickLabel(float $value): string
    {
        $abs = abs($value);
        if ($abs >= 1000000) {
            return round($value / 1000000, 1).'M';
        }
        if ($abs >= 1000) {
            return round($value / 1000, 1).'K';
        }

        return (string) $value;
    }
}
