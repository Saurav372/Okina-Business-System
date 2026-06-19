<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Support\Orders\WebsiteOrderCatalog;
use App\Support\Orders\WebsiteOrderRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteOrderRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_orders_use_the_approved_shared_checkout_shape(): void
    {
        $rules = app(WebsiteOrderRules::class);

        $this->assertSame(OrderType::WebsiteOrder->value(), $rules->orderType());
        $this->assertSame('website', $rules->orderSource());
        $this->assertSame(OrderStatus::PendingPayment->value(), $rules->initialStatus());
        $this->assertTrue($rules->createsPendingOrderBeforePayment());
        $this->assertTrue($rules->usesIdempotencyKey());
        $this->assertTrue($rules->requiresPaymentAttemptAfterOrderCreation());
    }

    public function test_website_order_rules_are_serializable_for_later_checkout_services(): void
    {
        $rules = app(WebsiteOrderRules::class);

        $this->assertSame(
            [
                'order_type' => 'website_order',
                'order_source' => 'website',
                'initial_status' => 'pending_payment',
                'creates_pending_order_before_payment' => true,
                'uses_idempotency_key' => true,
                'requires_payment_attempt_after_order_creation' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_website_order_catalog_documents_checkout_and_payment_attempt_rules(): void
    {
        $catalog = app(WebsiteOrderCatalog::class);

        $this->assertSame(
            [
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
            ],
            $catalog->definition(),
        );
    }
}
