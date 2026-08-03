<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseWorkflowService
{
    /**
     * Submit a draft or rejected expense for approval.
     */
    public function submit(Expense $expense, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();

        return DB::transaction(function () use ($expense, $actor) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::query()->where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedExpense->status, [Expense::STATUS_DRAFT, Expense::STATUS_REJECTED], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot submit expense in status [{$lockedExpense->status}]. Only draft or rejected expenses can be submitted for approval."],
                ]);
            }

            // Service-authoritative active category check under row lock
            $category = ExpenseCategory::query()->where('id', $lockedExpense->expense_category_id)->first();
            if (! $category || $category->trashed() || ! $category->is_active) {
                throw ValidationException::withMessages([
                    'expense_category_public_id' => 'Expense category is inactive.',
                ]);
            }

            $oldStatus = $lockedExpense->status;
            $now = now();

            $lockedExpense->status = Expense::STATUS_PENDING_APPROVAL;
            $lockedExpense->submitted_at = $now;
            $lockedExpense->submitted_by_user_id = $actor?->id;
            $lockedExpense->rejection_reason = null;
            $lockedExpense->appendHistoryEntry('submit', $oldStatus, Expense::STATUS_PENDING_APPROVAL, $actor?->id ?: 0, $now);
            $lockedExpense->save();

            $dispatchAudit = function () use ($lockedExpense, $oldStatus, $actor) {
                event(new AuditEvent('expense.submitted', $actor, [
                    'expense_id' => $lockedExpense->id,
                    'public_id' => $lockedExpense->public_id,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_PENDING_APPROVAL,
                    'submitted_at' => $lockedExpense->submitted_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            };

            if (app()->environment('testing')) {
                $dispatchAudit();
            } else {
                DB::afterCommit($dispatchAudit);
            }

            return $lockedExpense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }

    /**
     * Approve a pending expense.
     */
    public function approve(Expense $expense, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();

        return DB::transaction(function () use ($expense, $actor) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::query()->where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->status !== Expense::STATUS_PENDING_APPROVAL) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot approve expense in status [{$lockedExpense->status}]. Only pending approval expenses can be approved."],
                ]);
            }

            $oldStatus = $lockedExpense->status;
            $now = now();

            $lockedExpense->status = Expense::STATUS_APPROVED;
            $lockedExpense->approved_at = $now;
            $lockedExpense->approved_by_user_id = $actor?->id;
            $lockedExpense->appendHistoryEntry('approve', $oldStatus, Expense::STATUS_APPROVED, $actor?->id ?: 0, $now);
            $lockedExpense->save();

            $dispatchAudit = function () use ($lockedExpense, $oldStatus, $actor) {
                event(new AuditEvent('expense.approved', $actor, [
                    'expense_id' => $lockedExpense->id,
                    'public_id' => $lockedExpense->public_id,
                    'amount_minor' => $lockedExpense->amount_minor,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_APPROVED,
                    'approved_at' => $lockedExpense->approved_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            };

            if (app()->environment('testing')) {
                $dispatchAudit();
            } else {
                DB::afterCommit($dispatchAudit);
            }

            return $lockedExpense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }

    /**
     * Reject a pending expense with a reason.
     */
    public function reject(Expense $expense, string $reason, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'A rejection reason is required.',
            ]);
        }

        return DB::transaction(function () use ($expense, $reason, $actor) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::query()->where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->status !== Expense::STATUS_PENDING_APPROVAL) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot reject expense in status [{$lockedExpense->status}]. Only pending approval expenses can be rejected."],
                ]);
            }

            $oldStatus = $lockedExpense->status;
            $now = now();

            $lockedExpense->status = Expense::STATUS_REJECTED;
            $lockedExpense->rejected_at = $now;
            $lockedExpense->rejected_by_user_id = $actor?->id;
            $lockedExpense->rejection_reason = $reason;
            $lockedExpense->appendHistoryEntry('reject', $oldStatus, Expense::STATUS_REJECTED, $actor?->id ?: 0, $now, $reason);
            $lockedExpense->save();

            $dispatchAudit = function () use ($lockedExpense, $oldStatus, $reason, $actor) {
                event(new AuditEvent('expense.rejected', $actor, [
                    'expense_id' => $lockedExpense->id,
                    'public_id' => $lockedExpense->public_id,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_REJECTED,
                    'rejected_at' => $lockedExpense->rejected_at?->toIso8601String(),
                    'rejection_reason' => $reason,
                    'actor_id' => $actor?->id,
                ]));
            };

            if (app()->environment('testing')) {
                $dispatchAudit();
            } else {
                DB::afterCommit($dispatchAudit);
            }

            return $lockedExpense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }

    /**
     * Withdraw a pending expense back to draft status.
     */
    public function withdraw(Expense $expense, ?User $actor = null): Expense
    {
        $actor = $actor ?: Auth::user();

        return DB::transaction(function () use ($expense, $actor) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::query()->where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->status !== Expense::STATUS_PENDING_APPROVAL) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot withdraw expense in status [{$lockedExpense->status}]. Only pending approval expenses can be withdrawn."],
                ]);
            }

            $oldStatus = $lockedExpense->status;
            $now = now();

            $lockedExpense->status = Expense::STATUS_DRAFT;
            $lockedExpense->withdrawn_at = $now;
            $lockedExpense->withdrawn_by_user_id = $actor?->id;
            $lockedExpense->appendHistoryEntry('withdraw', $oldStatus, Expense::STATUS_DRAFT, $actor?->id ?: 0, $now);
            $lockedExpense->save();

            $dispatchAudit = function () use ($lockedExpense, $oldStatus, $actor) {
                event(new AuditEvent('expense.withdrawn', $actor, [
                    'expense_id' => $lockedExpense->id,
                    'public_id' => $lockedExpense->public_id,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_DRAFT,
                    'withdrawn_at' => $lockedExpense->withdrawn_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            };

            if (app()->environment('testing')) {
                $dispatchAudit();
            } else {
                DB::afterCommit($dispatchAudit);
            }

            return $lockedExpense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }
}
