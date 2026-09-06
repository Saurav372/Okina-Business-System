<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Support\Payments\CashfreeAdapterRules;
use App\Support\Payments\PaymentGatewayRules;

class WebsitePaymentInitiationService
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly PaymentGatewayRules $paymentGatewayRules,
        private readonly CashfreeAdapterRules $cashfreeAdapter,
    ) {}

    public function initiate(Order $order, PaymentAttempt $paymentAttempt): PaymentAttempt
    {
        if ($paymentAttempt->status !== 'created') {
            return $paymentAttempt;
        }

        if (! $this->onlinePaymentsEnabled() || $this->defaultGateway() !== 'cashfree') {
            return $paymentAttempt;
        }

        $providerResponse = $this->paymentGatewayRules->initiatePayment($this->cashfreeRequest($order, $paymentAttempt));
        $normalizedResponse = $this->cashfreeAdapter->normalizePaymentResponse($providerResponse);

        $paymentAttempt->forceFill([
            'status' => 'initiated',
            'gateway_order_id' => $normalizedResponse['gateway_order_id'] ?? null,
            'gateway_payment_id' => $normalizedResponse['gateway_payment_id'] ?? null,
            'gateway_reference' => $normalizedResponse['gateway_order_id'] ?? null,
            'checkout_url' => $normalizedResponse['checkout_url'] ?? null,
            'initiated_at' => now(),
        ])->save();

        return $paymentAttempt->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function cashfreeRequest(Order $order, PaymentAttempt $paymentAttempt): array
    {
        return $this->cashfreeAdapter->buildPaymentRequest([
            'provider' => $this->defaultGateway(),
            'order_public_id' => $order->public_id,
            'amount_minor' => $paymentAttempt->amount_minor,
            'currency' => $paymentAttempt->currency,
            'customer_public_id' => data_get($order->customer_snapshot, 'public_id'),
            'idempotency_key' => $paymentAttempt->idempotency_key,
            'gateway_mode' => $this->gatewayMode(),
            'return_url' => null,
            'notify_url' => null,
        ]);
    }

    private function defaultGateway(): string
    {
        return (string) $this->settingsService->get('payment', 'default_gateway', 'cashfree');
    }

    private function gatewayMode(): string
    {
        return (string) $this->settingsService->get('payment', 'gateway_mode', 'sandbox');
    }

    private function onlinePaymentsEnabled(): bool
    {
        return (bool) $this->settingsService->get('payment', 'online_payments_enabled', true);
    }
}
