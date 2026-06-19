<?php

namespace App\Support\Orders;

final class CancellationEffectCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'order_cancellation_effects',
            'label' => 'Order Cancellation Effects',
            'usage' => 'Cancellation marks the order cancelled, keeps payment facts separate, avoids stock reversal, and keeps customer-safe visibility.',
            'rules' => [
                'cancelled_order_status' => 'cancelled',
                'payment_facts_changed' => false,
                'refund_execution_triggered' => false,
                'stock_changed_on_cancellation' => false,
                'customer_visible' => true,
                'sensitive_notes_hidden_from_customers' => true,
            ],
            'safety_note' => 'Cancellation rules define the order state only; refund execution and stock reversal stay in later tasks.',
            'references' => ['A5.1.2', 'A5.2.1', 'A5.2.2', 'B4.2', 'C4.1', 'C2.1'],
        ];
    }
}
