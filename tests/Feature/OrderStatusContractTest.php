<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Support\Orders\OrderStatusCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStatusContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_order_statuses_expose_the_approved_operational_values(): void
    {
        $this->assertSame([
            'pending_payment',
            'confirmed',
            'in_production',
            'ready_to_ship',
            'shipped',
            'delivered',
            'cancelled',
            'refunded',
        ], OrderStatus::values());
    }

    public function test_shared_order_statuses_provide_labels_and_terminal_flags(): void
    {
        $this->assertSame('Pending Payment', OrderStatus::PendingPayment->label());
        $this->assertSame('Confirmed', OrderStatus::Confirmed->label());
        $this->assertSame('In Production', OrderStatus::InProduction->label());
        $this->assertTrue(OrderStatus::Delivered->isTerminal());
        $this->assertTrue(OrderStatus::Cancelled->isTerminal());
        $this->assertTrue(OrderStatus::Refunded->isTerminal());
        $this->assertFalse(OrderStatus::Confirmed->isTerminal());
        $this->assertSame(
            [
                'value' => 'pending_payment',
                'label' => 'Pending Payment',
                'is_terminal' => false,
                'is_customer_visible' => true,
            ],
            OrderStatus::PendingPayment->toArray(),
        );
    }

    public function test_order_status_catalog_keeps_operational_and_tracking_usage_rules_separate(): void
    {
        $catalog = app(OrderStatusCatalog::class);

        $this->assertSame([
            'pending_payment',
            'confirmed',
            'in_production',
            'ready_to_ship',
            'shipped',
            'delivered',
            'cancelled',
            'refunded',
        ], $catalog->keys());

        $confirmed = $catalog->definition(OrderStatus::Confirmed);
        $shipped = $catalog->definition('shipped');

        $this->assertSame('Confirmed', $confirmed['label']);
        $this->assertSame('Staff or business rules can move an accepted order into confirmed.', $confirmed['usage']);
        $this->assertFalse($confirmed['terminal']);
        $this->assertTrue($confirmed['customer_visible']);
        $this->assertSame(['A5.1.2', 'C4.1'], $confirmed['references']);

        $this->assertSame('Shipped', $shipped['label']);
        $this->assertSame('Order has been handed to the courier or delivery process.', $shipped['usage']);
        $this->assertFalse($shipped['terminal']);
        $this->assertTrue($shipped['customer_visible']);
        $this->assertSame(['C4.1', 'C4.2', 'B4.2'], $shipped['references']);
    }
}
