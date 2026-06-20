<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\Admin\OrderIndexCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_resource_exposes_the_approved_website_order_index_definition(): void
    {
        $index = OrderResource::registration()['index'];

        $this->assertSame('website_orders_index', $index['key']);
        $this->assertSame(Order::class, $index['model']);
        $this->assertSame('websiteOrders', $index['base_scope']);
        $this->assertSame(['placed_at' => 'desc', 'public_id' => 'desc'], $index['default_sort']);
        $this->assertSame(['public_id', 'customer', 'status', 'total_amount_minor', 'currency', 'design_approved', 'placed_at'], $index['columns']);
        $this->assertContains('pending_payment', array_column($index['scopes'], 'key'));
        $this->assertContains('active_fulfillment', array_column($index['scopes'], 'key'));
        $this->assertContains('closed', array_column($index['scopes'], 'key'));
        $this->assertContains('status', array_column($index['filters'], 'key'));
        $this->assertContains('design_approved', array_column($index['filters'], 'key'));
        $this->assertContains('placed_from', array_column($index['filters'], 'key'));
        $this->assertContains('placed_to', array_column($index['filters'], 'key'));
    }

    public function test_order_index_query_returns_only_website_orders_and_sorts_newest_first(): void
    {
        $catalog = app(OrderIndexCatalog::class);

        $olderWebsiteOrder = Order::factory()->create([
            'public_id' => 'OD-OLDEST',
            'status' => OrderStatus::PendingPayment->value(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'placed_at' => now()->subDays(3),
            'design_approved' => false,
        ]);

        $newerWebsiteOrder = Order::factory()->create([
            'public_id' => 'OD-NEWEST',
            'status' => OrderStatus::Shipped->value(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'placed_at' => now()->subDay(),
            'design_approved' => true,
        ]);

        Order::factory()->create([
            'public_id' => 'OD-SALES',
            'status' => OrderStatus::Delivered->value(),
            'order_type' => 'sales_order',
            'order_source' => 'staff',
            'placed_at' => now()->subHours(6),
            'design_approved' => true,
        ]);

        $results = $catalog->query()->get();

        $this->assertCount(2, $results);
        $this->assertSame(['OD-NEWEST', 'OD-OLDEST'], $results->pluck('public_id')->all());
        $this->assertTrue($results->every(fn (Order $order): bool => $order->order_type === 'website_order'));
        $this->assertSame('website', $results->first()->order_source);
    }

    public function test_order_index_query_applies_scope_and_filter_rules_without_touching_payment_history(): void
    {
        $catalog = app(OrderIndexCatalog::class);

        Order::factory()->create([
            'public_id' => 'OD-PENDING',
            'status' => OrderStatus::PendingPayment->value(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'placed_at' => now()->subDays(4),
            'design_approved' => false,
        ]);

        Order::factory()->create([
            'public_id' => 'OD-CONFIRMED',
            'status' => OrderStatus::Confirmed->value(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'placed_at' => now()->subDays(2),
            'design_approved' => true,
        ]);

        Order::factory()->create([
            'public_id' => 'OD-OLD-CLOSED',
            'status' => OrderStatus::Delivered->value(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'placed_at' => now()->subDays(10),
            'design_approved' => true,
        ]);

        $pendingOnly = $catalog->query(['scope' => 'pending_payment'])->get();
        $approvedWindow = $catalog->query([
            'status' => OrderStatus::Confirmed->value(),
            'design_approved' => true,
            'placed_from' => now()->subDays(3)->toDateString(),
            'placed_to' => now()->subDay()->toDateString(),
        ])->get();

        $this->assertSame(['OD-PENDING'], $pendingOnly->pluck('public_id')->all());
        $this->assertSame(['OD-CONFIRMED'], $approvedWindow->pluck('public_id')->all());

        $summary = $catalog->summarize($approvedWindow->firstOrFail());

        $this->assertSame('OD-CONFIRMED', $summary['public_id']);
        $this->assertSame('confirmed', $summary['status']);
        $this->assertSame(true, $summary['design_approved']);
        $this->assertArrayHasKey('customer', $summary);
        $this->assertArrayNotHasKey('id', $summary);
        $this->assertArrayNotHasKey('payments', $summary);
        $this->assertArrayNotHasKey('refunds', $summary);
    }
}
