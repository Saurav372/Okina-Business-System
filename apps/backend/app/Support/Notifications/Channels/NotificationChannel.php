<?php

namespace App\Support\Notifications\Channels;

use App\Models\NotificationLog;
use App\Support\Notifications\DeliveryResult;

interface NotificationChannel
{
    /**
     * Send the notification log through the concrete channel.
     */
    public function send(NotificationLog $log): DeliveryResult;
}
