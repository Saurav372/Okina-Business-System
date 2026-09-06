<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateVendorOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $purchaseOrder = $this->route('purchase_order');

        return $this->user() && $this->user()->can('update', $purchaseOrder);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'expected_at' => ['nullable', 'date'],
            'subtotal_amount_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'shipping_amount_minor' => ['nullable', 'integer', 'min:0'],
            'discount_amount_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(VendorOrderStatus::values())],
            'payment_status' => ['nullable', 'string', Rule::in(VendorOrderPaymentStatus::values())],
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
            $po = $this->route('purchase_order');

            $subtotal = $this->has('subtotal_amount_minor')
                ? (int) $this->input('subtotal_amount_minor')
                : ($po ? (int) $po->subtotal_amount_minor : 0);

            $tax = $this->has('tax_amount_minor')
                ? (int) $this->input('tax_amount_minor')
                : ($po ? (int) $po->tax_amount_minor : 0);

            $shipping = $this->has('shipping_amount_minor')
                ? (int) $this->input('shipping_amount_minor')
                : ($po ? (int) $po->shipping_amount_minor : 0);

            $discount = $this->has('discount_amount_minor')
                ? (int) $this->input('discount_amount_minor')
                : ($po ? (int) $po->discount_amount_minor : 0);

            if ($discount > ($subtotal + $tax + $shipping)) {
                $validator->errors()->add(
                    'discount_amount_minor',
                    'Discount cannot exceed the sum of subtotal, tax, and shipping.'
                );
            }
        });
    }
}
