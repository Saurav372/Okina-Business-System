<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateStaffRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        // Anti-escalation: Role assignment is strictly restricted to Super Admin or users with manage_roles
        return $user && ($user->hasRole(Role::SUPER_ADMIN) || $user->hasPermissionTo('users.manage_roles'));
    }

    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,slug'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roles = (array) $this->input('roles', []);
            $actor = $this->user();

            if (in_array(Role::SUPER_ADMIN, $roles, true) && ! $actor?->hasRole(Role::SUPER_ADMIN)) {
                $validator->errors()->add('roles', 'Only an active Super Admin can assign the Super Admin role.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role must be assigned to the staff member.',
            'roles.min' => 'At least one role must be assigned to the staff member.',
        ];
    }
}
