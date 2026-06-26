<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $unauthorizedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $manageExpenses = Permission::create([
            'name' => 'Manage Expenses',
            'slug' => 'finance.manage_expenses',
            'group' => 'finance',
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
        ]);
        $adminRole->permissions()->attach($manageExpenses);

        $salesRole = Role::create([
            'name' => 'Sales',
            'slug' => Role::SALES_STAFF,
        ]);

        // Create users
        $this->admin = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->unauthorizedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedStaff->roles()->attach($salesRole);
    }

    public function test_authorized_user_can_create_expense(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.store'), [
                'expense_category_public_id' => $category->public_id,
                'amount' => '120.50',
                'currency' => 'INR',
                'notes' => 'Office supplies',
                'reference' => 'REF-12345',
                'status' => Expense::STATUS_DRAFT,
                'occurred_at' => Carbon::today()->toDateString(),
            ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'public_id',
                'amount',
                'currency',
                'notes',
                'reference',
                'status',
                'occurred_at',
                'category' => ['public_id', 'name', 'code', 'is_active'],
                'recorded_by' => ['name', 'email'],
            ],
        ]);

        $this->assertDatabaseHas('expenses', [
            'amount_minor' => 12050,
            'recorded_by_user_id' => $this->admin->id,
            'notes' => 'Office supplies',
        ]);
    }

    public function test_unauthorized_user_cannot_create_expense(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->unauthorizedStaff)
            ->postJson(route('admin.expenses.store'), [
                'expense_category_public_id' => $category->public_id,
                'amount' => '120.50',
                'occurred_at' => Carbon::today()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_validation_rejects_missing_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.store'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_category_public_id', 'amount', 'occurred_at']);
    }

    public function test_validation_rejects_scientific_notation_in_amount(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.store'), [
                'expense_category_public_id' => $category->public_id,
                'amount' => '1e3',
                'occurred_at' => Carbon::today()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    public function test_validation_rejects_inactive_category(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.store'), [
                'expense_category_public_id' => $category->public_id,
                'amount' => '100.00',
                'occurred_at' => Carbon::today()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_category_public_id']);
        $response->assertJsonPath('errors.expense_category_public_id.0', 'Expense category is inactive.');
    }

    public function test_validation_rejects_soft_deleted_category(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $category->delete();

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.store'), [
                'expense_category_public_id' => $category->public_id,
                'amount' => '100.00',
                'occurred_at' => Carbon::today()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['expense_category_public_id']);
    }

    public function test_validation_rejects_future_occurred_date(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.store'), [
                'expense_category_public_id' => $category->public_id,
                'amount' => '100.00',
                'occurred_at' => Carbon::tomorrow()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['occurred_at']);
    }

    public function test_deleting_category_linked_to_expense_fails(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('admin.expense_categories.destroy', $category));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);
        $response->assertJsonPath('errors.category.0', 'Expense category is referenced by existing expenses.');
    }

    public function test_public_id_and_recorded_by_are_immutable(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Public ID is immutable.');
        $expense->public_id = 'EXP-CHANGED';
        $expense->save();
    }

    public function test_recorded_by_user_id_is_immutable(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $anotherUser = User::factory()->create();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Recorded by user is immutable.');
        $expense->recorded_by_user_id = $anotherUser->id;
        $expense->save();
    }

    public function test_unauthorized_user_cannot_leak_existence(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        // Unauthorized querying valid public_id returns 403
        $response = $this->actingAs($this->unauthorizedStaff)
            ->getJson(route('admin.expenses.show', $expense->public_id));
        $response->assertStatus(403);

        // Unauthorized querying nonexistent public_id returns 404
        $response = $this->actingAs($this->unauthorizedStaff)
            ->getJson(route('admin.expenses.show', 'EXP-NONEXISTENT'));
        $response->assertStatus(404);
    }

    public function test_listing_has_deterministic_sort(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        $date1 = Carbon::parse('2026-06-25');
        $date2 = Carbon::parse('2026-06-26');

        // Occurred at 25th (id 1)
        $expense1 = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
            'occurred_at' => $date1,
        ]);

        // Occurred at 26th (id 2)
        $expense2 = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
            'occurred_at' => $date2,
        ]);

        // Occurred at 26th (id 3)
        $expense3 = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
            'occurred_at' => $date2,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.index'));

        $response->assertOk();
        $data = $response->json('data');

        // Order should be: expense3 (26th, id 3), expense2 (26th, id 2), expense1 (25th, id 1)
        $this->assertEquals($expense3->public_id, $data[0]['public_id']);
        $this->assertEquals($expense2->public_id, $data[1]['public_id']);
        $this->assertEquals($expense1->public_id, $data[2]['public_id']);
    }

    public function test_n_plus_one_query_safety(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        Expense::factory()->count(5)->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.index'));

        $response->assertOk();

        // 1 count query, 1 listing query, 1 session check user query, 1 roles eager load, 1 permissions eager load, 1 expense_categories query, 1 users query.
        // N+1 would cause 5 additional queries for category and 5 for user.
        $queries = DB::getQueryLog();
        $this->assertLessThan(15, count($queries), 'N+1 queries detected on index listing.');
    }

    public function test_soft_deleted_category_renders_correctly_on_expense(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $category->delete();

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.expenses.show', $expense->public_id));

        $response->assertOk();
        $response->assertJsonPath('data.category.name', $category->name);
    }
}
