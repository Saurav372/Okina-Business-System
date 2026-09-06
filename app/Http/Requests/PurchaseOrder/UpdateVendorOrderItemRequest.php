<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorOrderItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $this->user() && $this->user()->can('update', $item);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity_ordered' => ['nullable', 'integer', 'min:1'],
            'unit_cost_minor' => ['nullable', 'integer', 'min:0'],
            'tax_amount_minor' => ['nullable', 'integer', 'min:0'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
