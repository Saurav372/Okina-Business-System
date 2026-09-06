<?php

namespace App\Support\Notifications\Channels;

use App\Models\NotificationLog;
use App\Support\Notifications\DeliveryResult;
use Illuminate\Support\Facades\Log;

class WhatsappChannel implements NotificationChannel
{
    /**
     * Send WhatsApp notification (mock log-based implementation).
     */
    public function send(NotificationLog $log): DeliveryResult
    {
        Log::info('Sending WhatsApp notification', [
            'to' => $log->recipient_address,
            'body_summary' => $log->body_summary,
        ]);

        return DeliveryResult::success('wa_ref_'.uniqid(), [
            'provider' => 'mock_whatsapp',
            'sent_at' => now()->toDateTimeString(),
        ]);
    }
}
