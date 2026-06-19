<?php

namespace App\Support\Payments;

final class RefundInterfaceCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'refund_interface_contract',
            'label' => 'Refund Interface Contract',
            'usage' => 'Refund requests, approvals, and processed outcomes use the shared refunds table and preserve original payments.',
            'rules' => [
                'source_of_truth' => 'refunds',
                'partial_refund_type' => 'partial',
                'full_refund_type' => 'full',
                'requested_status' => 'requested',
                'approved_status' => 'approved',
                'processing_status' => 'processing',
                'succeeded_status' => 'succeeded',
                'failed_status' => 'failed',
                'cancelled_status' => 'cancelled',
                'uses_shared_refunds_table' => true,
                'keeps_original_payments' => true,
            ],
            'safety_note' => 'Refund handling stays focused on shared refund records and state transitions; payment calculation and audit storage remain separate concerns.',
            'references' => ['A5.2.3', 'A5.2.4', 'A5.2.5', 'A5.2.6', 'C5.2.1', 'C5.2.5'],
        ];
    }
}
