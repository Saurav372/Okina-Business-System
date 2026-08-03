<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateExpenseCategoryRequest extends FormRequest
{
    protected $errorBag = 'category';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $raw = trim((string) $this->code);
            // Normalize to slug (lowercase, replace non-alphanumeric with dash, trim dashes)
            $normalized = preg_replace('/[^a-z0-9]+/', '-', strtolower($raw));
            $normalized = trim($normalized, '-');
            $this->merge(['code' => $normalized]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$|^[a-z0-9]$/',
                Rule::unique('expense_categories', 'code'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
