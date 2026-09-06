<?php

namespace App\Http\Resources;

use App\Models\ExpenseAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExpenseAttachment */
class ExpenseAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'checksum' => $this->checksum,
            'uploaded_at' => $this->uploaded_at?->toIso8601String(),
            'download_url' => route('admin.expenses.attachments.download', [
                'expense' => $this->expense?->public_id ?: $this->expense_id,
                'attachment' => $this->public_id,
            ]),
        ];
    }
}
