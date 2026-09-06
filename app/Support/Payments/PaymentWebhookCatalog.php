<?php

namespace App\Support\Payments;

final class PaymentWebhookCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'payment_webhook_contract',
            'label' => 'Payment Webhook Contract',
            'usage' => 'Webhook events are authenticated, deduplicated, and reduced to safe summary fields before payment or refund processing uses them.',
            'rules' => [
                'source_of_truth' => 'payment_webhook_logs',
                'duplicate_handling' => 'ignore_duplicate',
                'requires_signature_verification' => true,
                'requires_provider_event_id' => true,
                'keeps_raw_payloads_out_of_shared_records' => true,
                'processing_statuses' => [
                    'received' => 'received',
                    'processed' => 'processed',
                    'ignored_duplicate' => 'ignored_duplicate',
                ],
                'headers_isolated' => true,
                'raw_payload_isolated' => true,
            ],
            'safety_note' => 'Webhook handling stays at the replay-safety layer until a later task turns the safe summary into payment or refund updates.',
            'references' => ['A4.5', 'A5.3.1', 'A5.3.2', 'A5.3.4', 'B3.3.5', 'B3.3.6'],
        ];
    }
}
