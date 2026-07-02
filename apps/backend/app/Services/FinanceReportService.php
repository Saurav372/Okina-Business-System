<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceReportService
{
    /**
     * Generate financial summary and groupings.
     */
    public function generateSummary(array $filters): array
    {
        $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : null;
        $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : null;
        $groupBy = $filters['group_by'] ?? null;

        // Base query builders with canonical date filters
        $ordersQuery = $this->baseOrderQuery($startDate, $endDate);
        $paymentsQuery = $this->basePaymentQuery($startDate, $endDate);
        $refundsQuery = $this->baseRefundQuery($startDate, $endDate);
        $expensesQuery = $this->baseExpenseQuery($startDate, $endDate);

        // 1. Calculate overall stats
        $totalSales = (clone $ordersQuery)->sum('total_amount_minor');
        $totalOrdersCount = (clone $ordersQuery)->count();

        $totalPayments = (clone $paymentsQuery)->sum('amount_minor');
        $totalPaymentsCount = (clone $paymentsQuery)->count();

        $totalRefunds = (clone $refundsQuery)->sum('amount_minor');
        $totalRefundsCount = (clone $refundsQuery)->count();

        $totalExpenses = (clone $expensesQuery)->sum('amount_minor');
        $totalExpensesCount = (clone $expensesQuery)->count();

        // Outstanding balance calculation
        // total order values (active orders) minus succeeded payments (from those active orders)
        $activeOrderIds = (clone $ordersQuery)->pluck('orders.id');
        $paymentsForActiveOrders = Payment::query()
            ->whereIn('order_id', $activeOrderIds)
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->sum('amount_minor');

        $outstandingBalance = max(0, $totalSales - $paymentsForActiveOrders);

        $summary = [
            'total_sales_minor' => (int) $totalSales,
            'total_payments_minor' => (int) $totalPayments,
            'total_refunds_minor' => (int) $totalRefunds,
            'total_expenses_minor' => (int) $totalExpenses,
            'total_outstanding_minor' => (int) $outstandingBalance,
            'total_orders_count' => (int) $totalOrdersCount,
            'total_payments_count' => (int) $totalPaymentsCount,
            'total_refunds_count' => (int) $totalRefundsCount,
            'total_expenses_count' => (int) $totalExpensesCount,
        ];

        $response = [
            'currency' => 'INR',
            'summary' => $summary,
        ];

        // 2. Perform Grouping
        if ($groupBy === 'month') {
            $response['monthly'] = $this->getMonthlyGrouping($startDate, $endDate);
        } elseif ($groupBy === 'category') {
            $response['sales_by_category'] = $this->getSalesByCategoryGrouping($startDate, $endDate);
            $response['expenses_by_category'] = $this->getExpensesByCategoryGrouping($startDate, $endDate);
        }

        return $response;
    }

    /**
     * Get base query for active, non-cancelled orders.
     */
    protected function baseOrderQuery(?Carbon $startDate, ?Carbon $endDate): Builder
    {
        $query = Order::query()
            ->where('orders.status', '!=', OrderStatus::PendingPayment->value())
            ->where('orders.status', '!=', OrderStatus::Cancelled->value());

        $dateCol = 'COALESCE(orders.placed_at, orders.created_at)';

        if ($startDate) {
            $query->whereRaw("{$dateCol} >= ?", [$startDate]);
        }
        if ($endDate) {
            $query->whereRaw("{$dateCol} <= ?", [$endDate]);
        }

        return $query;
    }

    /**
     * Get base query for succeeded payments.
     */
    protected function basePaymentQuery(?Carbon $startDate, ?Carbon $endDate): Builder
    {
        $query = Payment::query()->where('payments.status', Payment::STATUS_SUCCEEDED);

        if ($startDate) {
            $query->where('payments.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('payments.created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Get base query for succeeded refunds.
     */
    protected function baseRefundQuery(?Carbon $startDate, ?Carbon $endDate): Builder
    {
        $query = Refund::query()->where('refunds.status', Refund::STATUS_SUCCEEDED);

        if ($startDate) {
            $query->where('refunds.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('refunds.created_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Get base query for approved expenses.
     */
    protected function baseExpenseQuery(?Carbon $startDate, ?Carbon $endDate): Builder
    {
        $query = Expense::query()->where('expenses.status', Expense::STATUS_APPROVED);

        if ($startDate) {
            $query->where('expenses.occurred_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('expenses.occurred_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Monthly Grouping engine (SQLite vs MySQL).
     */
    protected function getMonthlyGrouping(?Carbon $startDate, ?Carbon $endDate): array
    {
        $driver = DB::connection()->getDriverName();
        $isSqlite = $driver === 'sqlite';

        // 1. Get monthly sales
        $salesDateExpr = $isSqlite
            ? "strftime('%Y-%m', COALESCE(orders.placed_at, orders.created_at))"
            : "DATE_FORMAT(COALESCE(orders.placed_at, orders.created_at), '%Y-%m')";

        $salesMonthly = $this->baseOrderQuery($startDate, $endDate)
            ->selectRaw("{$salesDateExpr} as month_str, SUM(orders.total_amount_minor) as total_minor, COUNT(*) as count")
            ->groupBy('month_str')
            ->pluck('total_minor', 'month_str')
            ->all();

        $salesCountMonthly = $this->baseOrderQuery($startDate, $endDate)
            ->selectRaw("{$salesDateExpr} as month_str, COUNT(*) as count")
            ->groupBy('month_str')
            ->pluck('count', 'month_str')
            ->all();

        // 2. Get monthly payments
        $pmDateExpr = $isSqlite ? "strftime('%Y-%m', payments.created_at)" : "DATE_FORMAT(payments.created_at, '%Y-%m')";
        $paymentsMonthly = $this->basePaymentQuery($startDate, $endDate)
            ->selectRaw("{$pmDateExpr} as month_str, SUM(payments.amount_minor) as total_minor")
            ->groupBy('month_str')
            ->pluck('total_minor', 'month_str')
            ->all();

        $paymentsCountMonthly = $this->basePaymentQuery($startDate, $endDate)
            ->selectRaw("{$pmDateExpr} as month_str, COUNT(*) as count")
            ->groupBy('month_str')
            ->pluck('count', 'month_str')
            ->all();

        // 3. Get monthly refunds
        $rfDateExpr = $isSqlite ? "strftime('%Y-%m', refunds.created_at)" : "DATE_FORMAT(refunds.created_at, '%Y-%m')";
        $refundsMonthly = $this->baseRefundQuery($startDate, $endDate)
            ->selectRaw("{$rfDateExpr} as month_str, SUM(refunds.amount_minor) as total_minor")
            ->groupBy('month_str')
            ->pluck('total_minor', 'month_str')
            ->all();

        $refundsCountMonthly = $this->baseRefundQuery($startDate, $endDate)
            ->selectRaw("{$rfDateExpr} as month_str, COUNT(*) as count")
            ->groupBy('month_str')
            ->pluck('count', 'month_str')
            ->all();

        // 4. Get monthly expenses
        $expenseDateExpr = $isSqlite ? "strftime('%Y-%m', expenses.occurred_at)" : "DATE_FORMAT(expenses.occurred_at, '%Y-%m')";
        $expensesMonthly = $this->baseExpenseQuery($startDate, $endDate)
            ->selectRaw("{$expenseDateExpr} as month_str, SUM(expenses.amount_minor) as total_minor")
            ->groupBy('month_str')
            ->pluck('total_minor', 'month_str')
            ->all();

        $expensesCountMonthly = $this->baseExpenseQuery($startDate, $endDate)
            ->selectRaw("{$expenseDateExpr} as month_str, COUNT(*) as count")
            ->groupBy('month_str')
            ->pluck('count', 'month_str')
            ->all();

        // Collect all distinct months
        $allMonths = array_unique(array_merge(
            array_keys($salesMonthly),
            array_keys($paymentsMonthly),
            array_keys($refundsMonthly),
            array_keys($expensesMonthly)
        ));

        // Sort chronologically
        sort($allMonths);

        $monthlyData = [];
        foreach ($allMonths as $month) {
            if (empty($month)) {
                continue;
            }

            // Group-level outstanding balance calculation:
            // Group active orders for this month, sum their total values, and subtract succeeded payments for those orders
            $salesVal = (int) ($salesMonthly[$month] ?? 0);

            // To calculate outstanding balance for this month's orders correctly:
            $orderIdsThisMonth = Order::query()
                ->where('orders.status', '!=', OrderStatus::PendingPayment->value())
                ->where('orders.status', '!=', OrderStatus::Cancelled->value())
                ->whereRaw("{$salesDateExpr} = ?", [$month])
                ->pluck('orders.id');

            $paymentsForThisMonthOrders = Payment::query()
                ->whereIn('order_id', $orderIdsThisMonth)
                ->where('status', Payment::STATUS_SUCCEEDED)
                ->sum('amount_minor');

            $outstandingVal = max(0, $salesVal - $paymentsForThisMonthOrders);

            $monthlyData[] = [
                'month' => $month,
                'totals' => [
                    'sales_minor' => $salesVal,
                    'payments_minor' => (int) ($paymentsMonthly[$month] ?? 0),
                    'refunds_minor' => (int) ($refundsMonthly[$month] ?? 0),
                    'expenses_minor' => (int) ($expensesMonthly[$month] ?? 0),
                    'outstanding_minor' => (int) $outstandingVal,
                ],
                'counts' => [
                    'sales_count' => (int) ($salesCountMonthly[$month] ?? 0),
                    'payments_count' => (int) ($paymentsCountMonthly[$month] ?? 0),
                    'refunds_count' => (int) ($refundsCountMonthly[$month] ?? 0),
                    'expenses_count' => (int) ($expensesCountMonthly[$month] ?? 0),
                ],
            ];
        }

        return $monthlyData;
    }

    /**
     * Group sales by Product Category.
     */
    protected function getSalesByCategoryGrouping(?Carbon $startDate, ?Carbon $endDate): array
    {
        $salesQuery = $this->baseOrderQuery($startDate, $endDate)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.primary_category_id', '=', 'product_categories.id')
            ->selectRaw('
                product_categories.id as category_id,
                product_categories.name as category_name,
                product_categories.slug as category_slug,
                SUM(order_items.line_total_minor) as total_minor,
                COUNT(order_items.id) as item_count
            ')
            ->groupBy('product_categories.id', 'product_categories.name', 'product_categories.slug')
            ->orderBy('product_categories.id', 'asc')
            ->get();

        $data = [];
        foreach ($salesQuery as $row) {
            $data[] = [
                'category' => [
                    'id' => (int) $row->category_id,
                    'name' => $row->category_name,
                    'slug' => $row->category_slug,
                ],
                'total_sales_minor' => (int) $row->total_minor,
                'order_items_count' => (int) $row->item_count,
            ];
        }

        return $data;
    }

    /**
     * Group expenses by Expense Category.
     */
    protected function getExpensesByCategoryGrouping(?Carbon $startDate, ?Carbon $endDate): array
    {
        $expenseQuery = $this->baseExpenseQuery($startDate, $endDate)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('
                expense_categories.id as category_id,
                expense_categories.public_id as category_public_id,
                expense_categories.name as category_name,
                SUM(expenses.amount_minor) as total_minor,
                COUNT(expenses.id) as expense_count
            ')
            ->groupBy('expense_categories.id', 'expense_categories.public_id', 'expense_categories.name')
            ->orderBy('expense_categories.id', 'asc')
            ->get();

        $data = [];
        foreach ($expenseQuery as $row) {
            $data[] = [
                'category' => [
                    'public_id' => $row->category_public_id,
                    'name' => $row->category_name,
                ],
                'total_expenses_minor' => (int) $row->total_minor,
                'expenses_count' => (int) $row->expense_count,
            ];
        }

        return $data;
    }
}
