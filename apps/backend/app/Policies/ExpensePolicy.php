<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('finance.manage_expenses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('finance.manage_expenses');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('finance.manage_expenses');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('finance.manage_expenses');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('finance.manage_expenses');
    }

    /**
     * Determine whether the user can submit the model.
     */
    public function submit(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('finance.manage_expenses');
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('finance.approve_expenses');
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('finance.approve_expenses');
    }

    /**
     * Determine whether the user can view expense reports.
     */
    public function viewExpenseReports(User $user): bool
    {
        return $user->hasPermissionTo('reports.view') || $user->hasPermissionTo('finance.manage_expenses');
    }
}
