<?php

namespace App\Support\Orders;

final class SalesOrderCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'sales_order_admin',
            'label' => 'Sales Order Admin Workflow',
            'usage' => 'Admin creates official sales orders manually or from approved quotations, then records advance and final payments later.',
            'rules' => [
                'manual_creation_allowed' => true,
                'approved_quotation_conversion_allowed' => true,
                'advance_payments_supported' => true,
                'final_balance_payments_supported' => true,
                'gateway_independent' => true,
                'starts_confirmed' => true,
            ],
            'references' => ['A5.1.1', 'A5.1.2', 'A5.1.3', 'A5.1.4', 'A5.1.7', 'C1.2.6', 'C1.2.8', 'C1.3.7', 'C1.3.8'],
        ];
    }
}
