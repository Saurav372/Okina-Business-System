<?php

namespace App\Support\Payments;

final class PartialRefundCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'partial_refund_rules',
            'label' => 'Partial Refund Rules',
            'usage' => 'Partial refunds add to the successful refund total and move payment status to partially refunded or refunded based on remaining net paid amount.',
            'rules' => [
                'refund_type' => 'partial',
                'successful_refund_status' => 'succeeded',
                'refund_total_formula' => 'refund_total = current_refund_total + partial_refund_amount',
                'payment_status_formula' => 'refund_total = 0 -> paid; refund_total > 0 and net_paid > 0 -> partially_refunded; refund_total > 0 and net_paid = 0 -> refunded',
                'preserves_original_payments' => true,
                'separate_from_cancellation_effects' => true,
            ],
            'safety_note' => 'Partial refund rules update financial totals only; cancellation handling and full refund handling remain separate tasks.',
            'references' => ['A5.1.3', 'A5.1.4', 'A5.2.2', 'A5.2.3', 'C5.2', 'B3.3.6'],
        ];
    }
}
