<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $financeStaff;

    private User $salesStaff;

    private User $inventoryStaff;

    private User $productionStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $viewReports = Permission::create([
            'name' => 'View Reports',
            'slug' => 'reports.view',
            'group' => 'reports',
        ]);

        $manageExpenses = Permission::create([
            'name' => 'Manage Expenses',
            'slug' => 'finance.manage_expenses',
            'group' => 'finance',
        ]);

        $dashboardAccess = Permission::create([
            'name' => 'Dashboard Access',
            'slug' => 'dashboard.access',
            'group' => 'settings',
        ]);

        // Create roles
        $adminRole = Role::create(['name' => 'Admin', 'slug' => Role::ADMIN]);
        $adminRole->permissions()->attach([$viewReports->id, $manageExpenses->id, $dashboardAccess->id]);

        $financeRole = Role::create(['name' => 'Finance Staff', 'slug' => Role::FINANCE_STAFF]);
        $financeRole->permissions()->attach([$viewReports->id, $manageExpenses->id, $dashboardAccess->id]);

        $salesRole = Role::create(['name' => 'Sales Staff', 'slug' => Role::SALES_STAFF]);
        $salesRole->permissions()->attach([$dashboardAccess->id]);

        $inventoryRole = Role::create(['name' => 'Inventory Staff', 'slug' => Role::INVENTORY_STAFF]);
        $inventoryRole->permissions()->attach([$dashboardAccess->id]);

        $productionRole = Role::create(['name' => 'Production Staff', 'slug' => Role::PRODUCTION_STAFF]);
        $productionRole->permissions()->attach([$dashboardAccess->id]);

        // Create users
        $this->admin = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->admin->roles()->attach($adminRole);

        $this->financeStaff = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->financeStaff->roles()->attach($financeRole);

        $this->salesStaff = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->salesStaff->roles()->attach($salesRole);

        $this->inventoryStaff = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->inventoryStaff->roles()->attach($inventoryRole);

        $this->productionStaff = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->productionStaff->roles()->attach($productionRole);
    }

    /**
     * Test role-based authorization for the report endpoint.
     */
    public function test_report_authorization(): void
    {
        // Unauthenticated forbidden (guest check must run first as actingAs is sticky)
        $this->getJson(route('admin.expenses.report'))
            ->assertStatus(401);

        // Admin allowed
        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report'))
            ->assertOk();

        // Finance allowed
        $this->actingAs($this->financeStaff)
            ->getJson(route('admin.expenses.report'))
            ->assertOk();

        // Sales forbidden
        $this->actingAs($this->salesStaff)
            ->getJson(route('admin.expenses.report'))
            ->assertStatus(403);

        // Inventory forbidden
        $this->actingAs($this->inventoryStaff)
            ->getJson(route('admin.expenses.report'))
            ->assertStatus(403);

        // Production forbidden
        $this->actingAs($this->productionStaff)
            ->getJson(route('admin.expenses.report'))
            ->assertStatus(403);
    }

    /**
     * Test report structure and calculations on empty dataset.
     */
    public function test_report_empty_dataset(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report'))
            ->assertOk();

        $response->assertJsonStructure([
            'currency',
            'summary' => [
                'total_amount',
                'approved_amount',
                'pending_amount',
                'rejected_amount',
                'total_expenses',
            ],
            'categories',
        ]);

        $response->assertJson([
            'currency' => 'INR',
            'summary' => [
                'total_amount' => '0.00',
                'approved_amount' => '0.00',
                'pending_amount' => '0.00',
                'rejected_amount' => '0.00',
                'total_expenses' => 0,
            ],
            'categories' => [],
        ]);
    }

    /**
     * Test report aggregation and filters with loaded data.
     */
    public function test_report_aggregation_and_filters(): void
    {
        $cat1 = ExpenseCategory::factory()->create(['name' => 'A Travel', 'public_id' => 'CAT-TRAVEL']);
        $cat2 = ExpenseCategory::factory()->create(['name' => 'B Supplies', 'public_id' => 'CAT-SUPPLIES']);

        // Create expenses with different dates and categories
        Expense::factory()->create([
            'expense_category_id' => $cat1->id,
            'amount_minor' => 15000, // 150.00
            'status' => Expense::STATUS_APPROVED,
            'occurred_at' => '2026-06-01',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        Expense::factory()->create([
            'expense_category_id' => $cat1->id,
            'amount_minor' => 5000, // 50.00
            'status' => Expense::STATUS_PENDING_APPROVAL,
            'occurred_at' => '2026-06-15',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        Expense::factory()->create([
            'expense_category_id' => $cat2->id,
            'amount_minor' => 200000000, // 2,000,000.00 (Large amount)
            'status' => Expense::STATUS_REJECTED,
            'occurred_at' => '2026-06-20',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        Expense::factory()->create([
            'expense_category_id' => $cat2->id,
            'amount_minor' => 30000, // 300.00
            'status' => Expense::STATUS_APPROVED,
            'occurred_at' => '2026-05-15', // Outside June date range filter
            'recorded_by_user_id' => $this->admin->id,
        ]);

        // Query report for June 2026
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-30',
            ]))
            ->assertOk();

        // 1. Overall Summary
        $response->assertJson([
            'currency' => 'INR',
            'summary' => [
                'total_amount' => '2000200.00', // 150 + 50 + 2,000,000
                'approved_amount' => '150.00',
                'pending_amount' => '50.00',
                'rejected_amount' => '2000000.00',
                'total_expenses' => 3,
            ],
        ]);

        // 2. Deterministic order check (categories ordered by category_id asc)
        $categories = $response->json('categories');
        $this->assertCount(2, $categories);

        $this->assertEquals('CAT-TRAVEL', $categories[0]['category']['public_id']);
        $this->assertEquals('A Travel', $categories[0]['category']['name']);
        $this->assertEquals('200.00', $categories[0]['totals']['total_amount']);
        $this->assertEquals('150.00', $categories[0]['totals']['approved']);
        $this->assertEquals('50.00', $categories[0]['totals']['pending']);
        $this->assertEquals('0.00', $categories[0]['totals']['rejected']);
        $this->assertEquals(2, $categories[0]['count']);

        $this->assertEquals('CAT-SUPPLIES', $categories[1]['category']['public_id']);
        $this->assertEquals('B Supplies', $categories[1]['category']['name']);
        $this->assertEquals('2000000.00', $categories[1]['totals']['total_amount']);
        $this->assertEquals(1, $categories[1]['count']);

        // 3. Verify no internal IDs leak in the categories array
        $response->assertJsonMissingPath('categories.0.expense_category_id');
        $response->assertJsonMissingPath('categories.0.category.id');
    }

    /**
     * Test report with optional monthly grouping.
     */
    public function test_report_monthly_grouping(): void
    {
        $category = ExpenseCategory::factory()->create();

        Expense::factory()->create([
            'expense_category_id' => $category->id,
            'amount_minor' => 10000, // 100.00
            'status' => Expense::STATUS_APPROVED,
            'occurred_at' => '2026-05-10',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        Expense::factory()->create([
            'expense_category_id' => $category->id,
            'amount_minor' => 25000, // 250.00
            'status' => Expense::STATUS_APPROVED,
            'occurred_at' => '2026-06-12',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        Expense::factory()->create([
            'expense_category_id' => $category->id,
            'amount_minor' => 5000, // 50.00
            'status' => Expense::STATUS_PENDING_APPROVAL,
            'occurred_at' => '2026-06-25',
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'group_by' => 'month',
            ]))
            ->assertOk();

        $response->assertJsonStructure([
            'currency',
            'summary',
            'monthly' => [
                '*' => [
                    'month',
                    'totals' => [
                        'total_amount',
                        'approved',
                        'pending',
                        'rejected',
                    ],
                    'count',
                ],
            ],
        ]);

        $monthly = $response->json('monthly');
        $this->assertCount(2, $monthly);

        // Deterministic order: 2026-05 first, then 2026-06
        $this->assertEquals('2026-05', $monthly[0]['month']);
        $this->assertEquals('100.00', $monthly[0]['totals']['total_amount']);
        $this->assertEquals(1, $monthly[0]['count']);

        $this->assertEquals('2026-06', $monthly[1]['month']);
        $this->assertEquals('300.00', $monthly[1]['totals']['total_amount']);
        $this->assertEquals('250.00', $monthly[1]['totals']['approved']);
        $this->assertEquals('50.00', $monthly[1]['totals']['pending']);
        $this->assertEquals(2, $monthly[1]['count']);
    }

    /**
     * Test validation constraints.
     */
    public function test_report_validation(): void
    {
        // 1. Invalid date range (start_date after end_date)
        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'start_date' => '2026-06-30',
                'end_date' => '2026-06-01',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);

        // 2. Invalid date format
        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'start_date' => '06-01-2026',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);

        // 3. Nonexistent category public ID
        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'expense_category_public_id' => 'CAT-NONEXISTENT',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['expense_category_public_id']);

        // 4. Invalid status
        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'status' => 'invalid_status_value',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        // 5. Invalid group_by
        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report', [
                'group_by' => 'invalid_group_by',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['group_by']);
    }

    /**
     * Test that N+1 queries are prevented for category mapping.
     */
    public function test_report_prevent_n_plus_one_queries(): void
    {
        // Create multiple categories
        $cats = ExpenseCategory::factory()->count(5)->create();
        foreach ($cats as $cat) {
            Expense::factory()->create([
                'expense_category_id' => $cat->id,
                'amount_minor' => 1000,
                'recorded_by_user_id' => $this->admin->id,
            ]);
        }

        // Measure query count for 5 categories
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report'))
            ->assertOk();

        $queryCountFirst = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Create 5 more categories with expenses
        $moreCats = ExpenseCategory::factory()->count(5)->create();
        foreach ($moreCats as $cat) {
            Expense::factory()->create([
                'expense_category_id' => $cat->id,
                'amount_minor' => 1000,
                'recorded_by_user_id' => $this->admin->id,
            ]);
        }

        // Measure query count for 10 categories
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.report'))
            ->assertOk();

        $queryCountSecond = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Query count should remain constant (no N+1 loading)
        $this->assertEquals($queryCountFirst, $queryCountSecond);
    }
}
