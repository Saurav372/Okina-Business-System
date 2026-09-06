<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SalesOrderUpdateRequest extends FormRequest
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
            'customer_id' => ['required', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku_code' => ['required', 'string', 'exists:product_skus,sku_code'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.customization_snapshot' => ['nullable', 'array'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
