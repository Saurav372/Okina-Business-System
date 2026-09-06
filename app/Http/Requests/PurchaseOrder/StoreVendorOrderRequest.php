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
            'ordered_at' => ['nullable', 'date'],
            'order_date' => ['nullable', 'date'],
            'expected_at' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['draft', 'ordered'])],
            'subtotal_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.product_sku_id' => ['required_with:items', 'integer', 'exists:product_skus,id'],
            'items.*.quantity_ordered' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost_minor' => ['nullable', 'integer', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
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
