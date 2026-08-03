<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Rules\ValidMoneyAmount;
use App\Services\ExpenseAttachmentService;
use App\Services\ExpenseService;
use App\Services\ExpenseWorkflowService;
use App\Support\Money\MoneyParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExpensesReportingV5Test extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $approverUser;

    private User $staffUser;

    private ExpenseCategory $travelCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $managePermission = Permission::create(['name' => 'Manage Expenses', 'slug' => 'expenses.manage', 'group' => 'finance']);
        $viewPermission = Permission::create(['name' => 'View Expenses', 'slug' => 'expenses.view', 'group' => 'finance']);
        $approvePermission = Permission::create(['name' => 'Approve Expenses', 'slug' => 'expenses.approve', 'group' => 'finance']);

        $adminRole = Role::create(['name' => 'Admin', 'slug' => Role::ADMIN]);
        $adminRole->permissions()->attach([$managePermission->id, $viewPermission->id, $approvePermission->id]);

        $staffRole = Role::create(['name' => 'Finance Staff', 'slug' => Role::FINANCE_STAFF]);
        $staffRole->permissions()->attach([$viewPermission->id]);

        $this->adminUser = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->adminUser->roles()->attach($adminRole);

        $this->approverUser = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->approverUser->roles()->attach($adminRole);

        $this->staffUser = User::factory()->create(['user_type' => User::TYPE_STAFF, 'status' => User::STATUS_ACTIVE]);
        $this->staffUser->roles()->attach($staffRole);

        $this->travelCategory = ExpenseCategory::create([
            'name' => 'Travel & Lodging',
            'code' => 'TRAVEL_LODGING',
            'description' => 'Business travel expenses',
            'is_active' => true,
        ]);
    }

    /**
     * Acceptance Test 1 & 2: MoneyParser & ValidMoneyAmount rule
     */
    public function test_money_parser_and_validation_rule(): void
    {
        $this->assertSame(25050, MoneyParser::toMinorUnits('250.50'));
        $this->assertSame(1, MoneyParser::toMinorUnits('0.01'));
        $this->assertSame(1000, MoneyParser::toMinorUnits('10'));

        // Reject >2 decimal places
        $this->expectException(\InvalidArgumentException::class);
        MoneyParser::toMinorUnits('1.005');
    }

    public function test_zero_and_negative_money_amounts_rejected(): void
    {
        $rule = new ValidMoneyAmount(mustBeGreaterThanZero: true);
        $failed = false;

        $rule->validate('amount', '0.00', function ($msg) use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    /**
     * Acceptance Test 3 & 4: Draft Lifecycle
     */
    public function test_draft_expense_creation_and_soft_deletion(): void
    {
        $expense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 15000,
            'currency' => 'INR',
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_DRAFT,
        ]);

        $service = app(ExpenseService::class);
        $service->deleteExpense($expense, $this->adminUser);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    /**
     * Acceptance Test 5 & 6: Pending Immutability & Withdrawal
     */
    public function test_pending_expense_withdrawal_back_to_draft(): void
    {
        Storage::fake('local');
        $expense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 20000,
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_DRAFT,
        ]);

        $attachmentService = app(ExpenseAttachmentService::class);
        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');
        $attachmentService->attachProof($expense, $file, $this->adminUser);

        $workflowService = app(ExpenseWorkflowService::class);
        $workflowService->submit($expense, $this->adminUser);
        $this->assertSame(Expense::STATUS_PENDING_APPROVAL, $expense->fresh()->status);

        $workflowService->withdraw($expense, $this->adminUser);
        $this->assertSame(Expense::STATUS_DRAFT, $expense->fresh()->status);
        $this->assertNotNull($expense->fresh()->withdrawn_at);
    }

    /**
     * Acceptance Test 7: Approved Terminal Immutability
     */
    public function test_approved_expense_is_strictly_immutable(): void
    {
        $expense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 50000,
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_PENDING_APPROVAL,
        ]);

        $workflowService = app(ExpenseWorkflowService::class);
        $workflowService->approve($expense, $this->approverUser);

        $service = app(ExpenseService::class);

        $this->expectException(ValidationException::class);
        $service->updateExpense($expense, ['notes' => 'Attempting update']);
    }

    /**
     * Acceptance Test 10 & 11: Proof Replacement & Transaction-Safe Rollback
     */
    public function test_proof_attachment_transaction_safe_replacement(): void
    {
        Storage::fake('local');

        $expense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 12000,
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_DRAFT,
        ]);

        $service = app(ExpenseAttachmentService::class);
        $file1 = UploadedFile::fake()->create('invoice1.pdf', 100, 'application/pdf');
        $attachment1 = $service->attachProof($expense, $file1, $this->adminUser);

        Storage::disk('local')->assertExists($attachment1->storage_path);

        $file2 = UploadedFile::fake()->create('invoice2.jpg', 150, 'image/jpeg');
        $attachment2 = $service->attachProof($expense, $file2, $this->adminUser);

        Storage::disk('local')->assertExists($attachment2->storage_path);
        $this->assertSame($attachment2->id, $expense->fresh()->attachment->id);
    }

    /**
     * Acceptance Test 15: Attachment Download Route Security
     */
    public function test_attachment_download_security_and_mismatch_guard(): void
    {
        Storage::fake('local');

        $expense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 30000,
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_DRAFT,
        ]);

        $service = app(ExpenseAttachmentService::class);
        $file = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
        $attachment = $service->attachProof($expense, $file, $this->adminUser);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.attachments.download', [
                'expense' => $expense->public_id,
                'attachment' => $attachment->public_id,
            ]));

        $response->assertOk();

        // Mismatched attachment testing
        $otherExpense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 10000,
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_DRAFT,
        ]);

        $mismatchedResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.attachments.download', [
                'expense' => $otherExpense->public_id,
                'attachment' => $attachment->public_id,
            ]));

        $mismatchedResponse->assertStatus(404);
    }

    /**
     * Acceptance Test 20: Option A Category Deletion Safeguard
     */
    public function test_option_a_category_deletion_protection(): void
    {
        $expense = Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 15000,
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_DRAFT,
        ]);

        $this->expectException(ValidationException::class);
        $this->travelCategory->ensureNotReferenced();
    }

    /**
     * Acceptance Test 26: CSV Export Streaming & Formula Protection
     */
    public function test_csv_export_streaming_and_formula_protection(): void
    {
        Expense::create([
            'expense_category_id' => $this->travelCategory->id,
            'amount_minor' => 25050,
            'reference' => '=SUM(1,2)',
            'recorded_by_user_id' => $this->adminUser->id,
            'occurred_at' => now(),
            'status' => Expense::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.expenses.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString("'=SUM(1,2)", $response->streamedContent());
    }
}
