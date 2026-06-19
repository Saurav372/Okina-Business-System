<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Support\Orders\CancellationEligibilityCatalog;
use App\Support\Orders\CancellationEligibilityRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationEligibilityRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_eligibility_exposes_the_approved_order_types_and_statuses(): void
    {
        $rules = app(CancellationEligibilityRules::class);

        $this->assertSame(['website_order', 'sales_order'], $rules->orderTypes());
        $this->assertSame(['pending_payment', 'confirmed'], $rules->cancellableStatusesForWebsiteOrders());
        $this->assertSame(['confirmed'], $rules->cancellableStatusesForSalesOrders());
        $this->assertTrue($rules->cancellationIsSeparateFromRefunds());
    }

    public function test_cancellation_eligibility_is_based_on_order_type_and_status_only(): void
    {
        $rules = app(CancellationEligibilityRules::class);

        $this->assertTrue($rules->canCancel(OrderType::WebsiteOrder->value(), OrderStatus::PendingPayment->value()));
        $this->assertTrue($rules->canCancel('website_order', 'confirmed'));
        $this->assertTrue($rules->canCancel(OrderType::SalesOrder->value(), OrderStatus::Confirmed->value()));

        $this->assertFalse($rules->canCancel('website_order', 'in_production'));
        $this->assertFalse($rules->canCancel('website_order', 'delivered'));
        $this->assertFalse($rules->canCancel('sales_order', 'pending_payment'));
        $this->assertFalse($rules->canCancel('sales_order', 'ready_to_ship'));
        $this->assertFalse($rules->canCancel('unknown', 'confirmed'));
        $this->assertFalse($rules->canCancel(OrderType::SalesOrder->value(), 'unknown'));
    }

    public function test_cancellation_eligibility_catalog_documents_safety_guidance_for_later_refunds(): void
    {
        $catalog = app(CancellationEligibilityCatalog::class);

        $this->assertSame(
            [
                'key' => 'website_order',
                'label' => 'Website Order',
                'usage' => 'Website orders can be cancelled before production starts, while pending payment or confirmed.',
                'cancellable_statuses' => ['pending_payment', 'confirmed'],
                'safety_note' => 'Cancellation eligibility stays separate from payment refund execution.',
                'references' => ['A5.1.2', 'A5.1.4', 'A5.1.5', 'A5.2.1', 'B3.1.6'],
            ],
            $catalog->definition(OrderType::WebsiteOrder),
        );

        $this->assertSame(
            [
                'key' => 'sales_order',
                'label' => 'Sales Order',
                'usage' => 'Sales orders can be cancelled while still confirmed and before production starts.',
                'cancellable_statuses' => ['confirmed'],
                'safety_note' => 'Cancellation rules do not process refunds, payment reversals, or inventory reversal yet.',
                'references' => ['A5.1.2', 'A5.1.4', 'A5.1.6', 'A5.2.1', 'C1.2.6'],
            ],
            $catalog->definition('sales_order'),
        );
    }
}
