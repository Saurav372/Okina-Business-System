<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\Quotation;
use App\Enums\OrderStatus;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    protected function createSuperAdminUser(): User
    {
        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);
        
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

        // 5. Create a converted quotation and a draft quotation
        Quotation::factory()->create([
            'status' => Quotation::STATUS_CONVERTED,
        ]);
        Quotation::factory()->create([
            'status' => Quotation::STATUS_DRAFT,
        ]);

        // Force cache invalidation to load fresh seed values
        (new DashboardMetricsService)->clearCache();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('₹150.00'); // Revenue check
        $response->assertSee('Active Orders');
        $response->assertSee('1'); // 1 active order
        $response->assertSee('50.0%'); // Quote Conversion check (1 out of 2 = 50%)
        $response->assertDontSee('Welcome to your new dashboard!'); // Onboarding banner should be hidden now
    }
}
