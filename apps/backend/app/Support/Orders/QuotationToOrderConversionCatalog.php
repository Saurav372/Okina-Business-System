<?php

namespace App\Support\Orders;

final class QuotationToOrderConversionCatalog
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'quotation_to_sales_order',
            'label' => 'Quotation to Sales Order Conversion',
            'usage' => 'Approved quotations convert to sales orders only once.',
            'rules' => [
                'requires_approved_quotation' => true,
                'converts_only_once' => true,
                'conversion_idempotency_key_required' => true,
                'converted_order_type' => 'sales_order',
                'converted_order_source' => 'quotation',
                'converted_order_status' => 'confirmed',
            ],
            'references' => ['A5.1.1', 'A5.1.2', 'A5.1.6', 'A5.1.7', 'C1.3.7', 'C1.3.8'],
        ];
    }
}
