<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Support\Orders\QuotationToOrderConversionCatalog;
use App\Support\Orders\QuotationToOrderConversionRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationToOrderConversionRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_quotations_convert_into_sales_orders_with_the_expected_shape(): void
    {
        $rules = app(QuotationToOrderConversionRules::class);

        $this->assertSame('approved', $rules->sourceStatus());
        $this->assertSame(OrderType::SalesOrder->value(), $rules->convertedOrderType());
        $this->assertSame('quotation', $rules->convertedOrderSource());
        $this->assertSame(OrderStatus::Confirmed->value(), $rules->convertedOrderStatus());
        $this->assertTrue($rules->requiresApprovedQuotation());
        $this->assertTrue($rules->convertsOnlyOnce());
        $this->assertTrue($rules->usesConversionIdempotencyKey());
        $this->assertTrue($rules->preservesQuotationHistory());
    }

    public function test_conversion_rules_are_serializable_for_later_quote_to_order_services(): void
    {
        $rules = app(QuotationToOrderConversionRules::class);

        $this->assertSame(
            [
                'source_status' => 'approved',
                'converted_order_type' => 'sales_order',
                'converted_order_source' => 'quotation',
                'converted_order_status' => 'confirmed',
                'requires_approved_quotation' => true,
                'converts_only_once' => true,
                'uses_conversion_idempotency_key' => true,
                'preserves_quotation_history' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_conversion_catalog_documents_one_time_conversion_rules(): void
    {
        $catalog = app(QuotationToOrderConversionCatalog::class);

        $this->assertSame(
            [
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
            ],
            $catalog->definition(),
        );
    }
}
