<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SalesOrderCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_code' => ['required', 'string', 'max:80'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.customization_snapshot' => ['nullable', 'array'],
            'advance_payment' => ['nullable', 'array'],
            'advance_payment.amount_minor' => ['required_with:advance_payment', 'integer', 'min:0'],
            'advance_payment.due_date' => ['required_with:advance_payment', 'date'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
