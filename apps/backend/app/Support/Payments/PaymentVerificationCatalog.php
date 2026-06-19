<?php

namespace App\Support\Payments;

final class PaymentVerificationCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'payment_verification_contract',
            'label' => 'Payment Verification Contract',
            'usage' => 'A verified provider response is normalized into a shared payment record shape with succeeded, pending_verification, and failed states kept separate from refund records.',
            'rules' => [
                'source_of_truth' => 'payments',
                'verified_payment_status' => 'succeeded',
                'pending_verification_status' => 'pending_verification',
                'failed_verification_status' => 'failed',
                'keeps_payments_separate_from_refunds' => true,
                'updates_payment_records_safely' => true,
                'metadata_isolated' => true,
            ],
            'safety_note' => 'Verification only updates payment records from a trusted payment source; it never writes refund rows or exposes raw provider payloads.',
            'references' => ['A4.5', 'A5.1.3', 'A5.1.4', 'A5.3.1', 'A5.3.2', 'B3.3.6', 'C5.2.5'],
        ];
    }
}
