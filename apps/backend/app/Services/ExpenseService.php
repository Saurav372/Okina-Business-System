<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        protected ExpenseAttachmentService $attachmentService
    ) {}

    /**
     * Create a new operational expense.
     */
    public function createExpense(array $attributes, ?User $actor = null, ?UploadedFile $proofFile = null): Expense
    {
        $actor = $actor ?: Auth::user();

        // 1. Resolve & verify active category
        $category = null;
        if (! empty($attributes['expense_category_public_id'])) {
            $category = ExpenseCategory::query()
                ->where('public_id', $attributes['expense_category_public_id'])
                ->first();
        } elseif (! empty($attributes['expense_category_id'])) {
            $category = ExpenseCategory::find($attributes['expense_category_id']);
        }

        if (! $category || $category->trashed() || ! $category->is_active) {
            throw ValidationException::withMessages([
                'expense_category_public_id' => 'Expense category is inactive.',
            ]);
        }

        $expenseData = [
            'expense_category_id' => $category->id,
            'amount_minor' => (int) $attributes['amount_minor'],
            'currency' => strtoupper((string) ($attributes['currency'] ?? 'INR')),
            'notes' => $attributes['notes'] ?? null,
            'reference' => $attributes['reference'] ?? null,
            'occurred_at' => $attributes['occurred_at'],
            'status' => $attributes['status'] ?? Expense::STATUS_DRAFT,
            'recorded_by_user_id' => $actor?->id,
        ];

        return DB::transaction(function () use ($expenseData, $proofFile, $actor) {
            $expense = Expense::create($expenseData);

            if ($proofFile !== null) {
                $this->attachmentService->attachProof($expense, $proofFile, $actor);
            }

            DB::afterCommit(function () use ($expense, $actor) {
                event(new AuditEvent('expenses.created', $actor, [
                    'expense_id' => $expense->id,
                    'public_id' => $expense->public_id,
                    'amount_minor' => $expense->amount_minor,
                    'currency' => $expense->currency,
                    'status' => $expense->status,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $expense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }

    /**
     * Update an existing expense.
     */
    public function updateExpense(Expense $expense, array $attributes, ?User $actor = null, ?UploadedFile $proofFile = null): Expense
    {
        $actor = $actor ?: Auth::user();

        if (in_array($expense->status, [Expense::STATUS_APPROVED, Expense::STATUS_PENDING_APPROVAL], true)) {
            throw ValidationException::withMessages([
                'status' => ["Expenses in [{$expense->status}] status are immutable and cannot be updated."],
            ]);
        }

        // If category is being updated, verify active category
        $category = null;
        if (! empty($attributes['expense_category_public_id'])) {
            $category = ExpenseCategory::query()
                ->where('public_id', $attributes['expense_category_public_id'])
                ->first();

            if (! $category || $category->trashed() || ! $category->is_active) {
                throw ValidationException::withMessages([
                    'expense_category_public_id' => 'Expense category is inactive.',
                ]);
            }
        }

        return DB::transaction(function () use ($expense, $attributes, $category, $proofFile, $actor) {
            $oldStatus = $expense->status;
            $oldAmount = $expense->amount_minor;

            if ($category) {
                $expense->expense_category_id = $category->id;
            }

            if (isset($attributes['amount_minor'])) {
                $expense->amount_minor = (int) $attributes['amount_minor'];
            }

            if (isset($attributes['currency'])) {
                $expense->currency = strtoupper((string) $attributes['currency']);
            }

            if (array_key_exists('notes', $attributes)) {
                $expense->notes = $attributes['notes'];
            }

            if (array_key_exists('reference', $attributes)) {
                $expense->reference = $attributes['reference'];
            }

            if (isset($attributes['occurred_at'])) {
                $expense->occurred_at = $attributes['occurred_at'];
            }

            // If expense was rejected, editing resets status to draft and clears rejection_reason
            if ($oldStatus === Expense::STATUS_REJECTED) {
                $expense->status = Expense::STATUS_DRAFT;
                $expense->rejection_reason = null;
                $expense->appendHistoryEntry('edit_reset', Expense::STATUS_REJECTED, Expense::STATUS_DRAFT, $actor?->id ?: 0, now());
            }

            $expense->save();

            if ($proofFile !== null) {
                $this->attachmentService->attachProof($expense, $proofFile, $actor);
            }

            DB::afterCommit(function () use ($expense, $oldStatus, $oldAmount, $actor) {
                event(new AuditEvent('expenses.updated', $actor, [
                    'expense_id' => $expense->id,
                    'public_id' => $expense->public_id,
                    'old_amount_minor' => $oldAmount,
                    'amount_minor' => $expense->amount_minor,
                    'old_status' => $oldStatus,
                    'status' => $expense->status,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $expense->fresh(['expenseCategory', 'recordedBy', 'attachment']);
        });
    }

    /**
     * Soft delete an expense (preserves physical proof attachment files on disk).
     */
    public function deleteExpense(Expense $expense, ?User $actor = null): void
    {
        $actor = $actor ?: Auth::user();

        if ($expense->status === Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Approved expenses are immutable and cannot be deleted.'],
            ]);
        }

        if ($expense->status === Expense::STATUS_PENDING_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => ['Pending expenses cannot be deleted. Please withdraw the submission first.'],
            ]);
        }

        $expenseId = $expense->id;
        $publicId = $expense->public_id;

        DB::transaction(function () use ($expense) {
            $expense->delete();
        });

        DB::afterCommit(function () use ($expenseId, $publicId, $actor) {
            event(new AuditEvent('expenses.deleted', $actor, [
                'expense_id' => $expenseId,
                'public_id' => $publicId,
                'actor_id' => $actor?->id,
            ]));
        });
    }

    /**
     * Service-level maintenance force deletion (cleans up physical attachment files on disk).
     */
    public function forceDeleteExpense(Expense $expense, ?User $actor = null): void
    {
        $actor = $actor ?: Auth::user();

        if ($expense->status === Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Approved expenses cannot be force deleted.'],
            ]);
        }

        $attachment = $expense->attachment;

        DB::transaction(function () use ($expense) {
            if ($expense->attachment) {
                $expense->attachment->delete();
            }
            $expense->forceDelete();
        });

        if ($attachment && Storage::disk($attachment->disk)->exists($attachment->storage_path)) {
            Storage::disk($attachment->disk)->delete($attachment->storage_path);
        }
    }

    /* Forwarding helper methods delegating to ExpenseWorkflowService for backward compatibility */

    public function submitExpense(Expense $expense, ?User $actor = null): Expense
    {
        return app(ExpenseWorkflowService::class)->submit($expense, $actor);
    }

    public function approveExpense(Expense $expense, ?User $actor = null): Expense
    {
        return app(ExpenseWorkflowService::class)->approve($expense, $actor);
    }

    public function rejectExpense(Expense $expense, mixed $arg2, mixed $arg3 = null): Expense
    {
        if ($arg2 instanceof User) {
            $actor = $arg2;
            $reason = (string) $arg3;
        } else {
            $reason = (string) $arg2;
            $actor = $arg3 instanceof User ? $arg3 : null;
        }

        return app(ExpenseWorkflowService::class)->reject($expense, $reason, $actor);
    }
}
