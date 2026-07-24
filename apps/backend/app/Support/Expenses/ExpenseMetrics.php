<?php

namespace App\Support\Expenses;

use App\Models\Expense;

class ExpenseMetrics
{
    public int $totalApprovedMinor;

    public int $pendingApprovalCount;

    public int $rejectedCount;

    public int $totalExpensesCount;

    public function __construct(?ExpenseFilters $filters = null)
    {
        $query = ExpenseQueryBuilder::baseQuery();

        if ($filters) {
            ExpenseQueryBuilder::applyFilters($query, $filters);
        }

        $this->totalExpensesCount = (clone $query)->count();

        $this->totalApprovedMinor = (int) (clone $query)
            ->where('status', Expense::STATUS_APPROVED)
            ->sum('amount_minor');

        $this->pendingApprovalCount = (clone $query)
            ->where('status', Expense::STATUS_PENDING_APPROVAL)
            ->count();

        $this->rejectedCount = (clone $query)
            ->where('status', Expense::STATUS_REJECTED)
            ->count();
    }
}
