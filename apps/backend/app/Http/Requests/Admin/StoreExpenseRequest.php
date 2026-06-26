<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('finance.manage_expenses') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'expense_category_public_id' => [
                'required',
                'string',
                Rule::exists('expense_categories', 'public_id')->whereNull('deleted_at'),
            ],
            'amount' => [
                'required',
                'decimal:0,2',
                'gt:0',
            ],
            'currency' => [
                'nullable',
                'string',
                Rule::in(['INR']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    Expense::STATUS_DRAFT,
                    Expense::STATUS_PENDING_APPROVAL,
                    Expense::STATUS_APPROVED,
                    Expense::STATUS_REJECTED,
                ]),
            ],
            'occurred_at' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }
}
