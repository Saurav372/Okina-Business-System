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

            $expenseId = $lockedExpense->id;
            $publicId = $lockedExpense->public_id;
            $submittedAtIso = $lockedExpense->submitted_at?->toIso8601String();
            $actorId = $actor?->id;

            DB::afterCommit(function () use ($expenseId, $publicId, $oldStatus, $submittedAtIso, $actorId, $actor) {
                event(new AuditEvent('expenses.submitted', $actor, [
                    'expense_id' => $expenseId,
                    'public_id' => $publicId,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_PENDING_APPROVAL,
                    'submitted_at' => $submittedAtIso,
                    'actor_id' => $actorId,
                ]));
            });

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

            $expenseId = $lockedExpense->id;
            $publicId = $lockedExpense->public_id;
            $amountMinor = $lockedExpense->amount_minor;
            $approvedAtIso = $lockedExpense->approved_at?->toIso8601String();
            $actorId = $actor?->id;

            DB::afterCommit(function () use ($expenseId, $publicId, $amountMinor, $oldStatus, $approvedAtIso, $actorId, $actor) {
                event(new AuditEvent('expenses.approved', $actor, [
                    'expense_id' => $expenseId,
                    'public_id' => $publicId,
                    'amount_minor' => $amountMinor,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_APPROVED,
                    'approved_at' => $approvedAtIso,
                    'actor_id' => $actorId,
                ]));
            });

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

            $expenseId = $lockedExpense->id;
            $publicId = $lockedExpense->public_id;
            $rejectedAtIso = $lockedExpense->rejected_at?->toIso8601String();
            $actorId = $actor?->id;

            DB::afterCommit(function () use ($expenseId, $publicId, $oldStatus, $reason, $rejectedAtIso, $actorId, $actor) {
                event(new AuditEvent('expenses.rejected', $actor, [
                    'expense_id' => $expenseId,
                    'public_id' => $publicId,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_REJECTED,
                    'rejected_at' => $rejectedAtIso,
                    'rejection_reason' => $reason,
                    'actor_id' => $actorId,
                ]));
            });

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

            $expenseId = $lockedExpense->id;
            $publicId = $lockedExpense->public_id;
            $withdrawnAtIso = $lockedExpense->withdrawn_at?->toIso8601String();
            $actorId = $actor?->id;

            DB::afterCommit(function () use ($expenseId, $publicId, $oldStatus, $withdrawnAtIso, $actorId, $actor) {
                event(new AuditEvent('expenses.withdrawn', $actor, [
                    'expense_id' => $expenseId,
                    'public_id' => $publicId,
                    'from_status' => $oldStatus,
                    'to_status' => Expense::STATUS_DRAFT,
                    'withdrawn_at' => $withdrawnAtIso,
                    'actor_id' => $actorId,
                ]));
            });

            return $lockedExpense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }
}
