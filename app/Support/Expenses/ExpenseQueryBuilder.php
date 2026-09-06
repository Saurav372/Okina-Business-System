<?php

namespace App\Support\Expenses;

use App\Models\Expense;
use Carbon\Carbon;
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
            'attachment',
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
            $escaped = addcslashes($filters->search, '%_');
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('public_id', 'like', "%{$escaped}%")
                    ->orWhere('reference', 'like', "%{$escaped}%")
                    ->orWhere('notes', 'like', "%{$escaped}%")
                    ->orWhereHas('expenseCategory', fn (Builder $catQuery) => $catQuery->where('name', 'like', "%{$escaped}%")
                        ->orWhere('code', 'like', "%{$escaped}%"));
            });
        }

        if (! empty($filters->status) && $filters->status !== 'all') {
            $query->where('status', $filters->status);
        }

        if ($filters->categoryId !== null) {
            $query->where('expense_category_id', $filters->categoryId);
        }

        if (! empty($filters->categoryPublicId)) {
            $query->whereHas('expenseCategory', fn (Builder $catQuery) => $catQuery->where('public_id', $filters->categoryPublicId));
        }

        $tz = config('app.timezone', 'UTC');

        if ($filters->dateFrom) {
            $fromDate = Carbon::parse($filters->dateFrom, $tz)->startOfDay()->toDateString();
            $query->whereDate('occurred_at', '>=', $fromDate);
        }

        if ($filters->dateTo) {
            $toDate = Carbon::parse($filters->dateTo, $tz)->endOfDay()->toDateString();
            $query->whereDate('occurred_at', '<=', $toDate);
        }

        $query->orderBy($filters->sortBy, $filters->sortOrder);

        return $query;
    }
}
