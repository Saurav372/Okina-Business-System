<?php

namespace App\Services;

use App\Events\AuditEvent;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Create a new refund request for a succeeded payment.
     */
    public function requestRefund(
        Payment $payment,
        int $amountMinor,
        string $reasonCode,
        ?string $reasonNote = null,
        ?User $actor = null
    ): Refund {
        if ($payment->status !== Payment::STATUS_SUCCEEDED) {
            throw ValidationException::withMessages([
                'payment_id' => "Cannot request refund for payment with status [{$payment->status}]. Only succeeded payments can be refunded.",
            ]);
        }

        if ($amountMinor <= 0) {
            throw ValidationException::withMessages([
                'amount_minor' => 'Refund amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($payment, $amountMinor, $reasonCode, $reasonNote, $actor) {
            // Lock payment to calculate cumulative refunded amount accurately
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::query()->where('id', $payment->id)->lockForUpdate()->firstOrFail();

            $existingRefundedSum = (int) Refund::query()
                ->where('payment_id', $lockedPayment->id)
                ->whereIn('status', [Refund::STATUS_REQUESTED, Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING, Refund::STATUS_SUCCEEDED])
                ->sum('amount_minor');

            $maxRefundable = $lockedPayment->amount_minor - $existingRefundedSum;

            if ($amountMinor > $maxRefundable) {
                $maxFormatted = number_format($maxRefundable / 100, 2);
                throw ValidationException::withMessages([
                    'amount_minor' => "Refund amount exceeds remaining refundable balance on payment. Maximum remaining refundable: ₹{$maxFormatted}.",
                ]);
            }

            $refundType = ($amountMinor === $lockedPayment->amount_minor && $existingRefundedSum === 0)
                ? Refund::TYPE_FULL
                : Refund::TYPE_PARTIAL;

            $actor = $actor ?: Auth::user();

            $refund = Refund::create([
                'order_id' => $lockedPayment->order_id,
                'payment_id' => $lockedPayment->id,
                'provider' => $lockedPayment->provider ?? 'manual',
                'refund_type' => $refundType,
                'status' => Refund::STATUS_REQUESTED,
                'amount_minor' => $amountMinor,
                'currency' => $lockedPayment->currency ?? 'INR',
                'reason_code' => $reasonCode,
                'reason_note' => $reasonNote,
                'requested_by_user_id' => $actor?->id,
                'requested_at' => now(),
                'metadata' => [
                    'attempt_count' => 0,
                ],
            ]);

            DB::afterCommit(function () use ($refund, $actor) {
                event(new AuditEvent('refund.requested', $actor, [
                    'refund_id' => $refund->id,
                    'payment_id' => $refund->payment_id,
                    'order_id' => $refund->order_id,
                    'amount_minor' => $refund->amount_minor,
                    'reason_code' => $refund->reason_code,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $refund;
        });
    }

    /**
     * Approve a pending refund request (REQUESTED -> APPROVED).
     */
    public function approveRefund(Refund $refund, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($refund, $actor) {
            /** @var Refund $locked */
            $locked = Refund::query()->where('id', $refund->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Refund::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'status' => "Cannot approve refund with status [{$locked->status}]. Refund must be in REQUESTED status.",
                ]);
            }

            $actor = $actor ?: Auth::user();

            $locked->status = Refund::STATUS_APPROVED;
            $locked->approved_by_user_id = $actor?->id;
            $locked->approved_at = now();
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('refund.approved', $actor, [
                    'refund_id' => $locked->id,
                    'payment_id' => $locked->payment_id,
                    'approved_at' => $locked->approved_at?->toIso8601String(),
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }

    /**
     * Process an approved refund (APPROVED -> PROCESSING -> SUCCEEDED).
     */
    public function processRefund(Refund $refund, ?string $providerRefundId = null, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($refund, $providerRefundId, $actor) {
            /** @var Refund $locked */
            $locked = Refund::query()->where('id', $refund->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Refund::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'status' => "Cannot process refund with status [{$locked->status}]. Refund must be in APPROVED status.",
                ]);
            }

            return $this->executePayout($locked, $providerRefundId, $actor);
        });
    }

    /**
     * Retry a failed refund payout (FAILED -> PROCESSING -> SUCCEEDED).
     */
    public function retryRefund(Refund $refund, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($refund, $actor) {
            /** @var Refund $locked */
            $locked = Refund::query()->where('id', $refund->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Refund::STATUS_FAILED) {
                throw ValidationException::withMessages([
                    'status' => "Cannot retry refund with status [{$locked->status}]. Refund must be in FAILED status.",
                ]);
            }

            $metadata = $locked->metadata ?? [];
            $attemptCount = (int) ($metadata['attempt_count'] ?? 0);

            if ($attemptCount >= self::MAX_RETRY_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'attempt_count' => 'Refund has reached maximum retry attempt limit of '.self::MAX_RETRY_ATTEMPTS.' attempts. Manual intervention required.',
                ]);
            }

            $actor = $actor ?: Auth::user();

            $locked->status = Refund::STATUS_PROCESSING;
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('refund.retry_processing', $actor, [
                    'refund_id' => $locked->id,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $this->executePayout($locked, null, $actor);
        });
    }

    /**
     * Execute payout engine (shared payout execution logic).
     */
    protected function executePayout(Refund $locked, ?string $providerRefundId = null, ?User $actor = null): Refund
    {
        $actor = $actor ?: Auth::user();

        if ($providerRefundId !== null && trim($providerRefundId) !== '') {
            $duplicate = Refund::query()
                ->where('provider_refund_id', trim($providerRefundId))
                ->where('id', '!=', $locked->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'provider_refund_id' => "Provider refund reference [{$providerRefundId}] is already associated with another refund record.",
                ]);
            }

            $locked->provider_refund_id = trim($providerRefundId);
        }

        $metadata = $locked->metadata ?? [];
        $attemptCount = ((int) ($metadata['attempt_count'] ?? 0)) + 1;
        $metadata['attempt_count'] = $attemptCount;
        $metadata['last_attempt_at'] = now()->toIso8601String();
        $locked->metadata = $metadata;

        $locked->status = Refund::STATUS_SUCCEEDED;
        $locked->processed_by_user_id = $actor?->id;
        $locked->processed_at = now();
        $locked->save();

        DB::afterCommit(function () use ($locked, $actor) {
            event(new AuditEvent('refund.succeeded', $actor, [
                'refund_id' => $locked->id,
                'payment_id' => $locked->payment_id,
                'amount_minor' => $locked->amount_minor,
                'provider_refund_id' => $locked->provider_refund_id,
                'processed_at' => $locked->processed_at?->toIso8601String(),
                'actor_id' => $actor?->id,
            ]));
        });

        return $locked;
    }

    /**
     * Mark a refund processing as FAILED with gateway error metadata.
     */
    public function markFailed(
        Refund $refund,
        string $errorCode,
        string $errorMessage,
        ?array $response = null,
        ?User $actor = null
    ): Refund {
        return DB::transaction(function () use ($refund, $errorCode, $errorMessage, $response, $actor) {
            /** @var Refund $locked */
            $locked = Refund::query()->where('id', $refund->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Refund::STATUS_SUCCEEDED) {
                throw ValidationException::withMessages([
                    'status' => 'Succeeded refund is immutable and cannot be marked as failed.',
                ]);
            }

            $actor = $actor ?: Auth::user();
            $metadata = $locked->metadata ?? [];

            $metadata['provider_error_code'] = $errorCode;
            $metadata['provider_error_message'] = $errorMessage;
            $metadata['provider_response'] = $response;
            $metadata['last_attempt_at'] = now()->toIso8601String();

            $locked->status = Refund::STATUS_FAILED;
            $locked->metadata = $metadata;
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('refund.failed', $actor, [
                    'refund_id' => $locked->id,
                    'error_message' => $locked->metadata['provider_error_message'] ?? null,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }

    /**
     * Cancel a pending or approved refund (REQUESTED / APPROVED -> CANCELLED).
     */
    public function cancelRefund(Refund $refund, ?User $actor = null): Refund
    {
        return DB::transaction(function () use ($refund, $actor) {
            /** @var Refund $locked */
            $locked = Refund::query()->where('id', $refund->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Refund::STATUS_SUCCEEDED) {
                throw ValidationException::withMessages([
                    'status' => 'Succeeded refund is immutable and cannot be cancelled.',
                ]);
            }

            if ($locked->status === Refund::STATUS_CANCELLED) {
                return $locked;
            }

            $actor = $actor ?: Auth::user();

            $locked->status = Refund::STATUS_CANCELLED;
            $locked->save();

            DB::afterCommit(function () use ($locked, $actor) {
                event(new AuditEvent('refund.cancelled', $actor, [
                    'refund_id' => $locked->id,
                    'actor_id' => $actor?->id,
                ]));
            });

            return $locked;
        });
    }
}
