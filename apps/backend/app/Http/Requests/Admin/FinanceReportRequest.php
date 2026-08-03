<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FinanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Parameter validation only; action authorization handled in Controller/Gates
        return true;
    }

    public function rules(): array
    {
        return [
            'preset' => ['nullable', 'string', 'in:this_month,last_month,this_quarter,current_fiscal_year,custom'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'group_by' => ['nullable', 'string', 'in:month'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $preset = (string) $this->input('preset', '');
            $hasStart = $this->filled('start_date');
            $hasEnd = $this->filled('end_date');

            // If preset is custom, both start_date and end_date are required
            if ($preset === 'custom' && (! $hasStart || ! $hasEnd)) {
                $validator->errors()->add('start_date', 'Both start_date and end_date are required for a custom date range.');
            }

            // If preset is omitted but one-sided custom date range is provided, reject with 422
            if ($preset === '' && (($hasStart && ! $hasEnd) || (! $hasStart && $hasEnd))) {
                $validator->errors()->add('start_date', 'Both start_date and end_date are required for a custom date range.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'The end_date must be a date after or equal to start_date.',
        ];
    }
}
