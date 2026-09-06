<?php

namespace App\Support\Finance;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class FinanceReportFilters
{
    public function __construct(
        public readonly ?CarbonImmutable $startDate,
        public readonly ?CarbonImmutable $endDate,
        public readonly string $groupBy = 'month',
        public readonly string $preset = 'this_month',
        public readonly string $timezone = 'Asia/Kolkata'
    ) {}

    public static function fromRequest(Request $request): self
    {
        return self::fromArray($request->all());
    }

    public static function fromArray(array $attributes): self
    {
        $tz = config('app.timezone', 'Asia/Kolkata');
        $preset = (string) ($attributes['preset'] ?? '');
        $groupBy = 'month'; // Explicit single supported default grouping

        $rawStart = ! empty($attributes['start_date']) ? (string) $attributes['start_date'] : null;
        $rawEnd = ! empty($attributes['end_date']) ? (string) $attributes['end_date'] : null;

        // If preset is explicitly set and not 'custom', preset resolution wins over supplied dates
        if ($preset !== '' && $preset !== 'custom') {
            [$start, $end] = self::resolvePresetDates($preset, $tz);

            return new self($start, $end, $groupBy, $preset, $tz);
        }

        // If preset is 'custom' or empty but both start_date & end_date are supplied
        if ($rawStart !== null && $rawEnd !== null) {
            $start = CarbonImmutable::parse($rawStart, $tz)->startOfDay();
            $end = CarbonImmutable::parse($rawEnd, $tz)->endOfDay();

            return new self($start, $end, $groupBy, 'custom', $tz);
        }

        // Default: 'this_month'
        [$start, $end] = self::resolvePresetDates('this_month', $tz);

        return new self($start, $end, $groupBy, 'this_month', $tz);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function resolvePresetDates(string $preset, string $tz): array
    {
        $now = CarbonImmutable::now($tz);

        return match ($preset) {
            'last_month' => [
                $now->subMonth()->startOfMonth()->startOfDay(),
                $now->subMonth()->endOfMonth()->endOfDay(),
            ],
            'this_quarter' => [
                $now->startOfQuarter()->startOfDay(),
                $now->endOfQuarter()->endOfDay(),
            ],
            'current_fiscal_year' => self::resolveFiscalYearDates($now),
            default => [
                $now->startOfMonth()->startOfDay(),
                $now->endOfMonth()->endOfDay(),
            ],
        };
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function resolveFiscalYearDates(CarbonImmutable $now): array
    {
        $startMonth = (int) config('finance.fiscal_year_start_month', 4);
        $currentMonth = (int) $now->format('n');

        if ($currentMonth >= $startMonth) {
            $startYear = (int) $now->format('Y');
            $endYear = $startYear + 1;
        } else {
            $endYear = (int) $now->format('Y');
            $startYear = $endYear - 1;
        }

        $start = CarbonImmutable::create($startYear, $startMonth, 1, 0, 0, 0, $now->getTimezone());
        $end = $start->addYear()->subDay()->endOfDay();

        return [$start, $end];
    }

    public function toQueryArray(): array
    {
        return [
            'preset' => $this->preset,
            'start_date' => $this->startDate?->toDateString(),
            'end_date' => $this->endDate?->toDateString(),
            'group_by' => $this->groupBy,
        ];
    }
}
