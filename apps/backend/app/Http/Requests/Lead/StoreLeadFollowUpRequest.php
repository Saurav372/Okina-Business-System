<?php

namespace App\Http\Requests\Lead;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadFollowUpRequest extends FormRequest
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
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'due_at' => ['required', 'date', 'after_or_equal:now'],
            'subject' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string'],
            'notification_key' => ['nullable', 'string', 'max:120', 'unique:lead_follow_ups,notification_key'],
        ];
    }
}
