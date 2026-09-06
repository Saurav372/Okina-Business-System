<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\Expenses\ExpenseFilters;
use App\Support\Expenses\ExpenseQueryBuilder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseReportingService
{
    /**
     * Generate financial summary analytics metrics.
     */
    public function generateSummary(array|ExpenseFilters $filters = []): array
    {
        $filtersDTO = $filters instanceof ExpenseFilters ? $filters : new ExpenseFilters($filters);

        // Base query with search, category, and date range filters applied
        $baseQuery = ExpenseQueryBuilder::applyFilters(ExpenseQueryBuilder::baseQuery(), $filtersDTO);

        // All status queries
        $totalQuery = clone $baseQuery;
        $totalMinor = (int) $totalQuery->sum('amount_minor');
        $totalCount = (int) $totalQuery->count();

        $approvedQuery = (clone $baseQuery)->where('status', Expense::STATUS_APPROVED);
        $totalApprovedExpensesMinor = (int) $approvedQuery->sum('amount_minor');
        $approvedCount = (int) $approvedQuery->count();

        $pendingQuery = (clone $baseQuery)->where('status', Expense::STATUS_PENDING_APPROVAL);
        $totalPendingExpensesMinor = (int) $pendingQuery->sum('amount_minor');
        $pendingCount = (int) $pendingQuery->count();

        $rejectedQuery = (clone $baseQuery)->where('status', Expense::STATUS_REJECTED);
        $totalRejectedExpensesMinor = (int) $rejectedQuery->sum('amount_minor');
        $rejectedCount = (int) $rejectedQuery->count();

        $globalActiveCategoryCount = ExpenseCategory::query()->where('is_active', true)->count();

        // Category breakdown: aggregate expenses by category for the filtered dataset
        $categoryBreakdown = $this->buildCategoryBreakdown($filtersDTO);

        // Monthly breakdown: only populated when group_by=month is requested
        $monthly = [];
        if ($filtersDTO->groupBy === 'month') {
            $monthly = $this->buildMonthlyBreakdown($filtersDTO);
        }

        // Build category_breakdown for simple KPI consumers
        $simpleCategoryBreakdown = array_map(function ($c) {
            return [
                'category_id' => $c['category']['id'] ?? null,
                'public_id' => $c['category']['public_id'],
                'code' => $c['category']['code'],
                'name' => $c['category']['name'],
                'is_active' => $c['category']['is_active'],
                'approved_total_minor' => (int) ($c['totals']['approved_minor'] ?? 0),
            ];
        }, $categoryBreakdown);

        return [
            'currency' => 'INR',
            'summary' => [
                'total_amount' => number_format($totalMinor / 100, 2, '.', ''),
                'approved_amount' => number_format($totalApprovedExpensesMinor / 100, 2, '.', ''),
                'pending_amount' => number_format($totalPendingExpensesMinor / 100, 2, '.', ''),
                'rejected_amount' => number_format($totalRejectedExpensesMinor / 100, 2, '.', ''),
                'total_expenses' => $totalCount,
            ],
            'categories' => $categoryBreakdown,
            'monthly' => $monthly,
            // Additional KPI keys
            'total_approved_expenses_minor' => $totalApprovedExpensesMinor,
            'approved_count' => $approvedCount,
            'total_pending_expenses_minor' => $totalPendingExpensesMinor,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'total_expenses_count' => $totalCount,
            'global_active_categories_count' => $globalActiveCategoryCount,
            'category_breakdown' => $simpleCategoryBreakdown,
        ];
    }

    /**
     * Build category breakdown with nested structure: {category, totals, count}
     */
    protected function buildCategoryBreakdown(ExpenseFilters $filters): array
    {
        // Get all expense categories
        $categories = ExpenseCategory::query()->orderBy('id')->get();

        if ($categories->isEmpty()) {
            return [];
        }

        // Build a base query for the filtered dataset, grouped by category and status
        $baseQuery = Expense::query();

        if ($filters->dateFrom) {
            $baseQuery->whereDate('occurred_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $baseQuery->whereDate('occurred_at', '<=', $filters->dateTo);
        }
        if (! empty($filters->status) && $filters->status !== 'all') {
            $baseQuery->where('status', $filters->status);
        }

        // Aggregate by category_id and status
        $aggregates = (clone $baseQuery)
            ->select(
                'expense_category_id',
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount_minor) as total_minor')
            )
            ->groupBy('expense_category_id', 'status')
            ->get();

        // Index aggregates by category_id and status
        $byCategory = [];
        foreach ($aggregates as $row) {
            $catId = $row->expense_category_id;
            $status = $row->status;
            if (! isset($byCategory[$catId])) {
                $byCategory[$catId] = [];
            }
            $byCategory[$catId][$status] = [
                'count' => (int) $row->count,
                'total_minor' => (int) $row->total_minor,
            ];
        }

        $result = [];
        foreach ($categories as $cat) {
            $catData = $byCategory[$cat->id] ?? [];
            $totalCount = array_sum(array_column($catData, 'count'));

            // Skip categories with no expenses in the filtered period
            if ($totalCount === 0) {
                continue;
            }

            $approvedMinor = (int) ($catData[Expense::STATUS_APPROVED]['total_minor'] ?? 0);
            $pendingMinor = (int) ($catData[Expense::STATUS_PENDING_APPROVAL]['total_minor'] ?? 0);
            $rejectedMinor = (int) ($catData[Expense::STATUS_REJECTED]['total_minor'] ?? 0);
            $draftMinor = (int) ($catData[Expense::STATUS_DRAFT]['total_minor'] ?? 0);
            $totalMinor = $approvedMinor + $pendingMinor + $rejectedMinor + $draftMinor;

            $result[] = [
                'category' => [
                    'public_id' => $cat->public_id,
                    'code' => $cat->code,
                    'name' => $cat->name,
                    'is_active' => (bool) $cat->is_active,
                ],
                'totals' => [
                    'total_amount' => number_format($totalMinor / 100, 2, '.', ''),
                    'approved' => number_format($approvedMinor / 100, 2, '.', ''),
                    'pending' => number_format($pendingMinor / 100, 2, '.', ''),
                    'rejected' => number_format($rejectedMinor / 100, 2, '.', ''),
                    'approved_minor' => $approvedMinor,
                ],
                'count' => $totalCount,
            ];
        }

        return $result;
    }

    /**
     * Build monthly breakdown: [{month, totals, count}, ...]
     */
    protected function buildMonthlyBreakdown(ExpenseFilters $filters): array
    {
        $query = Expense::query();

        if ($filters->dateFrom) {
            $query->whereDate('occurred_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo) {
            $query->whereDate('occurred_at', '<=', $filters->dateTo);
        }
        if (! empty($filters->status) && $filters->status !== 'all') {
            $query->where('status', $filters->status);
        }

        // Use strftime for SQLite compatibility
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $monthExpr = DB::raw("strftime('%Y-%m', occurred_at) as month");
        } else {
            $monthExpr = DB::raw("DATE_FORMAT(occurred_at, '%Y-%m') as month");
        }

        $aggregates = $query
            ->select(
                $monthExpr,
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount_minor) as total_minor')
            )
            ->groupBy('month', 'status')
            ->orderBy('month')
            ->get();

        // Index by month and status
        $byMonth = [];
        foreach ($aggregates as $row) {
            $month = $row->month;
            $status = $row->status;
            if (! isset($byMonth[$month])) {
                $byMonth[$month] = [];
            }
            $byMonth[$month][$status] = [
                'count' => (int) $row->count,
                'total_minor' => (int) $row->total_minor,
            ];
        }

        $result = [];
        foreach ($byMonth as $month => $statusData) {
            $totalCount = array_sum(array_column($statusData, 'count'));
            $approvedMinor = (int) ($statusData[Expense::STATUS_APPROVED]['total_minor'] ?? 0);
            $pendingMinor = (int) ($statusData[Expense::STATUS_PENDING_APPROVAL]['total_minor'] ?? 0);
            $rejectedMinor = (int) ($statusData[Expense::STATUS_REJECTED]['total_minor'] ?? 0);
            $draftMinor = (int) ($statusData[Expense::STATUS_DRAFT]['total_minor'] ?? 0);
            $totalMinor = $approvedMinor + $pendingMinor + $rejectedMinor + $draftMinor;

            $result[] = [
                'month' => $month,
                'totals' => [
                    'total_amount' => number_format($totalMinor / 100, 2, '.', ''),
                    'approved' => number_format($approvedMinor / 100, 2, '.', ''),
                    'pending' => number_format($pendingMinor / 100, 2, '.', ''),
                    'rejected' => number_format($rejectedMinor / 100, 2, '.', ''),
                ],
                'count' => $totalCount,
            ];
        }

        return $result;
    }

    /**
     * Stream CSV export with formula injection protection.
     */
    public function streamCsvExport(ExpenseFilters $filters): StreamedResponse
    {
        $filename = 'expenses-'.Carbon::now()->format('Y-m-d').'.csv';
        $query = ExpenseQueryBuilder::applyFilters(ExpenseQueryBuilder::baseQuery(), $filters);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM
            fwrite($output, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($output, [
                'Expense ID',
                'Occurred Date',
                'Category Code',
                'Category Name',
                'Status',
                'Amount (INR)',
                'Reference',
                'Notes',
                'Recorded By',
                'Submitted At',
                'Approved At',
            ]);

            $query->chunk(100, function ($expenses) use ($output) {
                /** @var Expense $expense */
                foreach ($expenses as $expense) {
                    $amountFormatted = number_format($expense->amount_minor / 100, 2, '.', '');

                    fputcsv($output, [
                        $this->sanitizeCsvField($expense->public_id),
                        $expense->occurred_at?->format('Y-m-d') ?: '',
                        $this->sanitizeCsvField($expense->expenseCategory?->code ?: ''),
                        $this->sanitizeCsvField($expense->expenseCategory?->name ?: ''),
                        $this->sanitizeCsvField(ucwords(str_replace('_', ' ', $expense->status))),
                        $amountFormatted,
                        $this->sanitizeCsvField($expense->reference ?: ''),
                        $this->sanitizeCsvField($expense->notes ?: ''),
                        $this->sanitizeCsvField($expense->recordedBy?->name ?: ''),
                        $expense->submitted_at?->format('Y-m-d H:i:s') ?: '',
                        $expense->approved_at?->format('Y-m-d H:i:s') ?: '',
                    ]);
                }
            });

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Formula injection protection: prefix =, +, -, @, \t, \r with single quote '.
     */
    protected function sanitizeCsvField(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $firstChar = substr($value, 0, 1);
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
