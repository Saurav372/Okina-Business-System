<?php

namespace App\Support\Payments;

final class FullRefundCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'full_refund_rules',
            'label' => 'Full Refund Rules',
            'usage' => 'A full refund restores the full successful paid amount, marks the order refunded, and keeps the original payment history intact.',
            'rules' => [
                'refund_type' => 'full',
                'successful_refund_status' => 'succeeded',
                'order_status_after_full_refund' => 'refunded',
                'payment_status_after_full_refund' => 'refunded',
                'refund_total_formula' => 'refund_total = max(current_refund_total, paid_total)',
                'keeps_original_payments' => true,
                'keeps_customer_visibility_safe' => true,
            ],
            'safety_note' => 'Full refund rules set the financial outcome and order marker only; payment-state recalculation, refund approval flow, and audit storage remain separate tasks.',
            'references' => ['A5.1.3', 'A5.1.4', 'A5.2.3', 'A5.2.5', 'C5.2', 'C1.1'],
        ];
    }
}
