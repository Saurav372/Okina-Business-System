<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->hasPermissionTo('inventory.manage')
            || $user->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN, Role::INVENTORY_STAFF]);
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vendor_order_item_id' => ['required', 'integer', 'exists:vendor_order_items,id'],
            'items.*.quantity_received' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'A unique submission key is required to prevent duplicate receiving entries.',
            'items.required' => 'At least one line item quantity must be provided to process goods receipt.',
        ];
    }
}
