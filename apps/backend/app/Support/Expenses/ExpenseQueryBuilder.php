<?php

namespace App\Support\Expenses;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;

class ExpenseQueryBuilder
{
    /**
     * Build base query for Expense records with eager relations.
     *
     * @return Builder<Expense>
     */
    public static function baseQuery(): Builder
    {
        return Expense::query()->with([
            'expenseCategory',
            'recordedBy',
        ]);
    }

    /**
     * Apply filter criteria to query builder.
     *
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public static function applyFilters(Builder $query, ExpenseFilters $filters): Builder
    {
        if (! empty($filters->search)) {
            $search = $filters->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('public_id', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('expenseCategory', fn (Builder $catQuery) => $catQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters->status) && $filters->status !== 'all') {
            $query->where('status', $filters->status);
        }

        if ($filters->categoryId !== null) {
            $query->where('expense_category_id', $filters->categoryId);
        }

        if ($filters->dateFrom) {
            $query->whereDate('occurred_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->whereDate('occurred_at', '<=', $filters->dateTo);
        }

        return $query;
    }
}
