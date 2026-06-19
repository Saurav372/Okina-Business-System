<?php

namespace App\Support\Payments;

final class PaymentAttemptCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'payment_attempt_rules',
            'label' => 'Payment Attempt Rules',
            'usage' => 'A website checkout payment attempt is created once, identified by an idempotency key, and kept traceable through provider references and terminal statuses.',
            'rules' => [
                'attempt_type' => 'website_checkout',
                'provider' => 'cashfree',
                'duplicate_handling' => 'reuse_existing',
                'requires_idempotency_key' => true,
                'requires_traceable_gateway_reference' => true,
                'allowed_statuses' => ['created', 'initiated', 'requires_action', 'succeeded', 'failed', 'expired', 'cancelled'],
                'terminal_statuses' => ['succeeded', 'failed', 'expired', 'cancelled'],
                'metadata_isolated' => true,
            ],
            'safety_note' => 'Attempt payloads are normalized without exposing secrets, and duplicate checkout handling reuses the existing attempt instead of creating a second active one.',
            'references' => ['A4.5', 'A5.1.1', 'A5.3.1', 'A5.3.2', 'B3.1.6', 'B3.1.8', 'B3.3.5'],
        ];
    }
}
