<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Models\VendorOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVendorOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can('create', VendorOrder::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'expected_at' => ['nullable', 'date'],
            'subtotal_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $subtotal = (int) $this->input('subtotal_amount_minor', 0);
            $tax = (int) $this->input('tax_amount_minor', 0);
            $shipping = (int) $this->input('shipping_amount_minor', 0);
            $discount = (int) $this->input('discount_amount_minor', 0);

            if ($discount > ($subtotal + $tax + $shipping)) {
                $validator->errors()->add(
                    'discount_amount_minor',
                    'Discount cannot exceed the sum of subtotal, tax, and shipping.'
                );
            }
        });
    }
}
