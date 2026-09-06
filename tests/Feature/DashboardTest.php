<?php

namespace Tests\Feature;

use App\Enums\AuditActorType;
use App\Enums\OrderStatus;
use App\Events\AuditEvent;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use App\Services\DashboardService;
use App\Support\Admin\OrderIndexCatalog;
use App\Support\Finance\PaymentFilters;
use App\Support\Finance\PaymentQueryBuilder;
use App\Support\Inventory\StockBalanceCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Flush cache before each test to prevent cross-contamination
        Cache::flush();
    }

    protected function createSuperAdminUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ], $attributes));

        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => Role::SUPER_ADMIN,
        ]);

        $user->roles()->attach($role->id, [
            'assigned_by_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return $user;
    }

    protected function createAuditLog(array $attributes = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'event_id' => (string) Str::uuid(),
            'action' => 'orders.order_created',
            'module' => 'orders',
            'actor_type' => AuditActorType::USER,
            'subject_type' => 'order',
            'occurred_at' => now(),
        ], $attributes));
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = $this->createSuperAdminUser();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('brand/logo-light.png', false);
        $response->assertSee('Search admin modules', false);
        $response->assertDontSee('Command Palette is not implemented in sandbox.');
        $response->assertSee('Updated');
        $response->assertSee('Welcome to your new dashboard!'); // empty state onboarding should display
    }

    public function test_dashboard_calculations_and_empty_state_toggle(): void
    {
        $user = $this->createSuperAdminUser();

        // 1. Create a non-cancelled order
        Order::factory()->create([
            'total_amount_minor' => 15000, // ₹150.00
            'status' => OrderStatus::Confirmed->value(),
        ]);

        // 2. Create a cancelled order (should be excluded from revenue)
        Order::factory()->create([
            'total_amount_minor' => 99000,
            'status' => OrderStatus::Cancelled->value(),
        ]);

        // 3. Create a low stock SKU
        ProductSku::factory()->create([
            'track_stock' => true,
            'stock_quantity' => 2,
            'low_stock_threshold' => 5,
        ]);

        // 4. Create an optimal stock SKU
        ProductSku::factory()->create([
            'track_stock' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
        ]);

        // Force cache invalidation to load fresh seed values
        (new DashboardService)->clearCache($user);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Open Orders');
        $response->assertSee('1'); // 1 active order
        $response->assertDontSee('Welcome to your new dashboard!'); // Onboarding banner should be hidden now
    }

    public function test_recent_activity_timeline_filtering_and_sorting(): void
    {
        $user = $this->createSuperAdminUser();

        // Lock time for stable timestamp checks
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 12, 0, 0));

        // 1. Dispatch an allowed transaction event (Order Created)
        $this->createAuditLog([
            'action' => 'orders.order_created',
            'module' => 'orders',
            'summary' => 'Order OD-123 created',
            'occurred_at' => Carbon::now()->subHours(2), // relative format "2 hours ago"
            'actor_label_snapshot' => 'Saurav Nanda',
        ]);

        // 2. Dispatch a sensitive/internal event that should be filtered out (role assignment)
        $this->createAuditLog([
            'action' => 'users.role_assigned',
            'module' => 'users',
            'summary' => 'Role assigned',
            'occurred_at' => Carbon::now()->subMinutes(5),
            'actor_label_snapshot' => 'Saurav Nanda',
        ]);

        // 3. Dispatch an allowed event from yesterday (Payment Recorded)
        $this->createAuditLog([
            'action' => 'payments.payment_recorded',
            'module' => 'payments',
            'summary' => 'Payment collected',
            'occurred_at' => Carbon::now()->subDays(1)->subHours(1), // "Yesterday, ..."
            'actor_label_snapshot' => 'Saurav Nanda',
        ]);

        // Clear dashboard cache
        (new DashboardService)->clearCache($user);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Order OD-123 created');
        $response->assertSee('2 hours ago');
        $response->assertSee('Yesterday,');
        $response->assertDontSee('Role assigned'); // Internal action should be hidden

        Carbon::setTestNow(); // Reset time mock
    }

    public function test_recent_activity_unicode_names_and_missing_actor_fallbacks(): void
    {
        $user = $this->createSuperAdminUser();

        // 1. Event by Japanese actor
        $this->createAuditLog([
            'action' => 'orders.order_created',
            'module' => 'orders',
            'summary' => 'Order created',
            'occurred_at' => now(),
            'actor_label_snapshot' => '佐藤 健', // Sato Takeru
        ]);

        // 2. Event by Hindi actor
        $this->createAuditLog([
            'action' => 'orders.order_created',
            'module' => 'orders',
            'summary' => 'Order created',
            'occurred_at' => now(),
            'actor_label_snapshot' => 'सौरव नंदा', // Saurav Nanda
        ]);

        // 3. Event with no actor (Deleted/Null fallback)
        $this->createAuditLog([
            'action' => 'orders.order_created',
            'module' => 'orders',
            'summary' => 'Order created',
            'occurred_at' => now(),
            'actor_label_snapshot' => null,
        ]);

        (new DashboardService)->clearCache($user);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('佐'); // Initials fallback for Unicode names should resolve safely
        $response->assertSee('सौ');
        $response->assertSee('SY'); // System/Deleted actor initials fallback
    }

    public function test_dashboard_charts_rendering_and_caching(): void
    {
        $user = $this->createSuperAdminUser();

        // 1. Initially database has 0 orders. Verify empty state prompts display on charts
        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('No order value recorded for this period.');

        // 2. Create order
        Order::factory()->create([
            'total_amount_minor' => 2500000, // ₹25,000.00
            'status' => OrderStatus::Confirmed->value(),
            'placed_at' => now(),
        ]);

        // Evict dashboard cache keys
        (new DashboardService)->clearCache($user);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertStatus(200);

        // Verification of populated data
        $response->assertSee('Order Value');
        $response->assertSee('Monthly Orders');

        // Confirm the empty state guidelines cards are now hidden
        $response->assertDontSee('No order value recorded for this period.');
    }

    public function test_zero_metrics_are_quiet_and_all_eight_cards_have_stable_keys(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);
        $widgets = collect((new DashboardService)->getWidgetsData())->keyBy('key');
        $this->assertCount(8, $widgets);
        $this->assertSame('No payments awaiting deposit', $widgets['advances']->description);
        $this->assertSame('No collections today', $widgets['collections']->description);
        $this->assertNull($widgets['collections']->trend);
        $this->assertSame('neutral', $widgets['advances']->variant);
        $this->get(route('admin.dashboard'))->assertDontSee('action required')->assertDontSee('System Event');
    }

    public function test_operational_cards_and_filtered_lists_agree(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);
        $ready = Order::factory()->create(['status' => 'ready_to_ship', 'order_metadata' => ['payment_schedule' => ['amount_minor' => 5000]]]);
        Order::factory()->create(['status' => 'pending_payment']);
        Order::factory()->create(['status' => 'delivered']);
        Order::factory()->create(['status' => 'cancelled']);
        $widgets = collect((new DashboardService)->getWidgetsData())->keyBy('key');
        $catalog = new OrderIndexCatalog;
        $this->assertSame('2', $widgets['open_orders']->value);
        $this->assertSame('1', $widgets['dispatch']->value);
        $this->assertSame('1', $widgets['advances']->value);
        $this->assertSame(2, $catalog->query(['scope' => 'open'])->count());
        $this->assertSame([$ready->id], $catalog->query(['scope' => 'advance_pending'])->pluck('id')->all());
        $this->get($widgets['open_orders']->href)->assertOk();
        $this->get($widgets['advances']->href)->assertOk();
        $this->get($widgets['dispatch']->href)->assertOk();
    }

    public function test_collection_filter_uses_receipt_date_and_receivables_do_not_net_unrelated_orders(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);
        $paidOrder = Order::factory()->create(['status' => 'confirmed', 'total_amount_minor' => 10000]);
        Order::factory()->create(['status' => 'confirmed', 'total_amount_minor' => 20000]);
        Payment::create([
            'order_id' => $paidOrder->id, 'payment_type' => 'full', 'provider' => 'manual', 'method' => 'cash', 'status' => 'succeeded',
            'amount_minor' => 15000, 'currency' => 'INR', 'paid_at' => now(),
        ]);
        Payment::query()->update(['created_at' => now()->subDays(2)]);
        $widgets = collect((new DashboardService)->getWidgetsData())->keyBy('key');
        $this->assertSame('₹200', $widgets['receivables']->value);
        $this->assertSame('₹150', $widgets['collections']->value);
        $this->assertSame(1, PaymentQueryBuilder::buildQuery(new PaymentFilters(['paid_on' => now()->toDateString()]))->count());
        $this->get($widgets['collections']->href)->assertOk()->assertSee('Received on');
    }

    public function test_chart_period_totals_and_equal_elapsed_comparison_at_month_end(): void
    {
        $this->travelTo(Carbon::parse('2026-09-30 12:00:00'));
        Order::factory()->create(['status' => 'confirmed', 'placed_at' => '2026-07-01 10:00:00', 'total_amount_minor' => 10000]);
        Order::factory()->create(['status' => 'confirmed', 'placed_at' => '2026-09-30 11:00:00', 'total_amount_minor' => 20000]);
        Order::factory()->create(['status' => 'confirmed', 'placed_at' => '2026-06-30 11:00:00', 'total_amount_minor' => 15000]);
        Order::factory()->create(['status' => 'confirmed', 'placed_at' => '2026-06-30 13:00:00', 'total_amount_minor' => 90000]);
        Order::factory()->create(['status' => 'cancelled', 'placed_at' => '2026-09-01 12:00:00', 'total_amount_minor' => 90000]);
        $series = (new DashboardService)->getRevenueTrendSeries(3);
        $this->assertCount(3, $series->points);
        $this->assertSame(300.0, $series->currentValue);
        $this->assertSame(150.0, $series->previousValue);
        $this->assertSame(100.0, $series->changePercent);
        $this->assertTrue($series->points->last()->partial);
        $this->assertSame('September 2026', $series->points->last()->fullLabel);
        $this->assertSame(['Jul', 'Aug', 'Sep'], $series->points->pluck('label')->all());
        $this->travelBack();
    }

    public function test_real_audit_actions_keep_specific_safe_activity_facts(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);
        event(new AuditEvent('payments.payment_recorded', $user, [
            'order_public_id' => 'OD-1042', 'payment_public_id' => 1042,
            'amount_minor' => 1500000, 'currency' => 'INR', 'payment_status' => 'paid', 'record_status' => 'succeeded',
        ]));
        $activity = (new DashboardService)->getRecentActivity($user)->first();
        $this->assertSame('₹15,000.00 received', $activity->title);
        $this->assertNull($activity->href); // No fabricated record destination.
        $this->assertSame('payment.recorded', AuditLog::latest('id')->first()->action);
    }

    public function test_stock_attention_includes_depleted_items_and_matches_the_inventory_list(): void
    {
        $user = $this->createSuperAdminUser();
        $this->actingAs($user);
        foreach ([0, 3, 20] as $quantity) {
            $sku = ProductSku::factory()->create(['low_stock_threshold' => 5]);
            InventoryItem::updateOrCreate(['product_sku_id' => $sku->id], [
                'on_hand_quantity' => $quantity, 'reserved_quantity' => 0, 'low_stock_threshold' => 5,
            ]);
        }
        $widgets = collect((new DashboardService)->getWidgetsData())->keyBy('key');
        $this->assertSame('2', $widgets['low_stock']->value);
        $catalog = new StockBalanceCatalog;
        $this->assertSame(2, $catalog->getPaginatedBalances(['status' => 'needs_attention'])->total());
        $this->get($widgets['low_stock']->href)->assertOk()->assertSee('Low or depleted stock');
    }
}
