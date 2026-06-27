<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

class ExpenseReportingService
{
    /**
     * Generateaggregated expense summary.
     */
    public function generateSummary(array $filters): array
    {
        $categoryId = null;
        if (! empty($filters['expense_category_public_id'])) {
            $categoryId = ExpenseCategory::withTrashed()
                ->where('public_id', $filters['expense_category_public_id'])
                ->value('id');
        }

        $query = Expense::query();

        if (! empty($filters['start_date'])) {
            $query->where('occurred_at', '>=', $filters['start_date']);
        }
        if (! empty($filters['end_date'])) {
            $query->where('occurred_at', '<=', $filters['end_date']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if ($categoryId) {
            $query->where('expense_category_id', $categoryId);
        }

        // 1. Calculate overall summary statistics
        $stats = (clone $query)
            ->selectRaw('
                COALESCE(SUM(amount_minor), 0) as total_minor,
                COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as approved_minor,
                COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as pending_minor,
                COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as rejected_minor,
                COUNT(*) as total_count
            ', [Expense::STATUS_APPROVED, Expense::STATUS_PENDING_APPROVAL, Expense::STATUS_REJECTED])
            ->first();

        $summary = [
            'total_amount' => $this->formatAmount($stats->total_minor ?? 0),
            'approved_amount' => $this->formatAmount($stats->approved_minor ?? 0),
            'pending_amount' => $this->formatAmount($stats->pending_minor ?? 0),
            'rejected_amount' => $this->formatAmount($stats->rejected_minor ?? 0),
            'total_expenses' => (int) ($stats->total_count ?? 0),
        ];

        $groupBy = $filters['group_by'] ?? 'category';
        $response = [
            'currency' => 'INR',
            'summary' => $summary,
        ];

        // 2. Calculate grouped statistics
        if ($groupBy === 'month') {
            $driver = DB::connection()->getDriverName();
            $dateSelect = $driver === 'sqlite'
                ? "strftime('%Y-%m', occurred_at) as year_month"
                : "DATE_FORMAT(occurred_at, '%Y-%m') as year_month";

            $monthlyStats = (clone $query)
                ->selectRaw("
                    {$dateSelect},
                    COALESCE(SUM(amount_minor), 0) as total_minor,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as approved_minor,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as pending_minor,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as rejected_minor,
                    COUNT(*) as total_count
                ", [Expense::STATUS_APPROVED, Expense::STATUS_PENDING_APPROVAL, Expense::STATUS_REJECTED])
                ->groupBy('year_month')
                ->orderBy('year_month', 'asc')
                ->get();

            $monthlyData = [];
            foreach ($monthlyStats as $row) {
                if ($row->year_month === null) {
                    continue;
                }
                $monthlyData[] = [
                    'month' => $row->year_month,
                    'totals' => [
                        'total_amount' => $this->formatAmount($row->total_minor),
                        'approved' => $this->formatAmount($row->approved_minor),
                        'pending' => $this->formatAmount($row->pending_minor),
                        'rejected' => $this->formatAmount($row->rejected_minor),
                    ],
                    'count' => (int) $row->total_count,
                ];
            }
            $response['monthly'] = $monthlyData;
        } else {
            // Default: Group by Category
            $categoryStats = (clone $query)
                ->selectRaw('
                    expense_category_id,
                    COALESCE(SUM(amount_minor), 0) as total_minor,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as approved_minor,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as pending_minor,
                    COALESCE(SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END), 0) as rejected_minor,
                    COUNT(*) as total_count
                ', [Expense::STATUS_APPROVED, Expense::STATUS_PENDING_APPROVAL, Expense::STATUS_REJECTED])
                ->groupBy('expense_category_id')
                ->orderBy('expense_category_id', 'asc')
                ->get();

            $categoryIds = $categoryStats->pluck('expense_category_id')->filter()->unique()->all();
            $categories = ExpenseCategory::withTrashed()->whereIn('id', $categoryIds)->get()->keyBy('id');

            $categoriesData = [];
            foreach ($categoryStats as $row) {
                $category = $categories->get($row->expense_category_id);
                $categoriesData[] = [
                    'category' => [
                        'public_id' => $category?->public_id ?? 'UNKNOWN',
                        'name' => $category?->name ?? 'Unknown Category',
                    ],
                    'totals' => [
                        'total_amount' => $this->formatAmount($row->total_minor),
                        'approved' => $this->formatAmount($row->approved_minor),
                        'pending' => $this->formatAmount($row->pending_minor),
                        'rejected' => $this->formatAmount($row->rejected_minor),
                    ],
                    'count' => (int) $row->total_count,
                ];
            }
            $response['categories'] = $categoriesData;
        }

        return $response;
    }

    /**
     * Format minor unit amounts to decimal string standard.
     */
    protected function formatAmount(int $amountMinor): string
    {
        return number_format($amountMinor / 100, 2, '.', '');
    }
}
