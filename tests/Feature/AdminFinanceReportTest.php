<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinanceReportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin User
        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => 'Admin', 'guard_name' => 'web']
        );
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        // Staff User without finance report permission
        $staffRole = Role::query()->updateOrCreate(
            ['slug' => 'sales_staff'],
            ['name' => 'Sales Staff', 'guard_name' => 'web']
        );
        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->assignRole($staffRole);
    }

    /**
     * Web guest receives 302 redirect to login.
     */
    public function test_web_guest_redirected_to_login(): void
    {
        $response = $this->get('/admin/reports/finance');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    /**
     * JSON guest receives 401 Unauthorized.
     */
    public function test_json_guest_receives_401_unauthorized(): void
    {
        $response = $this->getJson('/admin/reports/finance/summary');
        $response->assertStatus(401);
    }

    /**
     * Unauthorized user receives 403 Forbidden.
     */
    public function test_unauthorized_user_receives_403_forbidden(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get('/admin/reports/finance');
        $response->assertStatus(403);

        $jsonResponse = $this->actingAs($this->unauthorizedUser)->getJson('/admin/reports/finance/summary');
        $jsonResponse->assertStatus(403);
    }

    /**
     * Admin user accesses dashboard view successfully.
     */
    public function test_admin_user_views_finance_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/reports/finance');
        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.finance');
        $response->assertSee('Finance & Operating Reports');
        $response->assertSee('Booked Sales Revenue');
    }

    /**
     * Admin user fetches JSON summary endpoint.
     */
    public function test_admin_user_fetches_json_summary(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson('/admin/reports/finance/summary');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'currency',
            'filters' => ['preset', 'start_date', 'end_date', 'group_by', 'timezone'],
            'metrics' => [
                'total_sales_minor',
                'total_sales_formatted',
                'total_payments_minor',
                'total_payments_formatted',
                'total_refunds_minor',
                'total_refunds_formatted',
                'total_expenses_minor',
                'total_expenses_formatted',
                'total_outstanding_minor',
                'total_outstanding_formatted',
                'net_cash_flow_minor',
                'net_cash_flow_formatted',
                'net_operating_income_minor',
                'net_operating_income_formatted',
            ],
            'monthly_trend',
            'expense_categories',
        ]);
    }

    /**
     * One-sided custom date range validation returns 422 Unprocessable Entity.
     */
    public function test_one_sided_custom_date_range_returns_422(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson('/admin/reports/finance/summary?start_date=2026-06-01');
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_date']);
    }
}
