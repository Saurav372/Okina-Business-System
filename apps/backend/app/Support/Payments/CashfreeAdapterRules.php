<?php

namespace App\Support\Payments;

use App\Contracts\CashfreeAdapterContract;

readonly class CashfreeAdapterRules implements CashfreeAdapterContract
{
    public function provider(): string
    {
        return 'cashfree';
    }

    public function supportsSandboxMode(): bool
    {
        return true;
    }

    public function keepsProviderPayloadsIsolated(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed>  $sharedPayload
     * @return array<string, mixed>
     */
    public function buildPaymentRequest(array $sharedPayload): array
    {
        return [
            'provider' => $this->provider(),
            'order_public_id' => $sharedPayload['order_public_id'] ?? null,
            'amount_minor' => $sharedPayload['amount_minor'] ?? null,
            'currency' => $sharedPayload['currency'] ?? 'INR',
            'customer_public_id' => $sharedPayload['customer_public_id'] ?? null,
            'idempotency_key' => $sharedPayload['idempotency_key'] ?? null,
            'gateway_mode' => $sharedPayload['gateway_mode'] ?? 'sandbox',
            'return_url' => $sharedPayload['return_url'] ?? null,
            'notify_url' => $sharedPayload['notify_url'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $cashfreePayload
     * @return array<string, mixed>
     */
    public function normalizePaymentResponse(array $cashfreePayload): array
    {
        return [
            'provider' => $this->provider(),
            'gateway_order_id' => $cashfreePayload['order_id'] ?? null,
            'gateway_payment_id' => $cashfreePayload['payment_id'] ?? null,
            'checkout_url' => $cashfreePayload['checkout_url'] ?? null,
            'status' => $cashfreePayload['status'] ?? null,
            'amount_minor' => $cashfreePayload['amount_minor'] ?? null,
            'currency' => $cashfreePayload['currency'] ?? 'INR',
        ];
    }

    /**
     * @param  array<string, mixed>  $cashfreePayload
     * @return array<string, mixed>
     */
    public function normalizeWebhookPayload(array $cashfreePayload): array
    {
        return [
            'provider' => $this->provider(),
            'event_id' => $cashfreePayload['event_id'] ?? null,
            'event_type' => $cashfreePayload['event_type'] ?? null,
            'gateway_order_id' => $cashfreePayload['order_id'] ?? null,
            'gateway_payment_id' => $cashfreePayload['payment_id'] ?? null,
            'status' => $cashfreePayload['status'] ?? null,
            'amount_minor' => $cashfreePayload['amount_minor'] ?? null,
            'currency' => $cashfreePayload['currency'] ?? 'INR',
            'payload_isolated' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $cashfreePayload
     * @return array<string, mixed>
     */
    public function normalizeRefundResponse(array $cashfreePayload): array
    {
        return [
            'provider' => $this->provider(),
            'provider_refund_id' => $cashfreePayload['refund_id'] ?? null,
            'gateway_payment_id' => $cashfreePayload['payment_id'] ?? null,
            'status' => $cashfreePayload['status'] ?? null,
            'amount_minor' => $cashfreePayload['amount_minor'] ?? null,
            'currency' => $cashfreePayload['currency'] ?? 'INR',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider(),
            'supports_sandbox_mode' => $this->supportsSandboxMode(),
            'keeps_provider_payloads_isolated' => $this->keepsProviderPayloadsIsolated(),
            'payment_request_shape' => [
                'provider',
                'order_public_id',
                'amount_minor',
                'currency',
                'customer_public_id',
                'idempotency_key',
                'gateway_mode',
                'return_url',
                'notify_url',
            ],
            'response_shape' => [
                'provider',
                'gateway_order_id',
                'gateway_payment_id',
                'checkout_url',
                'status',
                'amount_minor',
                'currency',
            ],
            'webhook_shape' => [
                'provider',
                'event_id',
                'event_type',
                'gateway_order_id',
                'gateway_payment_id',
                'status',
                'amount_minor',
                'currency',
                'payload_isolated',
            ],
            'refund_shape' => [
                'provider',
                'provider_refund_id',
                'gateway_payment_id',
                'status',
                'amount_minor',
                'currency',
            ],
        ];
    }
}
