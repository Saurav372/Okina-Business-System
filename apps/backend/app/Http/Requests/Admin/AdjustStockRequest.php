<?php

namespace App\Http\Requests\Admin;

use App\Enums\InventoryMovementReason;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
        $allowedReasons = array_column(InventoryMovementReason::cases(), 'value');
        $sensitiveReasons = [
            InventoryMovementReason::DAMAGED_GOODS->value,
            InventoryMovementReason::INVENTORY_LOSS->value,
            InventoryMovementReason::THEFT->value,
            InventoryMovementReason::EXPIRED_STOCK->value,
        ];

        return [
            'expected_on_hand' => ['required', 'integer', 'min:0'],
            'new_on_hand' => ['required', 'integer', 'min:0'],
            'new_reserved' => ['required', 'integer', 'min:0'],
            'reason_code' => ['required', 'string', Rule::in($allowedReasons)],
            'notes' => [
                Rule::requiredIf(fn () => in_array($this->input('reason_code'), $sensitiveReasons, true)),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required_if' => 'Detailed notes are required when performing stock adjustments for the selected reason.',
        ];
    }
}
