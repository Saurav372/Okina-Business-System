<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_slug' => ['required', 'string', 'max:200'],
            'sku_code' => ['required', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'customization_snapshot' => ['required', 'array'],
        ];
    }
}
