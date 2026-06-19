<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_address_id' => ['nullable', 'integer', 'min:1'],
            'billing_address_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
