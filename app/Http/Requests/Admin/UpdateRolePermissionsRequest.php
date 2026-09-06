<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Hard policy: Role-permission matrix modifications are strictly reserved for Super Admin
        return $user && $user->hasRole(Role::SUPER_ADMIN);
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,slug'],
        ];
    }
}
