<?php
 
namespace Tests\Feature;
 
use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\AuditLog;
use App\Enums\OrderStatus;
use App\Enums\AuditActorType;
use App\Services\DashboardService;
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
        $response->assertSee('Live System Status');
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
        $response->assertSee('Pending Orders');
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
        $response->assertSee('No sales revenue logged for the last 6 months.');
 
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
        $response->assertSee('Revenue Trend Chart'); // title tag test
        $response->assertSee('Monthly Orders Chart'); // title tag test
        
        // Confirm the empty state guidelines cards are now hidden
        $response->assertDontSee('No sales revenue logged for the last 6 months.');
    }
}
