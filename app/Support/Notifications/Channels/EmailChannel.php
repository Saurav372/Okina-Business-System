<?php

namespace App\Support\Notifications\Channels;

use App\Models\NotificationLog;
use App\Support\Notifications\DeliveryResult;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannel
{
    /**
     * Send email notification (mock log-based implementation).
     */
    public function send(NotificationLog $log): DeliveryResult
    {
        Log::info('Sending Email notification', [
            'to' => $log->recipient_address,
            'subject' => $log->subject_rendered,
        ]);

        return DeliveryResult::success('email_ref_'.uniqid(), [
            'provider' => 'mock_mail',
            'sent_at' => now()->toDateTimeString(),
        ]);
    }
}
