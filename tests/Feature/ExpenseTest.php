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

    private User $submitterOnly;

    private User $unauthorizedStaff;

    private User $inventoryStaff;

    private User $productionStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $manageExpenses = Permission::create([
            'name' => 'Manage Expenses',
            'slug' => 'finance.manage_expenses',
            'group' => 'finance',
        ]);

        $approveExpenses = Permission::create([
            'name' => 'Approve Expenses',
            'slug' => 'finance.approve_expenses',
            'group' => 'finance',
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'slug' => Role::ADMIN,
        ]);
        $adminRole->permissions()->attach([$manageExpenses->id, $approveExpenses->id]);

        $financeRole = Role::create([
            'name' => 'Finance Staff',
            'slug' => Role::FINANCE_STAFF,
        ]);
        $financeRole->permissions()->attach([$manageExpenses->id]);

        $salesRole = Role::create([
            'name' => 'Sales',
            'slug' => Role::SALES_STAFF,
        ]);

        $inventoryRole = Role::create([
            'name' => 'Inventory Staff',
            'slug' => Role::INVENTORY_STAFF,
        ]);

        $productionRole = Role::create([
            'name' => 'Production Staff',
            'slug' => Role::PRODUCTION_STAFF,
        ]);

        // Create users
        $this->admin = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->submitterOnly = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->submitterOnly->roles()->attach($financeRole);

        $this->unauthorizedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedStaff->roles()->attach($salesRole);

        $this->inventoryStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->inventoryStaff->roles()->attach($inventoryRole);

        $this->productionStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->productionStaff->roles()->attach($productionRole);
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

    public function test_expense_submit_transition_success(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.submit', $expense->public_id));

        $response->assertOk();
        $response->assertJsonPath('data.status', Expense::STATUS_PENDING_APPROVAL);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        $freshExpense = Expense::findOrFail($expense->id);
        $this->assertNotNull($freshExpense->metadata);
        $this->assertEquals(1, $freshExpense->metadata['version']);
        $this->assertCount(1, $freshExpense->metadata['history']);

        $entry = $freshExpense->metadata['history'][0];
        $this->assertEquals('submit', $entry['action']);
        $this->assertEquals(Expense::STATUS_DRAFT, $entry['from']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $entry['to']);
        $this->assertEquals($this->submitterOnly->id, $entry['performed_by_user_id']);
        $this->assertNotNull($entry['performed_at']);
    }

    public function test_expense_approval_transition_success(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.approve', $expense->public_id));

        $response->assertOk();
        $response->assertJsonPath('data.status', Expense::STATUS_APPROVED);

        $freshExpense = Expense::findOrFail($expense->id);
        $this->assertEquals(Expense::STATUS_APPROVED, $freshExpense->status);
        $this->assertNotNull($freshExpense->approved_at);

        $entry = $freshExpense->metadata['history'][0];
        $this->assertEquals('approve', $entry['action']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $entry['from']);
        $this->assertEquals(Expense::STATUS_APPROVED, $entry['to']);
        $this->assertEquals($this->admin->id, $entry['performed_by_user_id']);
    }

    public function test_expense_rejection_transition_success(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        $reason = 'Reason with at least ten characters.';
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => '   '.$reason.'   ', // test whitespace trim
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', Expense::STATUS_REJECTED);

        $freshExpense = Expense::findOrFail($expense->id);
        $this->assertEquals(Expense::STATUS_REJECTED, $freshExpense->status);
        $this->assertNull($freshExpense->approved_at);

        $entry = $freshExpense->metadata['history'][0];
        $this->assertEquals('reject', $entry['action']);
        $this->assertEquals($reason, $entry['reason']); // trimmed
        $this->assertEquals($this->admin->id, $entry['performed_by_user_id']);
    }

    public function test_expense_resubmit_after_rejection(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.submit', $expense->public_id));

        $response->assertOk();
        $response->assertJsonPath('data.status', Expense::STATUS_PENDING_APPROVAL);

        $freshExpense = Expense::findOrFail($expense->id);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $freshExpense->status);

        $entry = $freshExpense->metadata['history'][0];
        $this->assertEquals('submit', $entry['action']);
        $this->assertEquals(Expense::STATUS_REJECTED, $entry['from']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $entry['to']);
    }

    public function test_cannot_transition_from_approved_terminal_state(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_APPROVED,
            'approved_at' => Carbon::now()->subHour(),
        ]);

        $originalApprovedAt = $expense->approved_at;

        // Try submit
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.submit', $expense->public_id));
        $response->assertStatus(422);

        // Try approve
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.approve', $expense->public_id));
        $response->assertStatus(422);

        // Try reject
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => 'Some rejection reason here.',
            ]);
        $response->assertStatus(422);

        // Assert approved_at not modified
        $freshExpense = Expense::findOrFail($expense->id);
        $this->assertEquals($originalApprovedAt->toIso8601String(), $freshExpense->approved_at->toIso8601String());
    }

    public function test_permissions_gating_on_transitions(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        // SubmitterOnly cannot approve
        $response = $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.approve', $expense->public_id));
        $response->assertStatus(403);

        // SubmitterOnly cannot reject
        $response = $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => 'Reason long enough.',
            ]);
        $response->assertStatus(403);

        // UnauthorizedStaff cannot submit
        $expense->status = Expense::STATUS_DRAFT;
        $expense->save();
        $response = $this->actingAs($this->unauthorizedStaff)
            ->postJson(route('admin.expenses.submit', $expense->public_id));
        $response->assertStatus(403);
    }

    public function test_rejection_validation_rules(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        // Missing reason
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rejection_reason']);

        // Too short reason (less than 10 chars after trim)
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => '  short  ',
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rejection_reason']);

        // Spaces only
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => '           ',
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rejection_reason']);

        // Too long reason (over 1000 chars)
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => str_repeat('a', 1001),
            ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rejection_reason']);
    }

    public function test_invalid_direct_transitions_fail(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);

        // Draft directly to Approved
        $expense1 = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_DRAFT,
        ]);
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.approve', $expense1->public_id));
        $response->assertStatus(422);

        // Rejected directly to Approved
        $expense2 = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_REJECTED,
        ]);
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.approve', $expense2->public_id));
        $response->assertStatus(422);
    }

    public function test_duplicate_submits_fail(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.submit', $expense->public_id));
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_chronological_history_sequences(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_DRAFT,
        ]);

        // Transition 1: Submit
        $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.submit', $expense->public_id))
            ->assertOk();

        // Transition 2: Reject
        $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), [
                'rejection_reason' => 'Please correct the amount value.',
            ])
            ->assertOk();

        // Transition 3: Submit again
        $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.submit', $expense->public_id))
            ->assertOk();

        // Transition 4: Approve
        $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.approve', $expense->public_id))
            ->assertOk();

        $freshExpense = Expense::findOrFail($expense->id);
        $history = $freshExpense->metadata['history'];
        $this->assertCount(4, $history);

        $this->assertEquals('submit', $history[0]['action']);
        $this->assertEquals(Expense::STATUS_DRAFT, $history[0]['from']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $history[0]['to']);

        $this->assertEquals('reject', $history[1]['action']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $history[1]['from']);
        $this->assertEquals(Expense::STATUS_REJECTED, $history[1]['to']);

        $this->assertEquals('submit', $history[2]['action']);
        $this->assertEquals(Expense::STATUS_REJECTED, $history[2]['from']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $history[2]['to']);

        $this->assertEquals('approve', $history[3]['action']);
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $history[3]['from']);
        $this->assertEquals(Expense::STATUS_APPROVED, $history[3]['to']);
    }

    // -----------------------------------------------------------------------
    // C5.3.4 – Expense permission boundary tests
    // -----------------------------------------------------------------------

    /**
     * Users with finance.manage_expenses (Admin, Finance Staff) can access
     * the read-only expense listing.
     */
    public function test_manage_expenses_roles_can_list_and_show_expenses(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        foreach ([$this->admin, $this->submitterOnly] as $user) {
            $this->actingAs($user)
                ->getJson(route('admin.expenses.index'))
                ->assertOk();

            $this->actingAs($user)
                ->getJson(route('admin.expenses.show', $expense->public_id))
                ->assertOk();
        }
    }

    /**
     * Roles without finance.manage_expenses cannot list or show expenses.
     */
    public function test_roles_without_manage_expenses_cannot_list_or_show_expenses(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            $this->actingAs($user)
                ->getJson(route('admin.expenses.index'))
                ->assertStatus(403);

            $this->actingAs($user)
                ->getJson(route('admin.expenses.show', $expense->public_id))
                ->assertStatus(403);
        }
    }

    /**
     * Roles without finance.manage_expenses cannot create or update expenses.
     */
    public function test_roles_without_manage_expenses_cannot_create_or_update_expenses(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        $payload = [
            'expense_category_public_id' => $category->public_id,
            'amount' => '100.00',
            'occurred_at' => Carbon::today()->toDateString(),
        ];

        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            $this->actingAs($user)
                ->postJson(route('admin.expenses.store'), $payload)
                ->assertStatus(403);

            $this->actingAs($user)
                ->putJson(route('admin.expenses.update', $expense->public_id), $payload)
                ->assertStatus(403);
        }
    }

    /**
     * Roles without finance.manage_expenses cannot delete expenses.
     */
    public function test_roles_without_manage_expenses_cannot_delete_expenses(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
        ]);

        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            $this->actingAs($user)
                ->deleteJson(route('admin.expenses.destroy', $expense->public_id))
                ->assertStatus(403);
        }
    }

    /**
     * Roles without finance.manage_expenses cannot submit expenses.
     */
    public function test_roles_without_manage_expenses_cannot_submit_expenses(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
            'status' => Expense::STATUS_DRAFT,
        ]);

        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            $this->actingAs($user)
                ->postJson(route('admin.expenses.submit', $expense->public_id))
                ->assertStatus(403);
        }
    }

    /**
     * Only roles with finance.approve_expenses (Admin) can approve expenses.
     * Finance Staff (manage only) cannot approve.
     */
    public function test_only_approve_expenses_role_can_approve(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        // Finance Staff (manage but not approve) — must be 403
        $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.approve', $expense->public_id))
            ->assertStatus(403);

        // Unauthorized roles — must be 403
        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            $this->actingAs($user)
                ->postJson(route('admin.expenses.approve', $expense->public_id))
                ->assertStatus(403);
        }

        // Admin (has approve_expenses) — must succeed
        $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.approve', $expense->public_id))
            ->assertOk();
    }

    /**
     * Only roles with finance.approve_expenses (Admin) can reject expenses.
     * Finance Staff (manage only) cannot reject.
     */
    public function test_only_approve_expenses_role_can_reject(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->submitterOnly->id,
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        $reason = ['rejection_reason' => 'Reason must be at least ten chars.'];

        // Finance Staff (manage but not approve) — must be 403
        $this->actingAs($this->submitterOnly)
            ->postJson(route('admin.expenses.reject', $expense->public_id), $reason)
            ->assertStatus(403);

        // Unauthorized roles — must be 403
        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            $this->actingAs($user)
                ->postJson(route('admin.expenses.reject', $expense->public_id), $reason)
                ->assertStatus(403);
        }

        // Admin (has approve_expenses) — must succeed
        $this->actingAs($this->admin)
            ->postJson(route('admin.expenses.reject', $expense->public_id), $reason)
            ->assertOk();
    }

    /**
     * Unauthorized users always receive 403 on a valid public_id — not 404.
     * This prevents existence leakage (ID enumeration).
     */
    public function test_no_existence_leakage_for_unauthorized_users(): void
    {
        $category = ExpenseCategory::factory()->create(['is_active' => true]);
        $expense = Expense::factory()->create([
            'expense_category_id' => $category->id,
            'recorded_by_user_id' => $this->admin->id,
            'status' => Expense::STATUS_DRAFT,
        ]);

        $validId = $expense->public_id;

        foreach ([$this->unauthorizedStaff, $this->inventoryStaff, $this->productionStaff] as $user) {
            // show — valid ID must return 403, not 404
            $this->actingAs($user)
                ->getJson(route('admin.expenses.show', $validId))
                ->assertStatus(403);

            // update — valid ID must return 403
            $this->actingAs($user)
                ->putJson(route('admin.expenses.update', $validId), [])
                ->assertStatus(403);

            // delete — valid ID must return 403
            $this->actingAs($user)
                ->deleteJson(route('admin.expenses.destroy', $validId))
                ->assertStatus(403);

            // submit — valid ID must return 403
            $this->actingAs($user)
                ->postJson(route('admin.expenses.submit', $validId))
                ->assertStatus(403);

            // approve — valid ID must return 403
            $this->actingAs($user)
                ->postJson(route('admin.expenses.approve', $validId))
                ->assertStatus(403);

            // reject — valid ID must return 403
            $this->actingAs($user)
                ->postJson(route('admin.expenses.reject', $validId), [
                    'rejection_reason' => 'Ten chars minimum.',
                ])
                ->assertStatus(403);
        }
    }
}
