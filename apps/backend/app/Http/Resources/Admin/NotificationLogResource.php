<?php

namespace App\Http\Resources\Admin;

use App\Support\Notification\NotificationContentSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'template_key' => $this->template_key,
            'channel' => $this->channel,
            'status' => $this->status,
            'recipient_type' => $this->recipient_type,
            'recipient_address' => NotificationContentSanitizer::maskAddress($this->recipient_address),
            'subject_rendered' => NotificationContentSanitizer::sanitizeBody($this->subject_rendered, 200),
            'body_summary' => NotificationContentSanitizer::sanitizeBody($this->body_summary, 200),
            'created_at' => $this->created_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
            'attempts' => $this->relationLoaded('attempts') ? $this->attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'provider_reference' => $attempt->provider_reference,
                    'error_message' => $attempt->error_message,
                    'attempted_at' => $attempt->attempted_at?->toIso8601String(),
                ];
            }) : [],
        ];
    }
}
