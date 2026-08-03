<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\User;
use App\Support\Expenses\ExpenseAttachmentCodeGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseAttachmentService
{
    /**
     * Store or replace a proof attachment for an expense in a transaction-safe manner.
     */
    public function attachProof(Expense $expense, UploadedFile $file, ?User $actor = null): ExpenseAttachment
    {
        if ($expense->status === Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'proof_file' => 'Proof attachments on approved expenses are immutable and cannot be updated or replaced.',
            ]);
        }

        $disk = 'local';
        $attachmentPublicId = ExpenseAttachmentCodeGenerator::generate();
        $originalName = $file->getClientOriginalName();
        $sanitizedName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
        $storageDir = "expenses/{$expense->public_id}/{$attachmentPublicId}";
        $storagePath = $file->storeAs($storageDir, $sanitizedName, ['disk' => $disk]);
        $fullPath = Storage::disk($disk)->path($storagePath);
        $checksum = hash_file('sha256', $fullPath);
        $sizeBytes = $file->getSize();
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();

        $existingAttachment = ExpenseAttachment::query()->where('expense_id', $expense->id)->first();
        $oldStoragePath = $existingAttachment?->storage_path;
        $isReplacement = $existingAttachment !== null;
        $oldAttachmentId = $existingAttachment?->id;

        try {
            return DB::transaction(function () use ($expense, $attachmentPublicId, $originalName, $storagePath, $disk, $mimeType, $sizeBytes, $checksum, $actor, $isReplacement, $oldStoragePath, $oldAttachmentId) {
                // Delete existing attachment row at DB level
                ExpenseAttachment::query()->where('expense_id', $expense->id)->delete();

                $attachment = ExpenseAttachmentCodeGenerator::executeWithRetry(function () use ($expense, $attachmentPublicId, $originalName, $storagePath, $disk, $mimeType, $sizeBytes, $checksum, $actor) {
                    return ExpenseAttachment::create([
                        'public_id' => $attachmentPublicId,
                        'expense_id' => $expense->id,
                        'original_name' => $originalName,
                        'storage_path' => $storagePath,
                        'disk' => $disk,
                        'mime_type' => $mimeType,
                        'size_bytes' => $sizeBytes,
                        'checksum' => $checksum,
                        'uploaded_by_user_id' => $actor?->id,
                        'uploaded_at' => now(),
                    ]);
                });

                $expenseId = $expense->id;
                $newAttachmentId = $attachment->id;
                $actorId = $actor?->id;

                DB::afterCommit(function () use ($expenseId, $isReplacement, $oldStoragePath, $oldAttachmentId, $newAttachmentId, $checksum, $actorId, $actor) {
                    try {
                        if ($isReplacement && $oldStoragePath && Storage::disk('local')->exists($oldStoragePath)) {
                            Storage::disk('local')->delete($oldStoragePath);
                        }
                    } finally {
                        if ($isReplacement) {
                            event(new AuditEvent('expense_attachments.replaced', $actor, [
                                'expense_id' => $expenseId,
                                'old_attachment_id' => $oldAttachmentId,
                                'new_attachment_id' => $newAttachmentId,
                                'checksum' => $checksum,
                                'actor_id' => $actorId,
                            ]));
                        } else {
                            event(new AuditEvent('expense_attachments.created', $actor, [
                                'expense_id' => $expenseId,
                                'attachment_id' => $newAttachmentId,
                                'checksum' => $checksum,
                                'actor_id' => $actorId,
                            ]));
                        }
                    }
                });

                return $attachment;
            });
        } catch (\Throwable $e) {
            // DB transaction failed -> delete newly stored file from disk
            $this->cleanupUncommittedFile($storagePath, $disk);

            if ($e instanceof ValidationException) {
                throw $e;
            }

            throw new \RuntimeException('Failed to save expense proof attachment: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete an attachment record and its physical file.
     */
    public function removeAttachment(Expense $expense, ExpenseAttachment $attachment, ?User $actor = null): void
    {
        if ($expense->status === Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'attachment' => 'Proof attachments on approved expenses are immutable and cannot be deleted.',
            ]);
        }

        if ($attachment->expense_id !== $expense->id) {
            throw new \InvalidArgumentException('Attachment does not belong to the specified expense.');
        }

        $storagePath = $attachment->storage_path;
        $attachmentDisk = $attachment->disk;
        $attachmentId = $attachment->id;
        $expenseId = $expense->id;

        DB::transaction(function () use ($expense) {
            ExpenseAttachment::query()->where('expense_id', $expense->id)->delete();
        });

        DB::afterCommit(function () use ($expenseId, $attachmentId, $storagePath, $attachmentDisk, $actor) {
            try {
                if ($storagePath && Storage::disk($attachmentDisk)->exists($storagePath)) {
                    Storage::disk($attachmentDisk)->delete($storagePath);
                }
            } finally {
                event(new AuditEvent('expense_attachments.deleted', $actor, [
                    'expense_id' => $expenseId,
                    'attachment_id' => $attachmentId,
                    'actor_id' => $actor?->id,
                ]));
            }
        });
    }

    /**
     * Safely delete uncommitted physical file from storage disk upon DB rollback.
     */
    public function cleanupUncommittedFile(string $storagePath, string $disk = 'local'): void
    {
        if (Storage::disk($disk)->exists($storagePath)) {
            Storage::disk($disk)->delete($storagePath);
        }
    }
}
