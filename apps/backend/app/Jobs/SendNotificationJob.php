<?php

namespace App\Jobs;

use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Support\Notifications\NotificationChannelRegistry;
use App\Support\Notifications\NotificationRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $notificationLogId) {}

    /**
     * Execute the job.
     */
    public function handle(
        NotificationRenderer $renderer,
        NotificationChannelRegistry $registry
    ): void {
        $log = NotificationLog::find($this->notificationLogId);

        if (! $log) {
            return;
        }

        // Idempotency guard: if already sent, cancelled, skipped, or failed, exit early
        if (in_array($log->status, [
            NotificationLog::STATUS_SENT,
            NotificationLog::STATUS_CANCELLED,
            NotificationLog::STATUS_SKIPPED,
            NotificationLog::STATUS_FAILED,
        ], true)) {
            return;
        }

        // Transition status to queued
        $log->status = NotificationLog::STATUS_QUEUED;
        $log->save();

        // 1. Resolve Template
        $template = $log->template;
        if (! $template && $log->template_key) {
            $template = NotificationTemplate::where('template_key', $log->template_key)
                ->where('channel', $log->channel)
                ->where('status', NotificationTemplate::STATUS_ACTIVE)
                ->orderByDesc('version')
                ->first();
        }

        if (! $template) {
            DB::transaction(function () use ($log): void {
                $log->status = NotificationLog::STATUS_FAILED;
                $log->failed_at = now();
                $log->save();

                NotificationDeliveryAttempt::create([
                    'notification_log_id' => $log->id,
                    'status' => 'failed',
                    'error_message' => "Active template not found for event: {$log->event_type} on channel: {$log->channel}",
                    'attempted_at' => now(),
                ]);
            });

            return;
        }

        // 2. Render templates
        try {
            $processedPayload = $renderer->processPayload($template, $log->payload ?? []);
            $subject = $renderer->renderString($template->subject_template ?? '', $processedPayload);
            $body = $renderer->renderString($template->body_template, $processedPayload);

            // Update rendered content on the log
            $log->subject_rendered = $subject;
            $log->body_summary = substr($body, 0, 1000); // summary cap
            $log->template_id = $template->id;
            $log->template_version = $template->version;
            $log->save();
        } catch (Throwable $e) {
            DB::transaction(function () use ($log, $e): void {
                $log->status = NotificationLog::STATUS_FAILED;
                $log->failed_at = now();
                $log->save();

                NotificationDeliveryAttempt::create([
                    'notification_log_id' => $log->id,
                    'status' => 'failed',
                    'error_message' => 'Rendering failed: '.$e->getMessage(),
                    'attempted_at' => now(),
                ]);
            });

            return;
        }

        // 3. Resolve adapter
        try {
            $adapter = $registry->driver($log->channel);
        } catch (Throwable $e) {
            DB::transaction(function () use ($log, $e): void {
                $log->status = NotificationLog::STATUS_FAILED;
                $log->failed_at = now();
                $log->save();

                NotificationDeliveryAttempt::create([
                    'notification_log_id' => $log->id,
                    'status' => 'failed',
                    'error_message' => 'Adapter resolution failed: '.$e->getMessage(),
                    'attempted_at' => now(),
                ]);
            });

            return;
        }

        // 4. Send via Adapter
        try {
            $result = $adapter->send($log);
        } catch (Throwable $e) {
            // Write failed attempt to DB inside transaction before rethrowing
            DB::transaction(function () use ($log, $e): void {
                $log->status = NotificationLog::STATUS_FAILED;
                $log->failed_at = now();
                $log->save();

                NotificationDeliveryAttempt::create([
                    'notification_log_id' => $log->id,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'response_payload' => ['exception' => get_class($e)],
                    'attempted_at' => now(),
                ]);
            });

            throw $e; // Rethrow for Laravel queue retry
        }

        // 5. Save outcome in a database transaction
        DB::transaction(function () use ($log, $result): void {
            $log->status = $result->success ? NotificationLog::STATUS_SENT : NotificationLog::STATUS_FAILED;
            if ($result->success) {
                $log->sent_at = now();
            } else {
                $log->failed_at = now();
            }
            $log->save();

            NotificationDeliveryAttempt::create([
                'notification_log_id' => $log->id,
                'status' => $result->success ? 'success' : 'failed',
                'provider_reference' => $result->providerReference,
                'error_message' => $result->errorMessage,
                'response_payload' => $result->responsePayload,
                'attempted_at' => now(),
            ]);
        });
    }
}
