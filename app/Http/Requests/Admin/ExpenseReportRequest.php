<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'in:'.implode(',', [
                Expense::STATUS_DRAFT,
                Expense::STATUS_PENDING_APPROVAL,
                Expense::STATUS_APPROVED,
                Expense::STATUS_REJECTED,
            ])],
            'expense_category_public_id' => ['nullable', 'string', 'exists:expense_categories,public_id'],
            'group_by' => ['nullable', 'string', 'in:month,category'],
        ];
    }
}
