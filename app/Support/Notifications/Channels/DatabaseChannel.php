<?php

namespace App\Support\Notifications\Channels;

use App\Models\NotificationLog;
use App\Support\Notifications\DeliveryResult;
use Illuminate\Support\Facades\Log;

class DatabaseChannel implements NotificationChannel
{
    /**
     * Send Database notification (mock log-based implementation).
     */
    public function send(NotificationLog $log): DeliveryResult
    {
        Log::info('Sending Database notification', [
            'id' => $log->id,
            'event_type' => $log->event_type,
        ]);

        return DeliveryResult::success('db_ref_'.$log->id, [
            'provider' => 'mock_database',
            'sent_at' => now()->toDateTimeString(),
        ]);
    }
}
