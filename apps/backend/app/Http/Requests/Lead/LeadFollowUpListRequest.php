<?php

namespace App\Http\Requests\Lead;

use App\Enums\LeadFollowUpStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadFollowUpListRequest extends FormRequest
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
            'status' => [
                'nullable',
                'string',
                Rule::in(array_map(fn ($case) => $case->value, LeadFollowUpStatus::cases())),
                'prohibits:filter',
            ],
            'filter' => [
                'nullable',
                'string',
                Rule::in(['overdue', 'due_today']),
                'prohibits:status',
            ],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
