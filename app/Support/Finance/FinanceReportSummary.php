<?php

namespace App\Support\Finance;

class FinanceReportSummary
{
    /**
     * @param  array<string, string>  $metrics  Raw minor unit numeric strings
     * @param  array<int, array<string, mixed>>  $monthlyTrend
     * @param  array<int, array<string, mixed>>  $categoryBreakdown
     */
    public function __construct(
        public readonly FinanceReportFilters $filters,
        public readonly array $metrics,
        public readonly array $monthlyTrend,
        public readonly array $categoryBreakdown,
        public readonly string $currency = 'INR'
    ) {}

    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'filters' => [
                'preset' => $this->filters->preset,
                'start_date' => $this->filters->startDate?->toDateString(),
                'end_date' => $this->filters->endDate?->toDateString(),
                'group_by' => $this->filters->groupBy,
                'timezone' => $this->filters->timezone,
            ],
            'metrics' => $this->metrics,
            'monthly_trend' => $this->monthlyTrend,
            'expense_categories' => $this->categoryBreakdown,
        ];
    }
}
