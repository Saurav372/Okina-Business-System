<?php

namespace App\Support\Payments;

final class ManualPaymentCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'manual_payment_support',
            'label' => 'Manual Payment Support',
            'usage' => 'Staff can record manual payments using the same shared payment records, with no payment attempt required and verification handled through user attribution.',
            'rules' => [
                'provider' => 'manual',
                'payment_type' => 'manual_adjustment',
                'supports_manual_payments' => true,
                'requires_payment_attempt' => false,
                'requires_verified_by_user' => true,
                'keeps_manual_payments_separate_from_gateway_logic' => true,
                'notes_isolated' => true,
            ],
            'safety_note' => 'Manual payment entries update balances through the shared payments table and never impersonate gateway-originated payments.',
            'references' => ['A4.2', 'A5.1.3', 'A5.1.4', 'A5.3.4', 'C5.2.2', 'C5.2.6'],
        ];
    }
}
