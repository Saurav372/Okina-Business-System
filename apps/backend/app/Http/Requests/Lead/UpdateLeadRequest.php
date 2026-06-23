<?php

namespace App\Http\Requests\Lead;

use App\Models\Lead;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
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
            'status' => ['nullable', 'string', Rule::in(Lead::STATUSES)],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'lost_reason' => ['required_if:status,lost', 'nullable', 'string', 'max:160'],
        ];
    }
}
