<?php

namespace App\Support\Payments;

final class PaymentGatewayCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'payment_gateway_contract',
            'label' => 'Payment Gateway Contract',
            'usage' => 'Checkout and admin depend on an interface that can be implemented by Cashfree or any future provider without hardcoding provider logic into the core flow.',
            'rules' => [
                'provider' => 'gateway_agnostic',
                'gateway_independent_checkout_supported' => true,
                'supports_online_payment_initiation' => true,
                'supports_webhook_verification' => true,
                'supports_refunds' => true,
                'requires_idempotency_for_payment_attempt' => true,
                'requires_order_before_payment_initiation' => true,
            ],
            'safety_note' => 'The contract describes provider-neutral behavior only; Cashfree adapter details, webhook parsing, and provider-specific exceptions belong to later subtasks.',
            'references' => ['A4.2', 'A4.5', 'A5.1.1', 'A5.1.3', 'A5.3.2', 'B3.1', 'B3.3'],
        ];
    }
}
