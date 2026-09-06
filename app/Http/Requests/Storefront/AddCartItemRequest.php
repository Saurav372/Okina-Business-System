<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'sku_code' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'selected_options' => ['nullable', 'array'],
            'selected_options.*' => ['required', 'string', 'max:100'],
            'print_position' => ['nullable', 'string', 'max:100'],
            'print_method' => ['nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
