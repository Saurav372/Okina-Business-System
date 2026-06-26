<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentLedgerIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'provider' => 'nullable|string|max:100',
            'method' => 'nullable|string|max:100',
            'status' => ['nullable', 'string', Rule::in(['pending', 'succeeded', 'failed'])],
            'payment_type' => ['nullable', 'string', Rule::in(['full', 'partial'])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
