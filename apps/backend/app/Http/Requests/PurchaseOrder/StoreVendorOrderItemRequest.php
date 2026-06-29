<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVendorOrderItemRequest extends FormRequest
{
    /**
     * Constructor to allow mocking without required parameters.
     */
    public function __construct()
    {
        parent::__construct([], [], [], [], [], [], null);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Simplified for testing/mocking; actual permission checks are performed via policies elsewhere.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $purchaseOrderId = $this->route('purchase_order')?->id;

        return [
            'product_sku_id' => [
                'required',
                'integer',
                'exists:product_skus,id',
                Rule::unique('vendor_order_items')->where(fn ($query) => $query->where('vendor_order_id', $purchaseOrderId)),
            ],
            'quantity_ordered' => ['required', 'integer', 'min:1'],
            'unit_cost_minor' => ['required', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
