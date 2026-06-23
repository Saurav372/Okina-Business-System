<?php

namespace App\Http\Requests\Lead;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', 'string', Rule::in(Lead::SOURCES)],
            'source_detail' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', Rule::in(Lead::STATUSES)],
            'priority' => ['nullable', 'string', Rule::in(Lead::PRIORITIES)],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'company_name' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'interest_summary' => ['nullable', 'string', 'max:300'],
            'requirements' => ['nullable', 'string'],
            'product_interest' => ['nullable', 'array'],
        ];
    }
}
