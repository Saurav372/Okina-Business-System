<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole(Role::SUPER_ADMIN) || $user->hasPermissionTo('users.manage_staff'));
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:suspend,reactivate,unlock'],
        ];
    }
}
