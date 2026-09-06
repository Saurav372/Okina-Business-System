<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Support\Orders\SalesOrderCatalog;
use App\Support\Orders\SalesOrderRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_orders_use_the_approved_admin_created_shape(): void
    {
        $rules = app(SalesOrderRules::class);

        $this->assertSame(OrderType::SalesOrder->value(), $rules->orderType());
        $this->assertSame('admin', $rules->orderSource());
        $this->assertSame(OrderStatus::Confirmed->value(), $rules->initialStatus());
        $this->assertTrue($rules->mayBeCreatedManually());
        $this->assertTrue($rules->mayBeCreatedFromApprovedQuotation());
        $this->assertTrue($rules->supportsAdvancePayments());
        $this->assertTrue($rules->supportsFinalBalancePayments());
        $this->assertFalse($rules->requiresGatewayInitiation());
    }

    public function test_sales_order_rules_are_serializable_for_later_admin_services(): void
    {
        $rules = app(SalesOrderRules::class);

        $this->assertSame(
            [
                'order_type' => 'sales_order',
                'order_source' => 'admin',
                'initial_status' => 'confirmed',
                'may_be_created_manually' => true,
                'may_be_created_from_approved_quotation' => true,
                'supports_advance_payments' => true,
                'supports_final_balance_payments' => true,
                'requires_gateway_initiation' => false,
            ],
            $rules->toArray(),
        );
    }

    public function test_sales_order_catalog_documents_manual_and_quotation_based_workflows(): void
    {
        $catalog = app(SalesOrderCatalog::class);

        $this->assertSame(
            [
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
            ],
            $catalog->definition(),
        );
    }
}
