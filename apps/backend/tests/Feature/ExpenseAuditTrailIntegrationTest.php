<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\ExpenseAttachmentService;
use App\Services\ExpenseService;
use App\Services\ExpenseWorkflowService;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAuditTrailIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $approverUser;

    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

        $this->adminUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole(Role::ADMIN);

        $this->approverUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->approverUser->assignRole(Role::FINANCE_STAFF);

        $this->category = ExpenseCategory::create([
            'name' => 'Logistics Freight',
            'code' => 'EXP_FREIGHT_01',
            'description' => 'Freight logistics expenses',
            'is_active' => true,
        ]);
    }

    public function test_committed_expense_mutation_dispatches_audit_event_once(): void
    {
        Event::fake([AuditEvent::class]);

        $service = app(ExpenseService::class);
        $workflow = app(ExpenseWorkflowService::class);

        // Create expense
        $expense = $service->createExpense([
            'expense_category_public_id' => $this->category->public_id,
            'amount_minor' => 15000,
            'currency' => 'INR',
            'occurred_at' => '2026-08-01',
            'status' => Expense::STATUS_DRAFT,
        ], $this->adminUser);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($expense) {
            return $event->key === 'expenses.created'
                && $event->payload['expense_id'] === $expense->id;
        });

        // Submit expense
        $workflow->submit($expense, $this->adminUser);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($expense) {
            return $event->key === 'expenses.submitted'
                && $event->payload['expense_id'] === $expense->id
                && $event->payload['to_status'] === Expense::STATUS_PENDING_APPROVAL;
        });
    }

    public function test_rolled_back_expense_transaction_does_not_dispatch_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        try {
            DB::transaction(function () {
                $expense = Expense::create([
                    'expense_category_id' => $this->category->id,
                    'amount_minor' => 50000,
                    'currency' => 'INR',
                    'occurred_at' => '2026-08-01',
                    'status' => Expense::STATUS_DRAFT,
                    'recorded_by_user_id' => $this->adminUser->id,
                ]);

                DB::afterCommit(function () use ($expense) {
                    event(new AuditEvent('expenses.created', $this->adminUser, [
                        'expense_id' => $expense->id,
                    ]));
                });

                // Force exception to rollback transaction
                throw new \Exception('Simulated database failure during transaction');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        Event::assertNotDispatched(AuditEvent::class);
    }

    public function test_proof_attachment_replacement_cleans_up_old_file_and_dispatches_event(): void
    {
        Storage::fake('local');
        Event::fake([AuditEvent::class]);

        $expense = Expense::create([
            'expense_category_id' => $this->category->id,
            'amount_minor' => 20000,
            'currency' => 'INR',
            'occurred_at' => '2026-08-01',
            'status' => Expense::STATUS_DRAFT,
            'recorded_by_user_id' => $this->adminUser->id,
        ]);

        $attachmentService = app(ExpenseAttachmentService::class);

        // First upload
        $file1 = UploadedFile::fake()->create('receipt1.pdf', 100, 'application/pdf');
        $attachment1 = $attachmentService->attachProof($expense, $file1, $this->adminUser);

        Storage::disk('local')->assertExists($attachment1->storage_path);
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'expense_attachments.created';
        });

        // Replacement upload
        $file2 = UploadedFile::fake()->create('receipt2.pdf', 150, 'application/pdf');
        $attachment2 = $attachmentService->attachProof($expense, $file2, $this->adminUser);

        Storage::disk('local')->assertExists($attachment2->storage_path);
        Storage::disk('local')->assertMissing($attachment1->storage_path);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($attachment1, $attachment2) {
            return $event->key === 'expense_attachments.replaced'
                && $event->payload['old_attachment_id'] === $attachment1->id
                && $event->payload['new_attachment_id'] === $attachment2->id;
        });
    }
}
