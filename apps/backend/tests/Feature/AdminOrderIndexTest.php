<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Admin\OrderIndexCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_resource_exposes_the_approved_website_order_index_definition(): void
    {
        $index = OrderResource::registration()['index'];

        $this->assertSame('orders_index', $index['key']);
        $this->assertSame(Order::class, $index['model']);
        $this->assertSame('all', $index['base_scope']);
        $this->assertSame(['placed_at' => 'desc', 'public_id' => 'desc'], $index['default_sort']);
        $this->assertSame(['public_id', 'customer', 'order_source', 'status', 'payment_status', 'total_amount_minor', 'placed_at'], $index['columns']);
        $this->assertContains('pending_payment', array_column($index['scopes'], 'key'));
        $this->assertContains('active', array_column($index['scopes'], 'key'));
        $this->assertContains('completed', array_column($index['scopes'], 'key'));
        $this->assertContains('status', array_column($index['filters'], 'key'));
        $this->assertContains('order_source', array_column($index['filters'], 'key'));
        $this->assertContains('design_approved', array_column($index['filters'], 'key'));
        $this->assertContains('placed_from', array_column($index['filters'], 'key'));
        $this->assertContains('placed_to', array_column($index['filters'], 'key'));
    }

    public function test_order_index_query_returns_all_orders_and_sorts_newest_first(): void
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

        $salesOrder = Order::factory()->create([
            'public_id' => 'OD-SALES',
            'status' => OrderStatus::Delivered->value(),
            'order_type' => 'sales_order',
            'order_source' => 'manual',
            'placed_at' => now()->subHours(6),
            'design_approved' => true,
        ]);

        $results = $catalog->query()->get();

        // Should return all 3 orders now that websiteOrders base scope restriction is removed
        $this->assertCount(3, $results);
        $this->assertSame(['OD-SALES', 'OD-NEWEST', 'OD-OLDEST'], $results->pluck('public_id')->all());
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

    public function test_order_index_combined_search_filter_and_pagination_persistence(): void
    {
        // 1. Create permission, role and acting user
        Permission::query()->updateOrCreate(['slug' => 'orders.view'], [
            'name' => 'Orders View',
            'group' => 'orders',
            'guard_name' => 'web',
            'description' => 'Allow viewing orders',
            'is_sensitive' => false,
        ]);

        $role = Role::query()->updateOrCreate(['slug' => 'admin'], [
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);

        $role->permissions()->sync(Permission::query()->where('slug', 'orders.view')->pluck('id')->all());

        $user = User::factory()->create();
        $user->assignRole($role);

        // 2. Create sample orders with specific search parameters
        Order::factory()->create([
            'public_id' => 'OD-RAHUL-1',
            'status' => OrderStatus::PendingPayment->value(),
            'order_type' => 'website_order',
            'order_source' => 'whatsapp',
            'customer_snapshot' => ['name' => 'Rahul Sharma', 'phone' => '9999999999'],
            'total_amount_minor' => 10000,
            'placed_at' => now()->subDay(),
        ]);

        Order::factory()->create([
            'public_id' => 'OD-RAHUL-2',
            'status' => OrderStatus::PendingPayment->value(),
            'order_type' => 'website_order',
            'order_source' => 'whatsapp',
            'customer_snapshot' => ['name' => 'Rahul Verma', 'phone' => '9999999999'],
            'total_amount_minor' => 15000,
            'placed_at' => now()->subHours(2),
        ]);

        Order::factory()->create([
            'public_id' => 'OD-OTHER',
            'status' => OrderStatus::PendingPayment->value(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'customer_snapshot' => ['name' => 'Saurav Sen', 'phone' => '8888888888'],
            'total_amount_minor' => 20000,
            'placed_at' => now()->subHours(5),
        ]);

        Order::factory()->create([
            'public_id' => 'OD-RAHUL-CONFIRMED',
            'status' => OrderStatus::Confirmed->value(),
            'order_type' => 'website_order',
            'order_source' => 'whatsapp',
            'customer_snapshot' => ['name' => 'Rahul Gupta', 'phone' => '9999999999'],
            'total_amount_minor' => 25000,
            'placed_at' => now(),
        ]);

        // 3. Request order index with filters, sorting, search text, and pagination
        $response = $this->actingAs($user)
            ->get(route('admin.orders.index', [
                'search' => 'Rahul',
                'scope' => 'pending_payment',
                'order_source' => 'whatsapp',
                'per_page' => 1,
                'page' => 1,
                'sort' => 'total_amount_minor',
                'direction' => 'asc',
            ]));

        $response->assertStatus(200);

        // Verify that the query parameter values persist inside table header sort links
        $response->assertSee('search=Rahul');
        $response->assertSee('scope=pending_payment');
        $response->assertSee('order_source=whatsapp');

        // Verify pagination links render and carry over active search/filter parameters
        $response->assertSee('page=2');
        $response->assertSee('per_page=1');
    }
}
