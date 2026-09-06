<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentWebhookLog;
use App\Models\Refund;
use App\Models\User;
use App\Support\Payments\CashfreeAdapterRules;
use App\Support\Payments\GatewayFailureHandlingRules;
use App\Support\Payments\PaymentAttemptRules;
use App\Support\Payments\PaymentStateRecalculationRules;
use App\Support\Payments\PaymentVerificationRules;
use App\Support\Payments\PaymentWebhookRules;
use App\Support\Payments\RefundInterfaceRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentWebhookProcessingService
{
    public function __construct(
        private readonly CashfreeAdapterRules $cashfreeAdapter,
        private readonly PaymentWebhookRules $webhookRules,
        private readonly PaymentVerificationRules $verificationRules,
        private readonly RefundInterfaceRules $refundRules,
        private readonly PaymentStateRecalculationRules $stateRules,
        private readonly PaymentAttemptRules $attemptRules,
        private readonly GatewayFailureHandlingRules $failureRules,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string|array<int, string>>  $headers
     * @return array<string, mixed>
     */
    public function process(string $provider, array $payload, array $headers): array
    {
        $headers = $this->flattenHeaders($headers);
        $normalizedWebhook = $this->webhookRules->normalizeWebhook(array_merge($payload, ['provider' => $provider]), $headers);
        $eventId = $normalizedWebhook['provider_event_id'] ?? null;

        if (! is_string($eventId) || $eventId === '') {
            return $this->failureResult('validation_error', 'provider_event_id is required.', $normalizedWebhook, $headers, 422);
        }

        $existingLog = PaymentWebhookLog::query()
            ->where('provider', $provider)
            ->where('provider_event_id', $eventId)
            ->first();

        if ($existingLog !== null) {
            $existingLog->forceFill([
                'processing_status' => $this->webhookRules->webhookLogProcessingStatusIgnoredDuplicate(),
                'processed_at' => now(),
                'error_message' => null,
            ])->save();

            return $this->resultFromLog($existingLog, $this->duplicateResult($existingLog));
        }

        $log = PaymentWebhookLog::create([
            'provider' => $provider,
            'provider_event_id' => $eventId,
            'event_type' => (string) ($normalizedWebhook['event_type'] ?? 'unknown'),
            'provider_order_id' => $normalizedWebhook['provider_order_id'] ?? null,
            'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
            'provider_refund_id' => $normalizedWebhook['provider_refund_id'] ?? null,
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusReceived(),
            'signature_verified' => false,
            'payload_summary' => $normalizedWebhook['payload_summary'] ?? null,
            'error_message' => null,
            'received_at' => Carbon::parse($normalizedWebhook['received_at'] ?? now()),
        ]);

        if (! $this->signatureIsValid($normalizedWebhook, $headers)) {
            $this->updateLogFailure($log, 'signature_mismatch', 'Webhook signature verification failed.');

            return $this->resultFromLog($log, $this->failureResponseFromLog($log, 401));
        }

        $log->forceFill(['signature_verified' => true])->save();

        try {
            $result = DB::transaction(function () use ($provider, $normalizedWebhook, $log): array {
                $eventCategory = $this->eventCategory($normalizedWebhook);
                $attempt = $this->matchedAttempt($provider, $normalizedWebhook);

                if ($attempt === null && ! str_starts_with($eventCategory, 'refund')) {
                    $this->updateLogFailure($log, 'needs_review', 'Unable to match the webhook to a payment attempt.');

                    return $this->needsReviewResult($log, 'payment_attempt_unmatched');
                }

                if ($eventCategory === 'payment_succeeded') {
                    return $this->handleSuccessfulPayment($provider, $normalizedWebhook, $attempt, $log);
                }

                if ($eventCategory === 'payment_failed') {
                    return $this->handleFailedPayment($provider, $normalizedWebhook, $attempt, $log);
                }

                if ($eventCategory === 'refund_succeeded') {
                    return $this->handleSuccessfulRefund($provider, $normalizedWebhook, $attempt, $log);
                }

                if ($eventCategory === 'refund_failed') {
                    return $this->handleFailedRefund($provider, $normalizedWebhook, $attempt, $log);
                }

                $this->updateLogFailure($log, 'needs_review', 'Unsupported webhook event type.');

                return $this->needsReviewResult($log, 'unsupported_event_type');
            });

            $this->finalizeLog($log, $result);

            return $result;
        } catch (Throwable $throwable) {
            $failureContext = $this->failureRules->normalizeFailureContext([
                'gateway' => $provider,
                'failure_type' => 'bad_request',
                'failure_reason' => 'Webhook processing failed unexpectedly.',
                'retryable' => true,
                'provider_event_id' => $eventId,
                'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
                'provider_refund_id' => $normalizedWebhook['provider_refund_id'] ?? null,
                'failed_at' => now()->toISOString(),
            ]);

            $this->updateLogFailure($log, 'failed', 'Webhook processing failed unexpectedly.');
            $this->finalizeLog($log, [
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => (string) ($normalizedWebhook['event_type'] ?? 'unknown'),
                'processing_status' => 'failed',
                'signature_verified' => true,
                'error_message' => 'Webhook processing failed unexpectedly.',
                'retryable' => true,
                'failure_context' => $failureContext,
                'exception_class' => $throwable::class,
            ]);

            return [
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => (string) ($normalizedWebhook['event_type'] ?? 'unknown'),
                'processing_status' => 'failed',
                'signature_verified' => true,
                'error_message' => 'Webhook processing failed unexpectedly.',
                'retryable' => true,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @param  array<string, string>  $headers
     */
    private function signatureIsValid(array $normalizedWebhook, array $headers): bool
    {
        $expected = hash_hmac('sha256', $this->signaturePayload($normalizedWebhook), $this->signatureSecret());
        $given = (string) ($headers['x-signature'] ?? $headers['signature'] ?? '');

        return $given !== '' && hash_equals($expected, $given);
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     */
    private function signaturePayload(array $normalizedWebhook): string
    {
        $parts = [
            (string) ($normalizedWebhook['provider'] ?? ''),
            (string) ($normalizedWebhook['provider_event_id'] ?? ''),
            (string) ($normalizedWebhook['event_type'] ?? ''),
            (string) ($normalizedWebhook['provider_order_id'] ?? ''),
            (string) ($normalizedWebhook['provider_payment_id'] ?? ''),
            (string) ($normalizedWebhook['provider_refund_id'] ?? ''),
            (string) data_get($normalizedWebhook, 'payload_summary.status', ''),
            (string) data_get($normalizedWebhook, 'payload_summary.amount_minor', ''),
            (string) data_get($normalizedWebhook, 'payload_summary.currency', ''),
        ];

        return implode('|', $parts);
    }

    private function signatureSecret(): string
    {
        return (string) config('app.key');
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     */
    private function eventCategory(array $normalizedWebhook): string
    {
        $eventType = strtolower((string) ($normalizedWebhook['event_type'] ?? ''));
        $status = strtolower((string) data_get($normalizedWebhook, 'payload_summary.status', ''));

        if (str_contains($eventType, 'refund')) {
            return in_array($status, ['failed', 'cancelled'], true) ? 'refund_failed' : 'refund_succeeded';
        }

        if (in_array($status, ['succeeded', 'paid', 'captured', 'completed'], true) || str_contains($eventType, 'success')) {
            return 'payment_succeeded';
        }

        if (in_array($status, ['failed', 'expired', 'cancelled'], true) || str_contains($eventType, 'failed')) {
            return 'payment_failed';
        }

        return 'needs_review';
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     */
    private function matchedAttempt(string $provider, array $normalizedWebhook): ?PaymentAttempt
    {
        $query = PaymentAttempt::query()->where('provider', $provider);

        $providerOrderId = $normalizedWebhook['provider_order_id'] ?? null;
        $providerPaymentId = $normalizedWebhook['provider_payment_id'] ?? null;

        return $query->where(function ($builder) use ($providerOrderId, $providerPaymentId): void {
            if (is_string($providerOrderId) && $providerOrderId !== '') {
                $builder->orWhere('gateway_order_id', $providerOrderId);
            }

            if (is_string($providerPaymentId) && $providerPaymentId !== '') {
                $builder->orWhere('gateway_payment_id', $providerPaymentId);
            }
        })->first();
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    private function handleSuccessfulPayment(string $provider, array $normalizedWebhook, PaymentAttempt $attempt, PaymentWebhookLog $log): array
    {
        $order = $attempt->order()->firstOrFail();
        $verifiedPayment = $this->verificationRules->normalizeVerifiedPaymentPayload([
            'order_public_id' => $order->public_id,
            'payment_attempt_public_id' => $attempt->public_id,
            'provider' => $provider,
            'payment_type' => 'full',
            'amount_minor' => (int) data_get($normalizedWebhook, 'payload_summary.amount_minor', $attempt->amount_minor),
            'currency' => (string) data_get($normalizedWebhook, 'payload_summary.currency', $attempt->currency),
            'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
            'provider_order_id' => $normalizedWebhook['provider_order_id'] ?? null,
            'provider_reference' => $normalizedWebhook['provider_payment_id'] ?? $normalizedWebhook['provider_order_id'] ?? null,
            'paid_at' => $normalizedWebhook['received_at'] ?? now()->toISOString(),
        ]);

        $payment = Payment::query()->firstOrNew([
            'provider' => $provider,
            'provider_payment_id' => $verifiedPayment['provider_payment_id'],
        ]);

        $payment->forceFill([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => $verifiedPayment['payment_type'],
            'provider' => $verifiedPayment['provider'],
            'status' => $verifiedPayment['status'],
            'amount_minor' => $verifiedPayment['amount_minor'],
            'currency' => $verifiedPayment['currency'],
            'provider_order_id' => $verifiedPayment['provider_order_id'],
            'provider_reference' => $verifiedPayment['provider_reference'],
            'gateway_fee_minor' => $verifiedPayment['gateway_fee_minor'] ?? null,
            'net_amount_minor' => $verifiedPayment['net_amount_minor'] ?? null,
            'paid_at' => $verifiedPayment['paid_at'],
            'metadata' => $this->safeMetadata($normalizedWebhook),
        ])->save();

        $attempt->forceFill([
            'status' => 'succeeded',
            'gateway_order_id' => $attempt->gateway_order_id ?? ($normalizedWebhook['provider_order_id'] ?? null),
            'gateway_payment_id' => $normalizedWebhook['provider_payment_id'] ?? $attempt->gateway_payment_id,
            'gateway_reference' => $normalizedWebhook['provider_payment_id'] ?? $normalizedWebhook['provider_order_id'] ?? $attempt->gateway_reference,
            'completed_at' => now(),
        ])->save();

        $paymentStatus = $this->paymentStatusForOrder($order->refresh());
        $log->forceFill([
            'payment_attempt_id' => $attempt->id,
            'payment_id' => $payment->id,
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
            'error_message' => null,
        ])->save();

        return $this->response([
            'provider' => $provider,
            'event_id' => (string) ($normalizedWebhook['provider_event_id'] ?? ''),
            'event_type' => (string) ($normalizedWebhook['event_type'] ?? ''),
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
            'signature_verified' => true,
            'order_public_id' => $order->public_id,
            'payment_attempt_public_id' => $attempt->public_id,
            'payment_recorded' => true,
            'refund_recorded' => false,
            'payment_status' => $paymentStatus,
            'payment_attempt_status' => $attempt->status,
            'payment_id' => $payment->id,
            'amount_minor' => $payment->amount_minor,
            'currency' => $payment->currency,
            'gateway_order_id' => $attempt->gateway_order_id,
            'gateway_payment_id' => $attempt->gateway_payment_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    private function handleFailedPayment(string $provider, array $normalizedWebhook, PaymentAttempt $attempt, PaymentWebhookLog $log): array
    {
        $attempt->forceFill([
            'status' => 'failed',
            'gateway_order_id' => $attempt->gateway_order_id ?? ($normalizedWebhook['provider_order_id'] ?? null),
            'gateway_payment_id' => $normalizedWebhook['provider_payment_id'] ?? $attempt->gateway_payment_id,
            'gateway_reference' => $normalizedWebhook['provider_payment_id'] ?? $normalizedWebhook['provider_order_id'] ?? $attempt->gateway_reference,
            'completed_at' => now(),
        ])->save();

        $paymentStatus = $this->paymentStatusForOrder($attempt->order()->firstOrFail());
        $log->forceFill([
            'payment_attempt_id' => $attempt->id,
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
            'error_message' => null,
        ])->save();

        return $this->response([
            'provider' => $provider,
            'event_id' => (string) ($normalizedWebhook['provider_event_id'] ?? ''),
            'event_type' => (string) ($normalizedWebhook['event_type'] ?? ''),
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
            'signature_verified' => true,
            'order_public_id' => $attempt->order()->firstOrFail()->public_id,
            'payment_attempt_public_id' => $attempt->public_id,
            'payment_recorded' => false,
            'refund_recorded' => false,
            'payment_status' => $paymentStatus,
            'payment_attempt_status' => $attempt->status,
            'error_message' => null,
            'gateway_order_id' => $attempt->gateway_order_id,
            'gateway_payment_id' => $attempt->gateway_payment_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    private function handleSuccessfulRefund(string $provider, array $normalizedWebhook, ?PaymentAttempt $attempt, PaymentWebhookLog $log): array
    {
        return $this->processRefundLifecycle(
            $provider,
            $normalizedWebhook,
            $attempt,
            $log,
            Refund::STATUS_SUCCEEDED
        );
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    private function handleFailedRefund(string $provider, array $normalizedWebhook, ?PaymentAttempt $attempt, PaymentWebhookLog $log): array
    {
        return $this->processRefundLifecycle(
            $provider,
            $normalizedWebhook,
            $attempt,
            $log,
            Refund::STATUS_FAILED
        );
    }

    /**
     * Common generic refund lifecycle handler for success and failure webhooks.
     * Webhook processing is append-only. Incoming events may advance state, but they never regress state.
     *
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    private function processRefundLifecycle(
        string $provider,
        array $normalizedWebhook,
        ?PaymentAttempt $attempt,
        PaymentWebhookLog $log,
        string $incomingTargetStatus
    ): array {
        $payment = Payment::query()
            ->where('provider', $provider)
            ->where('provider_payment_id', $normalizedWebhook['provider_payment_id'] ?? null)
            ->first();

        if ($payment === null && $attempt !== null) {
            $payment = Payment::query()->where('order_id', $attempt->order_id)->where('status', 'succeeded')->latest('id')->first();
        }

        if ($payment === null) {
            $log->forceFill([
                'payment_attempt_id' => $attempt?->id,
                'processing_status' => 'needs_review',
                'error_message' => 'refund_record_unmatched',
                'processed_at' => now(),
            ])->save();

            return [
                'provider' => $provider,
                'event_id' => (string) ($normalizedWebhook['provider_event_id'] ?? ''),
                'event_type' => (string) ($normalizedWebhook['event_type'] ?? ''),
                'processing_status' => 'needs_review',
                'signature_verified' => true,
                'error_message' => 'refund_record_unmatched',
            ];
        }

        $refundPayload = $this->refundRules->normalizeRefundPayload([
            'order_public_id' => $payment->order->public_id,
            'payment_public_id' => $payment->id,
            'provider' => $provider,
            'refund_type' => (int) data_get($normalizedWebhook, 'payload_summary.amount_minor', 0) >= $payment->amount_minor ? 'full' : 'partial',
            'status' => $incomingTargetStatus,
            'amount_minor' => (int) data_get($normalizedWebhook, 'payload_summary.amount_minor', 0),
            'currency' => (string) data_get($normalizedWebhook, 'payload_summary.currency', 'INR'),
            'provider_refund_id' => $normalizedWebhook['provider_refund_id'] ?? null,
            'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
            'provider_reference' => $normalizedWebhook['provider_refund_id'] ?? $normalizedWebhook['provider_payment_id'] ?? null,
            'processed_at' => $normalizedWebhook['received_at'] ?? now()->toISOString(),
        ]);

        $refund = Refund::query()
            ->where('provider', $provider)
            ->where('provider_refund_id', $refundPayload['provider_refund_id'])
            ->first();

        if ($refund !== null && $refund->status === $incomingTargetStatus) {
            $log->forceFill([
                'payment_attempt_id' => $attempt?->id,
                'payment_id' => $payment->id,
                'refund_id' => $refund->id,
            ]);

            return $this->handleDuplicateWebhook($log);
        }

        $auditPayload = [];
        $dispatchAudit = false;
        $invalidTransition = false;

        try {
            $result = DB::transaction(function () use ($provider, $normalizedWebhook, $attempt, $log, $payment, $refundPayload, $incomingTargetStatus, &$auditPayload, &$dispatchAudit) {
                $refund = Refund::query()
                    ->where('provider', $provider)
                    ->where('provider_refund_id', $refundPayload['provider_refund_id'])
                    ->lockForUpdate()
                    ->first();

                if ($refund === null) {
                    $refund = new Refund;
                    $refund->order_id = $payment->order_id;
                    $refund->payment_id = $payment->id;
                    $refund->provider = $refundPayload['provider'];
                    $refund->refund_type = $refundPayload['refund_type'];
                    $refund->status = Refund::STATUS_REQUESTED;
                    $refund->amount_minor = $refundPayload['amount_minor'];
                    $refund->currency = $refundPayload['currency'];
                    $refund->provider_refund_id = $refundPayload['provider_refund_id'];
                    $refund->provider_payment_id = $refundPayload['provider_payment_id'];
                    $refund->provider_reference = $refundPayload['provider_reference'];
                    $refund->metadata = $this->safeMetadata($normalizedWebhook);
                    $refund->save();

                    $refund = Refund::query()->lockForUpdate()->findOrFail($refund->id);
                }

                if ($refund->status === $incomingTargetStatus) {
                    throw new \LogicException('idempotent_replay');
                }

                $oldStatus = $refund->status;

                // Webhook processing is append-only. Incoming events may advance state, but they never regress state.
                if ($incomingTargetStatus === Refund::STATUS_SUCCEEDED) {
                    if ($refund->status === Refund::STATUS_REQUESTED) {
                        $systemUser = User::query()->where('user_type', User::TYPE_STAFF)->first();
                        if ($systemUser === null) {
                            $systemUser = User::factory()->create(['user_type' => User::TYPE_STAFF]);
                        }
                        $refund->approve($systemUser);
                    }

                    if ($refund->status === Refund::STATUS_APPROVED) {
                        $systemUser = User::query()->where('user_type', User::TYPE_STAFF)->first();
                        if ($systemUser === null) {
                            $systemUser = User::factory()->create(['user_type' => User::TYPE_STAFF]);
                        }
                        $refund->markProcessing($systemUser, null, $refundPayload['provider_refund_id'], $refundPayload['provider_payment_id'], $refundPayload['provider_reference']);
                    }

                    $processedAt = isset($refundPayload['processed_at']) ? Carbon::parse($refundPayload['processed_at']) : null;
                    $refund->markSucceeded(
                        $processedAt,
                        $refundPayload['provider_refund_id'],
                        $refundPayload['provider_payment_id'],
                        $refundPayload['provider_reference']
                    );
                } else {
                    $processedAt = isset($refundPayload['processed_at']) ? Carbon::parse($refundPayload['processed_at']) : null;
                    $refund->markFailed(
                        $processedAt,
                        $normalizedWebhook['payload_summary']['reason_code'] ?? 'gateway_failure',
                        $normalizedWebhook['payload_summary']['reason_note'] ?? 'Refund failed via gateway'
                    );
                }

                $newStatus = $refund->status;
                $refund->save();

                $paymentStatus = $this->paymentStatusForOrder($payment->order()->firstOrFail());
                $log->forceFill([
                    'payment_attempt_id' => $attempt?->id,
                    'payment_id' => $payment->id,
                    'refund_id' => $refund->id,
                    'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
                    'error_message' => null,
                ])->save();

                $auditPayload = [
                    'refund_id' => $refund->id,
                    'refund_public_id' => $refund->id,
                    'payment_id' => $payment->id,
                    'payment_public_id' => $payment->id,
                    'order_public_id' => $payment->order?->public_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'occurred_at' => now()->toIso8601String(),
                ];

                if ($incomingTargetStatus === Refund::STATUS_FAILED) {
                    $auditPayload['reason_code'] = $refund->reason_code;
                    $auditPayload['reason_note'] = $refund->reason_note;
                }

                $dispatchAudit = true;

                return [
                    'provider' => $provider,
                    'event_id' => (string) ($normalizedWebhook['provider_event_id'] ?? ''),
                    'event_type' => (string) ($normalizedWebhook['event_type'] ?? ''),
                    'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
                    'signature_verified' => true,
                    'order_public_id' => $payment->order->public_id,
                    'payment_attempt_public_id' => $attempt?->public_id,
                    'payment_recorded' => true,
                    'refund_recorded' => true,
                    'payment_status' => $paymentStatus,
                    'payment_attempt_status' => $attempt?->status,
                    'payment_id' => $payment->id,
                    'refund_id' => $refund->id,
                    'amount_minor' => $refund->amount_minor,
                    'currency' => $refund->currency,
                ];
            });
        } catch (\LogicException $e) {
            if ($e->getMessage() === 'idempotent_replay') {
                return $this->handleDuplicateWebhook($log);
            }
            $invalidTransition = true;
        }

        if ($invalidTransition) {
            $log->forceFill([
                'payment_attempt_id' => $attempt?->id,
                'payment_id' => $payment->id,
                'refund_id' => $refund->id ?? null,
                'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
                'error_message' => 'invalid_state_transition',
                'processed_at' => now(),
            ])->save();

            return [
                'provider' => $provider,
                'event_id' => (string) ($normalizedWebhook['provider_event_id'] ?? ''),
                'event_type' => (string) ($normalizedWebhook['event_type'] ?? ''),
                'processing_status' => $this->webhookRules->webhookLogProcessingStatusProcessed(),
                'signature_verified' => true,
                'error_message' => 'invalid_state_transition',
            ];
        }

        if ($dispatchAudit) {
            $eventKey = $incomingTargetStatus === Refund::STATUS_SUCCEEDED
                ? 'refunds.refund_processing_succeeded'
                : 'refunds.refund_processing_failed';

            event(new AuditEvent($eventKey, null, $auditPayload));
        }

        return $result;
    }

    private function handleDuplicateWebhook(PaymentWebhookLog $log): array
    {
        $log->forceFill([
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusIgnoredDuplicate(),
            'processed_at' => now(),
            'error_message' => null,
        ])->save();

        return $this->resultFromLog($log, $this->duplicateResult($log));
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    private function failureResult(string $failureType, string $message, array $normalizedWebhook, array $headers, int $statusCode): array
    {
        $log = PaymentWebhookLog::create([
            'provider' => (string) ($normalizedWebhook['provider'] ?? 'cashfree'),
            'provider_event_id' => $normalizedWebhook['provider_event_id'] ?? null,
            'event_type' => (string) ($normalizedWebhook['event_type'] ?? 'unknown'),
            'provider_order_id' => $normalizedWebhook['provider_order_id'] ?? null,
            'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
            'provider_refund_id' => $normalizedWebhook['provider_refund_id'] ?? null,
            'processing_status' => 'failed',
            'signature_verified' => false,
            'payload_summary' => $normalizedWebhook['payload_summary'] ?? null,
            'error_message' => $message,
            'received_at' => Carbon::parse($normalizedWebhook['received_at'] ?? now()),
        ]);

        $failureContext = $this->failureRules->normalizeFailureContext([
            'gateway' => $normalizedWebhook['provider'] ?? 'cashfree',
            'failure_type' => $failureType,
            'failure_reason' => $message,
            'retryable' => false,
            'provider_event_id' => $normalizedWebhook['provider_event_id'] ?? null,
            'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
            'provider_refund_id' => $normalizedWebhook['provider_refund_id'] ?? null,
            'failed_at' => now()->toISOString(),
        ]);

        return [
            'http_status' => $statusCode,
            'provider' => $log->provider,
            'event_id' => $log->provider_event_id,
            'event_type' => $log->event_type,
            'processing_status' => $log->processing_status,
            'signature_verified' => false,
            'error_message' => $message,
            'retryable' => false,
            'failure_context' => $failureContext,
        ];
    }

    private function updateLogFailure(PaymentWebhookLog $log, string $status, string $message): void
    {
        $log->forceFill([
            'processing_status' => $status,
            'error_message' => $message,
            'processed_at' => now(),
        ])->save();
    }

    private function finalizeLog(PaymentWebhookLog $log, array $result): void
    {
        $log->forceFill([
            'processing_status' => (string) ($result['processing_status'] ?? $log->processing_status),
            'error_message' => $result['error_message'] ?? $log->error_message,
            'processed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function response(array $result): array
    {
        return array_filter($result, static fn ($value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicateResult(PaymentWebhookLog $log): array
    {
        $log->loadMissing(['paymentAttempt.order', 'payment.order', 'refund.order']);
        $attempt = $log->paymentAttempt;
        $payment = $log->payment;
        $refund = $log->refund;
        $order = $attempt?->order ?? $payment?->order ?? $refund?->order;

        return [
            'provider' => $log->provider,
            'event_id' => $log->provider_event_id,
            'event_type' => $log->event_type,
            'processing_status' => $this->webhookRules->webhookLogProcessingStatusIgnoredDuplicate(),
            'signature_verified' => (bool) $log->signature_verified,
            'error_message' => null,
            'retryable' => false,
            'order_public_id' => $order?->public_id,
            'payment_attempt_public_id' => $attempt?->public_id,
            'payment_recorded' => $payment !== null,
            'refund_recorded' => $refund !== null,
            'payment_status' => $order !== null ? $this->paymentStatusForOrder($order) : null,
            'payment_attempt_status' => $attempt?->status,
            'gateway_order_id' => $attempt?->gateway_order_id,
            'gateway_payment_id' => $attempt?->gateway_payment_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function failureResponseFromLog(PaymentWebhookLog $log, int $statusCode): array
    {
        return [
            'http_status' => $statusCode,
            'provider' => $log->provider,
            'event_id' => $log->provider_event_id,
            'event_type' => $log->event_type,
            'processing_status' => $log->processing_status,
            'signature_verified' => (bool) $log->signature_verified,
            'error_message' => $log->error_message,
            'retryable' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function needsReviewResult(PaymentWebhookLog $log, string $reason): array
    {
        return [
            'provider' => $log->provider,
            'event_id' => $log->provider_event_id,
            'event_type' => $log->event_type,
            'processing_status' => $log->processing_status,
            'signature_verified' => (bool) $log->signature_verified,
            'error_message' => $log->error_message,
            'review_reason' => $reason,
            'retryable' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function resultFromLog(PaymentWebhookLog $log, array $result): array
    {
        return array_merge([
            'provider' => $log->provider,
            'event_id' => $log->provider_event_id,
            'event_type' => $log->event_type,
            'processing_status' => $log->processing_status,
            'signature_verified' => (bool) $log->signature_verified,
            'error_message' => $log->error_message,
            'retryable' => false,
        ], $result);
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     */
    private function paymentStatusForOrder(Order $order): string
    {
        $paidTotal = (int) Payment::query()
            ->where('order_id', $order->id)
            ->where('status', 'succeeded')
            ->sum('amount_minor');

        $refundTotal = (int) Refund::query()
            ->where('order_id', $order->id)
            ->where('status', $this->refundRules->succeededStatus())
            ->sum('amount_minor');

        return $this->stateRules->calculate($order->total_amount_minor, $paidTotal, $refundTotal, $order->getExpectedAdvanceAmount());
    }

    /**
     * @param  array<string, mixed>  $normalizedWebhook
     * @return array<string, mixed>
     */
    private function safeMetadata(array $normalizedWebhook): array
    {
        return array_filter([
            'provider' => $normalizedWebhook['provider'] ?? null,
            'provider_event_id' => $normalizedWebhook['provider_event_id'] ?? null,
            'event_type' => $normalizedWebhook['event_type'] ?? null,
            'provider_order_id' => $normalizedWebhook['provider_order_id'] ?? null,
            'provider_payment_id' => $normalizedWebhook['provider_payment_id'] ?? null,
            'provider_refund_id' => $normalizedWebhook['provider_refund_id'] ?? null,
            'payload_summary' => $normalizedWebhook['payload_summary'] ?? null,
        ], static fn ($value): bool => $value !== null);
    }

    /**
     * @param  array<string, string|array<int, string>>  $headers
     * @return array<string, string>
     */
    private function flattenHeaders(array $headers): array
    {
        return array_change_key_case(array_map(static function ($value): string {
            return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        }, $headers), CASE_LOWER);
    }
}
