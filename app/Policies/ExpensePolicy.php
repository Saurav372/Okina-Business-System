<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    protected function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    public function viewAny(User $user): bool
    {
        return $this->hasAnyPermission($user, ['expenses.view', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->hasAnyPermission($user, ['expenses.view', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyPermission($user, ['expenses.create', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function update(User $user, Expense $expense): bool
    {
        if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PENDING_APPROVAL], true)) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.manage', 'finance.manage_expenses']);
    }

    public function delete(User $user, Expense $expense): bool
    {
        if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PENDING_APPROVAL], true)) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.delete', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function submit(User $user, Expense $expense): bool
    {
        if ($expense->status === Expense::STATUS_APPROVED) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.manage', 'expenses.create', 'finance.manage_expenses']);
    }

    public function withdraw(User $user, Expense $expense): bool
    {
        if ($expense->status !== Expense::STATUS_PENDING_APPROVAL) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.manage', 'finance.manage_expenses']);
    }

    public function approve(User $user, Expense $expense): bool
    {
        if ($expense->status !== Expense::STATUS_PENDING_APPROVAL) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.approve', 'finance.approve_expenses', 'expenses.manage']);
    }

    public function reject(User $user, Expense $expense): bool
    {
        if ($expense->status !== Expense::STATUS_PENDING_APPROVAL) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.approve', 'finance.approve_expenses', 'expenses.manage']);
    }

    public function viewAttachment(User $user, Expense $expense): bool
    {
        return $this->hasAnyPermission($user, ['expenses.view', 'expenses.manage', 'finance.manage_expenses']);
    }

    public function deleteAttachment(User $user, Expense $expense): bool
    {
        if ($expense->status === Expense::STATUS_APPROVED) {
            return false;
        }

        return $this->hasAnyPermission($user, ['expenses.manage', 'expenses.delete', 'finance.manage_expenses']);
    }

    public function viewExpenseReports(User $user): bool
    {
        return $this->hasAnyPermission($user, ['reports.view', 'expenses.view', 'expenses.manage', 'finance.manage_expenses']);
    }
}
