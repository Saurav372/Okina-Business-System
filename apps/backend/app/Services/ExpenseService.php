<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    /**
     * Create a new expense record in DRAFT status.
     *
     * @param  array{expense_category_id?: int, expense_category_public_id?: string, amount_minor: int, currency?: string, notes?: string|null, reference?: string|null, occurred_at: string, status?: string}  $data
     */
    public function createExpense(array $data, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();

        // 1. Resolve Expense Category
        $category = null;
        if (! empty($data['expense_category_id'])) {
            $category = ExpenseCategory::findOrFail($data['expense_category_id']);
        } elseif (! empty($data['expense_category_public_id'])) {
            $category = ExpenseCategory::where('public_id', $data['expense_category_public_id'])->firstOrFail();
        } else {
            throw ValidationException::withMessages([
                'expense_category_id' => ['An expense category is required.'],
            ]);
        }

        // 2. Service-level Category Active Validation
        try {
            $category->ensureCanAssignToExpense();
        } catch (\LogicException $e) {
            throw ValidationException::withMessages([
                'expense_category_id' => [$e->getMessage()],
            ]);
        }

        return DB::transaction(function () use ($data, $category, $actor) {
            $expense = new Expense;
            $expense->expense_category_id = $category->id;
            $expense->amount_minor = (int) $data['amount_minor'];
            $expense->currency = $data['currency'] ?? 'INR';
            $expense->notes = $data['notes'] ?? null;
            $expense->reference = $data['reference'] ?? null;
            $expense->status = $data['status'] ?? Expense::STATUS_DRAFT;
            $expense->occurred_at = $data['occurred_at'];
            $expense->recorded_by_user_id = $actor->id;
            $expense->save();

            $expense->load(['expenseCategory', 'recordedBy']);

            DB::afterCommit(function () use ($expense, $actor) {
                event(new AuditEvent('expense.created', $actor, [
                    'expense_id' => $expense->id,
                    'public_id' => $expense->public_id,
                    'expense_category_id' => $expense->expense_category_id,
                    'amount_minor' => $expense->amount_minor,
                    'currency' => $expense->currency,
                    'status' => $expense->status,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $expense;
        });
    }

    /**
     * Submit an expense for approval.
     */
    public function submitExpense(Expense $expense, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();

        return DB::transaction(function () use ($expense, $actor) {
            /** @var Expense $locked */
            $locked = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            $oldStatus = $locked->status;
            try {
                $locked->submit($actor);
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'status' => [$e->getMessage()],
                ]);
            }

            $locked->load(['expenseCategory', 'recordedBy']);

            DB::afterCommit(function () use ($locked, $oldStatus, $actor) {
                event(new AuditEvent('expense.submitted', $actor, [
                    'expense_id' => $locked->id,
                    'public_id' => $locked->public_id,
                    'from_status' => $oldStatus,
                    'to_status' => $locked->status,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }

    /**
     * Approve an expense.
     */
    public function approveExpense(Expense $expense, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();

        return DB::transaction(function () use ($expense, $actor) {
            /** @var Expense $locked */
            $locked = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            $oldStatus = $locked->status;
            try {
                $locked->approve($actor);
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'status' => [$e->getMessage()],
                ]);
            }

            $locked->load(['expenseCategory', 'recordedBy']);

            DB::afterCommit(function () use ($locked, $oldStatus, $actor) {
                event(new AuditEvent('expense.approved', $actor, [
                    'expense_id' => $locked->id,
                    'public_id' => $locked->public_id,
                    'from_status' => $oldStatus,
                    'to_status' => $locked->status,
                    'approved_at' => $locked->approved_at?->format('c'),
                    'amount_minor' => $locked->amount_minor,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }

    /**
     * Reject an expense with a mandatory reason.
     */
    public function rejectExpense(Expense $expense, ?User $actor = null, string $reason = ''): Expense
    {
        $actor = $actor ?: Auth::user();

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => ['A valid rejection reason must be provided.'],
            ]);
        }

        return DB::transaction(function () use ($expense, $actor, $reason) {
            /** @var Expense $locked */
            $locked = Expense::query()->whereKey($expense->id)->lockForUpdate()->firstOrFail();

            $oldStatus = $locked->status;
            try {
                $locked->reject($actor, $reason);
            } catch (\LogicException $e) {
                throw ValidationException::withMessages([
                    'status' => [$e->getMessage()],
                ]);
            }

            $locked->load(['expenseCategory', 'recordedBy']);

            DB::afterCommit(function () use ($locked, $oldStatus, $reason, $actor) {
                event(new AuditEvent('expense.rejected', $actor, [
                    'expense_id' => $locked->id,
                    'public_id' => $locked->public_id,
                    'from_status' => $oldStatus,
                    'to_status' => $locked->status,
                    'rejection_reason' => $reason,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }
}
