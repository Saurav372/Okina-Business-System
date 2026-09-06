<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class ExpenseCategoryPolicy
{
    protected function hasAnyPermission(Authenticatable $actor, array $permissions): bool
    {
        if (! ($actor instanceof User)) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($actor->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    public function viewAny(Authenticatable $actor): bool
    {
        return $this->hasAnyPermission($actor, ['expense_categories.view', 'expense_categories.manage', 'expenses.view', 'finance.manage_expenses']);
    }

    public function view(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        return $this->hasAnyPermission($actor, ['expense_categories.view', 'expense_categories.manage', 'expenses.view', 'finance.manage_expenses']);
    }

    public function create(Authenticatable $actor): bool
    {
        return $this->hasAnyPermission($actor, ['expense_categories.manage', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function update(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        return $this->hasAnyPermission($actor, ['expense_categories.manage', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function delete(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        return $this->hasAnyPermission($actor, ['expense_categories.delete', 'expense_categories.manage', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function toggleActive(Authenticatable $actor, ExpenseCategory $expenseCategory): bool
    {
        return $this->hasAnyPermission($actor, ['expense_categories.manage', 'expenses.manage', 'finance.manage_expenses']);
    }
}
