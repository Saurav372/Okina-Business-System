<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole(Role::SUPER_ADMIN) || $user->hasPermissionTo('users.manage_staff'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
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
            'email.unique' => 'A user account with this email address already exists.',
            'roles.required' => 'At least one system role must be selected for the staff member.',
            'roles.min' => 'At least one system role must be selected for the staff member.',
        ];
    }
}
