<?php

namespace App\Support\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class NotificationDispatcher
{
    /**
     * Dispatch a notification.
     *
     * @param  array<string, mixed>  $recipient
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(
        string $eventType,
        string $channel,
        string $recipientType,
        array $recipient,
        array $payload,
        ?string $dedupeKey = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): NotificationLog {
        $recipientUserId = $recipientType === NotificationLog::RECIPIENT_USER ? ($recipient['id'] ?? null) : null;
        $recipientCustomerId = $recipientType === NotificationLog::RECIPIENT_CUSTOMER ? ($recipient['id'] ?? null) : null;
        $recipientAddress = $recipient['address'] ?? null;

        // Resolve active template early
        $template = NotificationTemplate::where('template_key', $eventType)
            ->where('channel', $channel)
            ->where('status', NotificationTemplate::STATUS_ACTIVE)
            ->orderByDesc('version')
            ->first();

        // 1. If template is missing, write failed log and log attempt immediately, do not queue
        if (! $template) {
            try {
                return DB::transaction(function () use ($eventType, $channel, $recipientType, $recipientUserId, $recipientCustomerId, $recipientAddress, $payload, $dedupeKey, $relatedType, $relatedId) {
                    $log = NotificationLog::create([
                        'event_type' => $eventType,
                        'template_key' => $eventType,
                        'channel' => $channel,
                        'status' => NotificationLog::STATUS_FAILED,
                        'recipient_type' => $recipientType,
                        'recipient_user_id' => $recipientUserId,
                        'recipient_customer_id' => $recipientCustomerId,
                        'recipient_address' => $recipientAddress,
                        'payload' => $payload,
                        'related_type' => $relatedType,
                        'related_id' => $relatedId,
                        'dedupe_key' => $dedupeKey,
                        'failed_at' => now(),
                    ]);

                    NotificationDeliveryAttempt::create([
                        'notification_log_id' => $log->id,
                        'status' => 'failed',
                        'error_message' => "Active template not found for event: {$eventType} on channel: {$channel}",
                        'attempted_at' => now(),
                    ]);

                    return $log;
                });
            } catch (QueryException $e) {
                // Enforce idempotency: unique constraint check on dedupe_key
                if ($dedupeKey && ($e->getCode() === '23000' || str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'UNIQUE'))) {
                    $existing = NotificationLog::where('dedupe_key', $dedupeKey)->first();
                    if ($existing) {
                        return $existing;
                    }
                }
                throw $e;
            }
        }

        // 2. Creating pending log with active template reference
        try {
            $log = NotificationLog::create([
                'event_type' => $eventType,
                'template_id' => $template->id,
                'template_key' => $template->template_key,
                'template_version' => $template->version,
                'channel' => $channel,
                'status' => NotificationLog::STATUS_PENDING,
                'recipient_type' => $recipientType,
                'recipient_user_id' => $recipientUserId,
                'recipient_customer_id' => $recipientCustomerId,
                'recipient_address' => $recipientAddress,
                'payload' => $payload,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'dedupe_key' => $dedupeKey,
            ]);
        } catch (QueryException $e) {
            // Enforce idempotency: unique constraint check on dedupe_key
            if ($dedupeKey && ($e->getCode() === '23000' || str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'UNIQUE'))) {
                $existing = NotificationLog::where('dedupe_key', $dedupeKey)->first();
                if ($existing) {
                    return $existing;
                }
            }
            throw $e;
        }

        // Safely queue the job after database transaction commits
        DB::afterCommit(function () use ($log): void {
            SendNotificationJob::dispatch($log->id);
        });

        return $log;
    }
}
