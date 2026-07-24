<?php

namespace App\Support\Expenses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseCatalog
{
    /**
     * Get paginated expense records with filters applied.
     */
    public function getPaginatedExpenses(ExpenseFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = ExpenseQueryBuilder::baseQuery();
        ExpenseQueryBuilder::applyFilters($query, $filters);

        return $query->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
