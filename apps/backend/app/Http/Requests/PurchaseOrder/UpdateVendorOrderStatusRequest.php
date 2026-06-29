<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorOrderStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::enum(VendorOrderStatus::class),
            ],
            'payment_status' => [
                'nullable',
                Rule::enum(VendorOrderPaymentStatus::class),
            ],
        ];
    }
}
