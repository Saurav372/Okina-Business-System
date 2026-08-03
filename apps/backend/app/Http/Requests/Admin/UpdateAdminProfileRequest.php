<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProfileRequest extends FormRequest
{
    /**
     * Named error bag for profile form errors.
     */
    protected $errorBag = 'profile';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->has('phone') ? trim((string) $this->input('phone')) : null;

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => $phone === '' ? null : $phone,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['prohibited'],
            'role' => ['prohibited'],
            'status' => ['prohibited'],
            'user_type' => ['prohibited'],
            'password' => ['prohibited'],
            'permissions' => ['prohibited'],
            'is_admin' => ['prohibited'],
        ];
    }
}
