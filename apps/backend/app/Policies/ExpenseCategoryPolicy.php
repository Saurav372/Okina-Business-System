<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class ExpenseCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.manage_expenses');
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.manage_expenses');
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.manage_expenses');
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.manage_expenses');
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        if ($actor instanceof User) {
            return $actor->hasPermissionTo('finance.manage_expenses');
        }

        return false;
    }
}
