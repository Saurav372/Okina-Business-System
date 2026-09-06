<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'amount' => number_format($this->amount_minor / 100, 2, '.', ''),
            'amount_minor' => $this->amount_minor,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'reference' => $this->reference,
            'status' => $this->status,
            'occurred_at' => $this->occurred_at?->toDateString(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'withdrawn_at' => $this->withdrawn_at?->toIso8601String(),
            'category' => new ExpenseCategoryResource($this->whenLoaded('expenseCategory')),
            'attachment' => new ExpenseAttachmentResource($this->whenLoaded('attachment')),
            'recorded_by' => [
                'name' => $this->recordedBy?->name,
                'email' => $this->recordedBy?->email,
            ],
            'history' => $this->metadata['history'] ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
