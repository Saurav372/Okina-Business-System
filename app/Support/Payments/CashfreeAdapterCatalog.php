<?php

namespace App\Support\Payments;

final class CashfreeAdapterCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'cashfree_adapter_contract',
            'label' => 'Cashfree Adapter Contract',
            'usage' => 'Cashfree maps provider-specific request, response, webhook, and refund payloads into shared payment gateway shapes while keeping provider-only data isolated.',
            'rules' => [
                'provider' => 'cashfree',
                'supports_sandbox_mode' => true,
                'keeps_provider_payloads_isolated' => true,
                'payment_request_shape' => ['provider', 'order_public_id', 'amount_minor', 'currency', 'customer_public_id', 'idempotency_key', 'gateway_mode', 'return_url', 'notify_url'],
                'response_shape' => ['provider', 'gateway_order_id', 'gateway_payment_id', 'checkout_url', 'status', 'amount_minor', 'currency'],
                'webhook_shape' => ['provider', 'event_id', 'event_type', 'gateway_order_id', 'gateway_payment_id', 'status', 'amount_minor', 'currency', 'payload_isolated'],
                'refund_shape' => ['provider', 'provider_refund_id', 'gateway_payment_id', 'status', 'amount_minor', 'currency'],
            ],
            'safety_note' => 'Cashfree-specific payloads stay isolated from shared business logic; adapter mapping is provider-facing only and does not start gateway operations by itself.',
            'references' => ['A4.2', 'A4.5', 'A5.3.1', 'A5.3.3', 'A5.3.5', 'B3.1', 'B3.3'],
        ];
    }
}
