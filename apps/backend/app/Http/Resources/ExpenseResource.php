<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'amount' => number_format($this->amount_minor / 100, 2, '.', ''),
            'currency' => $this->currency,
            'notes' => $this->notes,
            'reference' => $this->reference,
            'status' => $this->status,
            'occurred_at' => $this->occurred_at->toDateString(),
            'category' => new ExpenseCategoryResource($this->whenLoaded('expenseCategory')),
            'recorded_by' => [
                'name' => $this->recordedBy?->name,
                'email' => $this->recordedBy?->email,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
