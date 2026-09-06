<?php

namespace App\Support\Orders;

final class WebsiteOrderCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'website_order_checkout',
            'label' => 'Website Order Checkout',
            'usage' => 'Checkout creates a pending website order before payment starts.',
            'rules' => [
                'pending_order_before_payment' => true,
                'duplicate_submission_protection' => true,
                'payment_attempt_after_order_creation' => true,
                'gateway_independent' => true,
            ],
            'references' => ['A5.1.1', 'A5.1.2', 'A5.1.3', 'A5.1.4', 'B3.1.6', 'B3.1.8', 'B3.1.9'],
        ];
    }
}
