<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use App\Rules\ValidMoneyAmount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    protected $errorBag = 'expense';

    public function authorize(): bool
    {
        return true;
    }

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
                new ValidMoneyAmount(mustBeGreaterThanZero: true),
            ],
            'currency' => [
                'sometimes',
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
                ]),
            ],
            'occurred_at' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'proof_file' => [
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png,webp',
                'max:10240', // 10MB limit
            ],
        ];
    }
}
