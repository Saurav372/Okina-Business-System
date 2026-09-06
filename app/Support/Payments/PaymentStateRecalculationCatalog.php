<?php

namespace App\Support\Payments;

final class PaymentStateRecalculationCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'payment_state_recalculation',
            'label' => 'Payment-State Recalculation',
            'usage' => 'Payment status is derived from successful payments and refunds, with refund states taking priority once refunds exist.',
            'rules' => [
                'source_of_truth' => 'payments and refunds',
                'unpaid_status' => 'unpaid',
                'partially_paid_status' => 'partially_paid',
                'paid_status' => 'paid',
                'partially_refunded_status' => 'partially_refunded',
                'refunded_status' => 'refunded',
                'refund_state_takes_priority_over_paid_state' => true,
                'net_paid_formula' => 'net_paid = max(0, paid_total - refund_total)',
            ],
            'safety_note' => 'Payment status is derived only from payment and refund records; order status and cancellation behavior remain separate concerns.',
            'references' => ['A5.1.3', 'A5.1.4', 'A5.2.3', 'A5.2.4', 'C5.2', 'C1.1', 'B4.2'],
        ];
    }
}
