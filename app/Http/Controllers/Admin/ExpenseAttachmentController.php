<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Services\ExpenseAttachmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseAttachmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ExpenseAttachmentService $attachmentService
    ) {}

    /**
     * Download proof attachment file.
     */
    public function download(Request $request, Expense $expense, ExpenseAttachment $attachment): StreamedResponse
    {
        $this->authorize('viewAttachment', $expense);

        // Parent-child binding security check
        if ($attachment->expense_id !== $expense->id) {
            abort(404, 'Attachment does not belong to the specified expense.');
        }

        if (! Storage::disk($attachment->disk)->exists($attachment->storage_path)) {
            abort(404, 'Physical proof attachment file not found on storage disk.');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->storage_path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type]
        );
    }

    /**
     * Remove proof attachment from a draft/rejected expense.
     */
    public function destroy(Request $request, Expense $expense, ExpenseAttachment $attachment): JsonResponse|RedirectResponse
    {
        $this->authorize('deleteAttachment', $expense);

        if ($attachment->expense_id !== $expense->id) {
            abort(404, 'Attachment does not belong to the specified expense.');
        }

        $this->attachmentService->removeAttachment($expense, $attachment, $request->user());

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Proof attachment removed successfully.']);
        }

        return redirect()->back()->with('success', 'Proof attachment removed successfully.');
    }
}
