<?php

namespace App\Http\Requests\Admin;

use App\Rules\ValidMoneyAmount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
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
                'sometimes',
                'string',
                Rule::exists('expense_categories', 'public_id')->where('is_active', true)->whereNull('deleted_at'),
            ],
            'amount' => [
                'sometimes',
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
            'occurred_at' => [
                'sometimes',
                'date',
                'before_or_equal:today',
            ],
            'proof_file' => [
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png,webp',
                'max:10240',
            ],
        ];
    }
}
