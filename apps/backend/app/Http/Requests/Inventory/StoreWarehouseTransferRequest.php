<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryLocation;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseTransferRequest extends FormRequest
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
            'product_sku_id' => ['required', 'integer', 'exists:product_skus,id'],
            'source_location' => ['required', Rule::enum(InventoryLocation::class)],
            'destination_location' => ['required', Rule::enum(InventoryLocation::class), 'different:source_location'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_location.different' => 'Source and destination locations cannot be identical.',
            'quantity.min' => 'Transfer stock quantity must be at least 1 unit.',
        ];
    }
}
