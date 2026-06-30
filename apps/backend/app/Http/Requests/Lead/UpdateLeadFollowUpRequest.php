<?php

namespace App\Http\Requests\Lead;

use App\Models\LeadFollowUp;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadFollowUpRequest extends FormRequest
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
        $followUp = $this->route('follow_up');
        $followUpId = $followUp instanceof LeadFollowUp ? $followUp->id : $followUp;

        return [
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
            'due_at' => ['nullable', 'date', 'after_or_equal:now'],
            'subject' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string'],
            'notification_key' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('lead_follow_ups', 'notification_key')->ignore($followUpId),
            ],
        ];
    }
}
