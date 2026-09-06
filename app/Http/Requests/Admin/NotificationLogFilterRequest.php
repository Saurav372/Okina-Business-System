<?php

namespace App\Http\Requests\Admin;

use App\Models\NotificationLog;
use Illuminate\Foundation\Http\FormRequest;

class NotificationLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $channels = implode(',', [
            NotificationLog::RECIPIENT_CUSTOMER,
            'email',
            'sms',
            'whatsapp',
            'push',
            'in_app',
        ]);

        $statuses = implode(',', [
            NotificationLog::STATUS_PENDING,
            NotificationLog::STATUS_QUEUED,
            NotificationLog::STATUS_SENT,
            NotificationLog::STATUS_FAILED,
            NotificationLog::STATUS_CANCELLED,
            NotificationLog::STATUS_SKIPPED,
        ]);

        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'channel' => ['nullable', 'string', 'in:'.$channels],
            'status' => ['nullable', 'string', 'in:'.$statuses],
            'event_type' => ['nullable', 'string', 'max:255'],
            'recipient_address' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
