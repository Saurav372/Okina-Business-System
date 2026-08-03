<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Support\Finance\FinanceReportFilters;
use App\Support\Finance\FinanceReportPresenter;
use App\Support\Finance\FinanceReportSummary;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportService
{
    /**
     * Generate comprehensive Finance Report summary DTO.
     */
    public function generateSummary(FinanceReportFilters|array $filters): FinanceReportSummary
    {
        if (is_array($filters)) {
            $filters = FinanceReportFilters::fromArray($filters);
        }
        $startDate = $filters->startDate;
        $endDate = $filters->endDate;

        // 1. Base Query Builders
        $ordersQuery = $this->baseOrderQuery($startDate, $endDate);
        $paymentsQuery = $this->basePaymentQuery($startDate, $endDate);
        $refundsQuery = $this->baseRefundQuery($startDate, $endDate);
        $expensesQuery = $this->baseExpenseQuery($startDate, $endDate);

        // 2. Summary Metrics (Raw minor units)
        $totalSales = (int) (clone $ordersQuery)->sum('orders.total_amount_minor');
        $totalOrdersCount = (int) (clone $ordersQuery)->count();

        $totalPayments = (int) (clone $paymentsQuery)->sum('payments.amount_minor');
        $totalPaymentsCount = (int) (clone $paymentsQuery)->count();

        $totalRefunds = (int) (clone $refundsQuery)->sum('refunds.amount_minor');
        $totalRefundsCount = (int) (clone $refundsQuery)->count();

        $totalExpenses = (int) (clone $expensesQuery)->sum('expenses.amount_minor');
        $totalExpensesCount = (int) (clone $expensesQuery)->count();

        // 3. Per-Order As-Of Outstanding Receivables Accounting
        // Evaluated on eligible orders placed on or before end_date
        $totalOutstanding = $this->calculatePerOrderOutstandingReceivables($endDate);

        // Net Metrics
        $netCashFlow = $totalPayments - $totalRefunds - $totalExpenses;
        $netOperatingIncome = $totalSales - $totalRefunds - $totalExpenses;

        $metrics = [
            'total_sales_minor' => (string) $totalSales,
            'total_payments_minor' => (string) $totalPayments,
            'total_refunds_minor' => (string) $totalRefunds,
            'total_expenses_minor' => (string) $totalExpenses,
            'total_outstanding_minor' => (string) $totalOutstanding,
            'net_cash_flow_minor' => (string) $netCashFlow,
            'net_operating_income_minor' => (string) $netOperatingIncome,
            'total_orders_count' => (int) $totalOrdersCount,
            'total_payments_count' => (int) $totalPaymentsCount,
            'total_refunds_count' => (int) $totalRefundsCount,
            'total_expenses_count' => (int) $totalExpensesCount,
        ];

        // 4. Monthly Trend (Zero-filled across date range)
        $monthlyTrend = $this->getZeroFilledMonthlyTrend($startDate, $endDate);

        // 5. Expense Category Breakdown (With basis points & historical soft-deleted categories preserved)
        $categoryBreakdown = $this->getExpenseCategoryBreakdown($startDate, $endDate, $totalExpenses);

        return new FinanceReportSummary(
            filters: $filters,
            metrics: $metrics,
            monthlyTrend: $monthlyTrend,
            categoryBreakdown: $categoryBreakdown,
            currency: 'INR'
        );
    }

    /**
     * Stream CSV export download directly from FinanceReportSummary DTO.
     */
    public function streamCsvExport(FinanceReportFilters $filters, ?User $actor = null): StreamedResponse
    {
        $actor = $actor ?: Auth::user();
        $summary = $this->generateSummary($filters);
        $presented = FinanceReportPresenter::present($summary);

        $nowIso = now()->toIso8601String();
        $filename = 'finance-report-'.$summary->filters->endDate?->format('Y-m-d').'.csv';

        // Dispatch audit event before response streaming
        event(new AuditEvent('finance_reports.exported', $actor, [
            'actor_id' => $actor?->id,
            'start_date' => $summary->filters->startDate?->toDateString(),
            'end_date' => $summary->filters->endDate?->toDateString(),
            'preset' => $summary->filters->preset,
            'group_by' => $summary->filters->groupBy,
            'currency' => $summary->currency,
            'filename' => $filename,
            'generated_at' => $nowIso,
        ]));

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($presented, $summary) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fwrite($file, "\xEF\xBB\xBF");

            $sanitize = function (?string $value): string {
                if ($value === null) {
                    return '';
                }
                $str = (string) $value;
                if (preg_match('/^[=\+\-@\t\r]/', $str)) {
                    return "'".$str;
                }

                return $str;
            };

            // Section 1: Metadata
            fputcsv($file, [$sanitize('Section'), $sanitize('Key/Code'), $sanitize('Name/Period'), $sanitize('Amount Minor'), $sanitize('Formatted Amount'), $sanitize('Currency'), $sanitize('Count'), $sanitize('Share BPS')]);
            fputcsv($file, [$sanitize('Metadata'), $sanitize('Start Date'), $sanitize($summary->filters->startDate?->toDateString()), '', '', $sanitize($summary->currency), '', '']);
            fputcsv($file, [$sanitize('Metadata'), $sanitize('End Date'), $sanitize($summary->filters->endDate?->toDateString()), '', '', $sanitize($summary->currency), '', '']);
            fputcsv($file, [$sanitize('Metadata'), $sanitize('Preset'), $sanitize($summary->filters->preset), '', '', $sanitize($summary->currency), '', '']);
            fputcsv($file, [$sanitize('Metadata'), $sanitize('Timezone'), $sanitize($summary->filters->timezone), '', '', $sanitize($summary->currency), '', '']);

            // Section 2: Executive Summary KPIs
            $metrics = $presented['metrics'];
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('total_sales'), $sanitize('Booked Sales Revenue'), $sanitize($metrics['total_sales_minor']), $sanitize($metrics['total_sales_formatted']), $sanitize($summary->currency), $metrics['total_orders_count'], '']);
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('total_payments'), $sanitize('Succeeded Payments'), $sanitize($metrics['total_payments_minor']), $sanitize($metrics['total_payments_formatted']), $sanitize($summary->currency), $metrics['total_payments_count'], '']);
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('total_refunds'), $sanitize('Succeeded Refunds'), $sanitize($metrics['total_refunds_minor']), $sanitize($metrics['total_refunds_formatted']), $sanitize($summary->currency), $metrics['total_refunds_count'], '']);
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('total_expenses'), $sanitize('Approved Expenses'), $sanitize($metrics['total_expenses_minor']), $sanitize($metrics['total_expenses_formatted']), $sanitize($summary->currency), $metrics['total_expenses_count'], '']);
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('total_outstanding'), $sanitize('As-Of Outstanding Receivables'), $sanitize($metrics['total_outstanding_minor']), $sanitize($metrics['total_outstanding_formatted']), $sanitize($summary->currency), '', '']);
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('net_cash_flow'), $sanitize('Net Cash Flow'), $sanitize($metrics['net_cash_flow_minor']), $sanitize($metrics['net_cash_flow_formatted']), $sanitize($summary->currency), '', '']);
            fputcsv($file, [$sanitize('Executive Summary'), $sanitize('net_operating_income'), $sanitize('Net Operating Income'), $sanitize($metrics['net_operating_income_minor']), $sanitize($metrics['net_operating_income_formatted']), $sanitize($summary->currency), '', '']);

            // Section 3: Monthly Trends
            foreach ($presented['monthly_trend'] as $row) {
                fputcsv($file, [
                    $sanitize('Monthly Trend'),
                    $sanitize('period'),
                    $sanitize($row['period']),
                    $sanitize((string) $row['net_operating_income_minor']),
                    $sanitize($row['net_operating_income_formatted']),
                    $sanitize($summary->currency),
                    '',
                    '',
                ]);
            }

            // Section 4: Expense Categories
            foreach ($presented['expense_categories'] as $cat) {
                fputcsv($file, [
                    $sanitize('Expense Category'),
                    $sanitize($cat['category_code']),
                    $sanitize($cat['category_name']),
                    $sanitize((string) $cat['total_minor']),
                    $sanitize($cat['total_formatted']),
                    $sanitize($summary->currency),
                    $cat['expense_count'],
                    $cat['share_basis_points'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Base query for active, non-cancelled orders recognized for Booked Sales Revenue.
     */
    protected function baseOrderQuery(?CarbonImmutable $startDate, ?CarbonImmutable $endDate): Builder
    {
        $query = Order::query()->revenueRecognized();

        $dateCol = 'COALESCE(orders.placed_at, orders.created_at)';

        if ($startDate) {
            $query->whereRaw("{$dateCol} >= ?", [$startDate->toDateTimeString()]);
        }
        if ($endDate) {
            $query->whereRaw("{$dateCol} <= ?", [$endDate->toDateTimeString()]);
        }

        return $query;
    }

    /**
     * Base query for succeeded customer payments.
     */
    protected function basePaymentQuery(?CarbonImmutable $startDate, ?CarbonImmutable $endDate): Builder
    {
        $query = Payment::query()->where('payments.status', Payment::STATUS_SUCCEEDED);

        $dateCol = 'COALESCE(payments.paid_at, payments.created_at)';

        if ($startDate) {
            $query->whereRaw("{$dateCol} >= ?", [$startDate->toDateTimeString()]);
        }
        if ($endDate) {
            $query->whereRaw("{$dateCol} <= ?", [$endDate->toDateTimeString()]);
        }

        return $query;
    }

    /**
     * Base query for succeeded refunds.
     */
    protected function baseRefundQuery(?CarbonImmutable $startDate, ?CarbonImmutable $endDate): Builder
    {
        $query = Refund::query()->where('refunds.status', Refund::STATUS_SUCCEEDED);

        $dateCol = 'COALESCE(refunds.processed_at, refunds.created_at)';

        if ($startDate) {
            $query->whereRaw("{$dateCol} >= ?", [$startDate->toDateTimeString()]);
        }
        if ($endDate) {
            $query->whereRaw("{$dateCol} <= ?", [$endDate->toDateTimeString()]);
        }

        return $query;
    }

    /**
     * Base query for approved expenses filtered by occurred_at (date column).
     */
    protected function baseExpenseQuery(?CarbonImmutable $startDate, ?CarbonImmutable $endDate): Builder
    {
        $query = Expense::query()->where('expenses.status', Expense::STATUS_APPROVED);

        if ($startDate) {
            $query->where('expenses.occurred_at', '>=', $startDate->toDateString());
        }
        if ($endDate) {
            $query->where('expenses.occurred_at', '<=', $endDate->toDateString());
        }

        return $query;
    }

    /**
     * Calculate per-order as-of outstanding receivables clamped to >= 0 per order.
     */
    protected function calculatePerOrderOutstandingReceivables(?CarbonImmutable $endDate): int
    {
        $cutoffStr = $endDate ? $endDate->toDateTimeString() : CarbonImmutable::now()->toDateTimeString();

        // 1. Get eligible orders placed on or before cutoff
        $eligibleOrders = Order::query()
            ->receivableEligible()
            ->whereRaw('COALESCE(orders.placed_at, orders.created_at) <= ?', [$cutoffStr])
            ->select('orders.id', 'orders.total_amount_minor')
            ->get();

        if ($eligibleOrders->isEmpty()) {
            return 0;
        }

        $orderIds = $eligibleOrders->pluck('id')->all();

        // 2. Fetch payments per order through cutoff
        $paymentsByOrder = Payment::query()
            ->whereIn('order_id', $orderIds)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereRaw('COALESCE(paid_at, created_at) <= ?', [$cutoffStr])
            ->groupBy('order_id')
            ->selectRaw('order_id, SUM(amount_minor) as total_paid')
            ->pluck('total_paid', 'order_id')
            ->all();

        // 3. Fetch refunds per order through cutoff
        $refundsByOrder = Refund::query()
            ->whereIn('order_id', $orderIds)
            ->where('status', Refund::STATUS_SUCCEEDED)
            ->whereRaw('COALESCE(processed_at, created_at) <= ?', [$cutoffStr])
            ->groupBy('order_id')
            ->selectRaw('order_id, SUM(amount_minor) as total_refunded')
            ->pluck('total_refunded', 'order_id')
            ->all();

        $totalReceivable = 0;

        foreach ($eligibleOrders as $order) {
            $orderTotal = (int) $order->total_amount_minor;
            $paid = (int) ($paymentsByOrder[$order->id] ?? 0);
            $refunded = (int) ($refundsByOrder[$order->id] ?? 0);

            $netPaid = $paid - $refunded;
            $orderReceivable = max(0, $orderTotal - $netPaid);

            $totalReceivable += $orderReceivable;
        }

        return $totalReceivable;
    }

    /**
     * Zero-fill monthly trends across full date range.
     */
    protected function getZeroFilledMonthlyTrend(?CarbonImmutable $startDate, ?CarbonImmutable $endDate): array
    {
        $driver = DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        // Monthly sales
        $salesExpr = $isSqlite ? "strftime('%Y-%m', COALESCE(orders.placed_at, orders.created_at))" : "DATE_FORMAT(COALESCE(orders.placed_at, orders.created_at), '%Y-%m')";
        $salesMonthly = $this->baseOrderQuery($startDate, $endDate)
            ->selectRaw("{$salesExpr} as period, SUM(orders.total_amount_minor) as total_minor")
            ->groupBy('period')
            ->pluck('total_minor', 'period')
            ->all();

        // Monthly payments
        $pmExpr = $isSqlite ? "strftime('%Y-%m', COALESCE(payments.paid_at, payments.created_at))" : "DATE_FORMAT(COALESCE(payments.paid_at, payments.created_at), '%Y-%m')";
        $paymentsMonthly = $this->basePaymentQuery($startDate, $endDate)
            ->selectRaw("{$pmExpr} as period, SUM(payments.amount_minor) as total_minor")
            ->groupBy('period')
            ->pluck('total_minor', 'period')
            ->all();

        // Monthly refunds
        $rfExpr = $isSqlite ? "strftime('%Y-%m', COALESCE(refunds.processed_at, refunds.created_at))" : "DATE_FORMAT(COALESCE(refunds.processed_at, refunds.created_at), '%Y-%m')";
        $refundsMonthly = $this->baseRefundQuery($startDate, $endDate)
            ->selectRaw("{$rfExpr} as period, SUM(refunds.amount_minor) as total_minor")
            ->groupBy('period')
            ->pluck('total_minor', 'period')
            ->all();

        // Monthly expenses
        $expExpr = $isSqlite ? "strftime('%Y-%m', expenses.occurred_at)" : "DATE_FORMAT(expenses.occurred_at, '%Y-%m')";
        $expensesMonthly = $this->baseExpenseQuery($startDate, $endDate)
            ->selectRaw("{$expExpr} as period, SUM(expenses.amount_minor) as total_minor")
            ->groupBy('period')
            ->pluck('total_minor', 'period')
            ->all();

        // Determine all periods between startDate and endDate
        $startMonth = $startDate ? $startDate->startOfMonth() : CarbonImmutable::now()->startOfMonth();
        $endMonth = $endDate ? $endDate->startOfMonth() : CarbonImmutable::now()->startOfMonth();

        $periods = [];
        $curr = $startMonth;
        while ($curr->lte($endMonth)) {
            $periods[] = $curr->format('Y-m');
            $curr = $curr->addMonth();
        }

        $trend = [];
        foreach ($periods as $p) {
            $sales = (int) ($salesMonthly[$p] ?? 0);
            $pm = (int) ($paymentsMonthly[$p] ?? 0);
            $rf = (int) ($refundsMonthly[$p] ?? 0);
            $exp = (int) ($expensesMonthly[$p] ?? 0);

            $netCash = $pm - $rf - $exp;
            $netIncome = $sales - $rf - $exp;

            $trend[] = [
                'period' => $p,
                'sales_minor' => (string) $sales,
                'payments_minor' => (string) $pm,
                'refunds_minor' => (string) $rf,
                'expenses_minor' => (string) $exp,
                'net_cash_flow_minor' => (string) $netCash,
                'net_operating_income_minor' => (string) $netIncome,
            ];
        }

        return $trend;
    }

    /**
     * Group expenses by category with share basis points and soft-deleted category support.
     */
    protected function getExpenseCategoryBreakdown(?CarbonImmutable $startDate, ?CarbonImmutable $endDate, int $totalApprovedExpenses): array
    {
        $query = $this->baseExpenseQuery($startDate, $endDate)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('
                expense_categories.id as category_id,
                expense_categories.code as category_code,
                expense_categories.name as category_name,
                expense_categories.deleted_at as deleted_at,
                SUM(expenses.amount_minor) as total_minor,
                COUNT(expenses.id) as expense_count
            ')
            ->groupBy('expense_categories.id', 'expense_categories.code', 'expense_categories.name', 'expense_categories.deleted_at')
            ->orderBy('expense_categories.id', 'asc')
            ->get();

        $breakdown = [];
        foreach ($query as $row) {
            $catTotal = (int) $row->total_minor;
            $shareBps = $totalApprovedExpenses > 0
                ? (int) round(($catTotal * 10000) / $totalApprovedExpenses, 0, PHP_ROUND_HALF_UP)
                : 0;

            $breakdown[] = [
                'category_code' => $row->category_code,
                'category_name' => $row->category_name.($row->deleted_at !== null ? ' (Soft Deleted)' : ''),
                'total_minor' => (string) $catTotal,
                'expense_count' => (int) $row->expense_count,
                'share_basis_points' => $shareBps,
                'is_deleted' => $row->deleted_at !== null,
            ];
        }

        return $breakdown;
    }
}
