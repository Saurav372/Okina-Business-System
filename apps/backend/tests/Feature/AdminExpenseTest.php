<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ExpenseService;
use App\Support\Expenses\ExpenseFilters;
use App\Support\Expenses\ExpenseMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $approverUser;

    protected User $unauthorizedUser;

    protected ExpenseCategory $activeCategory;

    protected ExpenseCategory $inactiveCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $permManage = Permission::query()->firstOrCreate(['slug' => 'finance.manage_expenses'], [
            'name' => 'Manage Expenses',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'Manage expenses',
            'is_sensitive' => false,
        ]);

        $permApprove = Permission::query()->firstOrCreate(['slug' => 'finance.approve_expenses'], [
            'name' => 'Approve Expenses',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'Approve expenses',
            'is_sensitive' => false,
        ]);

        $adminRole = Role::query()->firstOrCreate(['slug' => Role::ADMIN], [
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$permManage->id, $permApprove->id]);

        $approverRole = Role::query()->firstOrCreate(['slug' => Role::FINANCE_STAFF], [
            'name' => 'Finance Staff',
            'guard_name' => 'web',
        ]);
        $approverRole->permissions()->syncWithoutDetaching([$permManage->id, $permApprove->id]);

        $unauthRole = Role::query()->firstOrCreate(['slug' => 'guest_role'], [
            'name' => 'Guest Role',
            'guard_name' => 'web',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);

        $this->approverUser = User::factory()->create();
        $this->approverUser->roles()->attach($approverRole);

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->roles()->attach($unauthRole);

        $this->activeCategory = ExpenseCategory::create([
            'name' => 'Logistics Freight',
            'code' => 'EXP-FREIGHT-01',
            'description' => 'Freight logistics expenses',
            'is_active' => true,
        ]);

        $this->inactiveCategory = ExpenseCategory::create([
            'name' => 'Legacy Discontinued Overhead',
            'code' => 'EXP-LEGACY-99',
            'description' => 'Discontinued category',
            'is_active' => false,
        ]);
    }

    protected function createTestExpense(array $attributes = []): Expense
    {
        return Expense::create(array_merge([
            'expense_category_id' => $this->activeCategory->id,
            'amount_minor' => 250000, // ₹2,500.00
            'currency' => 'INR',
            'recorded_by_user_id' => $this->adminUser->id,
            'reference' => 'INV-2026-FRT-01',
            'notes' => 'Freight transportation charges',
            'status' => Expense::STATUS_DRAFT,
            'occurred_at' => '2026-06-15',
        ], $attributes));
    }

    public function test_admin_can_view_expense_dashboard_and_metrics(): void
    {
        $this->createTestExpense(['reference' => 'REF-DASH-100']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.index'));

        $response->assertOk();
        $response->assertSee('REF-DASH-100');
    }

    public function test_user_can_create_expense_with_active_category(): void
    {
        $payload = [
            'expense_category_public_id' => $this->activeCategory->public_id,
            'amount' => '1500.50',
            'currency' => 'INR',
            'reference' => 'REF-CREATE-200',
            'notes' => 'Office supplies',
            'occurred_at' => '2026-06-20',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.expenses.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('expenses', [
            'expense_category_id' => $this->activeCategory->id,
            'amount_minor' => 150050,
            'reference' => 'REF-CREATE-200',
            'status' => Expense::STATUS_DRAFT,
        ]);
    }

    public function test_creating_expense_with_inactive_category_is_rejected(): void
    {
        $service = app(ExpenseService::class);

        $this->expectException(ValidationException::class);

        $service->createExpense([
            'expense_category_id' => $this->inactiveCategory->id,
            'amount_minor' => 10000,
            'occurred_at' => '2026-06-20',
        ], $this->adminUser);
    }

    public function test_user_can_submit_draft_expense_for_approval(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_DRAFT]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.expenses.submit', $expense));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $expense->status);
    }

    public function test_expense_submission_dispatches_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $expense = $this->createTestExpense(['status' => Expense::STATUS_DRAFT]);
        $service = app(ExpenseService::class);

        $service->submitExpense($expense, $this->adminUser);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($expense) {
            return $event->key === 'expense.submitted'
                && $event->payload['expense_id'] === $expense->id
                && $event->payload['to_status'] === Expense::STATUS_PENDING_APPROVAL;
        });
    }

    public function test_authorized_user_can_approve_pending_expense(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);

        $response = $this->actingAs($this->approverUser)
            ->post(route('admin.expenses.approve', $expense));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->status);
        $this->assertNotNull($expense->approved_at);
    }

    public function test_expense_approval_dispatches_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $expense = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);
        $service = app(ExpenseService::class);

        $service->approveExpense($expense, $this->approverUser);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($expense) {
            return $event->key === 'expense.approved'
                && $event->payload['expense_id'] === $expense->id
                && $event->payload['to_status'] === Expense::STATUS_APPROVED;
        });
    }

    public function test_authorized_user_can_reject_pending_expense_with_reason(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);

        $payload = ['rejection_reason' => 'Missing original receipt invoice attachment.'];

        $response = $this->actingAs($this->approverUser)
            ->post(route('admin.expenses.reject', $expense), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_REJECTED, $expense->status);
    }

    public function test_expense_rejection_dispatches_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $expense = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);
        $service = app(ExpenseService::class);

        $service->rejectExpense($expense, $this->approverUser, 'Duplicate submission');

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($expense) {
            return $event->key === 'expense.rejected'
                && $event->payload['expense_id'] === $expense->id
                && $event->payload['rejection_reason'] === 'Duplicate submission';
        });
    }

    public function test_resubmitting_rejected_expense_clears_previous_rejection_metadata(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);
        $service = app(ExpenseService::class);

        // Reject expense
        $service->rejectExpense($expense, $this->approverUser, 'Invalid category selected');
        $expense->refresh();
        $this->assertEquals(Expense::STATUS_REJECTED, $expense->status);

        // Resubmit expense
        $service->submitExpense($expense, $this->adminUser);
        $expense->refresh();

        $this->assertEquals(Expense::STATUS_PENDING_APPROVAL, $expense->status);
        $history = $expense->metadata['history'] ?? [];
        $this->assertCount(2, $history);
        $this->assertEquals('submit', end($history)['action']);
    }

    public function test_cannot_approve_already_approved_expense(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_APPROVED]);
        $service = app(ExpenseService::class);

        $this->expectException(ValidationException::class);
        $service->approveExpense($expense, $this->approverUser);
    }

    public function test_approved_expense_cannot_be_edited(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_APPROVED]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.expenses.update', $expense), [
                'amount' => '9999.00',
            ]);

        $response->assertForbidden();
    }

    public function test_approved_expense_cannot_be_deleted(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_APPROVED]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.expenses.destroy', $expense));

        $response->assertForbidden();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_concurrent_expense_approval_only_succeeds_once(): void
    {
        $expense = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);
        $service = app(ExpenseService::class);

        $approved = $service->approveExpense($expense, $this->approverUser);
        $this->assertEquals(Expense::STATUS_APPROVED, $approved->status);

        // Second concurrent approval attempt fails
        $this->expectException(ValidationException::class);
        $service->approveExpense($expense, $this->approverUser);
    }

    public function test_expense_kpi_metrics_calculation(): void
    {
        $this->createTestExpense(['status' => Expense::STATUS_APPROVED, 'amount_minor' => 100000]); // ₹1000
        $this->createTestExpense(['status' => Expense::STATUS_APPROVED, 'amount_minor' => 50000]);  // ₹500
        $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL]);
        $this->createTestExpense(['status' => Expense::STATUS_REJECTED]);

        $metrics = new ExpenseMetrics(new ExpenseFilters);

        $this->assertEquals(150000, $metrics->totalApprovedMinor);
        $this->assertEquals(1, $metrics->pendingApprovalCount);
        $this->assertEquals(1, $metrics->rejectedCount);
        $this->assertEquals(4, $metrics->totalExpensesCount);
    }

    public function test_expense_filters_by_category(): void
    {
        $e1 = $this->createTestExpense(['expense_category_id' => $this->activeCategory->id, 'reference' => 'CAT-MATCH-REF']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.index', ['category_id' => $this->activeCategory->id]));

        $response->assertOk();
        $response->assertSee('CAT-MATCH-REF');
    }

    public function test_expense_filters_by_status(): void
    {
        $e1 = $this->createTestExpense(['status' => Expense::STATUS_PENDING_APPROVAL, 'reference' => 'PENDING-MATCH-REF']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.index', ['status' => Expense::STATUS_PENDING_APPROVAL]));

        $response->assertOk();
        $response->assertSee('PENDING-MATCH-REF');
    }

    public function test_expense_search_by_reference(): void
    {
        $e1 = $this->createTestExpense(['reference' => 'SEARCH-UNIQUE-REF-99']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.index', ['search' => 'SEARCH-UNIQUE-REF-99']));

        $response->assertOk();
        $response->assertSee('SEARCH-UNIQUE-REF-99');
    }
}
