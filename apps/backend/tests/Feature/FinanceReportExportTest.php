<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FinanceReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            ['name' => 'Admin', 'guard_name' => 'web']
        );
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);
    }

    /**
     * Test CSV export headers, BOM, multi-section schema, and formula injection sanitization.
     */
    public function test_csv_export_headers_bom_schema_and_formula_sanitization(): void
    {
        Event::fake([AuditEvent::class]);

        // Create an expense with dangerous formula characters in category code & name
        $category = ExpenseCategory::factory()->create([
            'name' => '=EVAL(1+1)',
            'code' => '+DANGEROUS_CMD',
        ]);
        Expense::factory()->create([
            'expense_category_id' => $category->id,
            'status' => Expense::STATUS_APPROVED,
            'amount_minor' => 15000,
            'occurred_at' => CarbonImmutable::parse('2026-06-10'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/reports/finance/export?start_date=2026-06-01&end_date=2026-06-30');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="finance-report-2026-06-30.csv"', $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        // Verify UTF-8 BOM prefix (\xEF\xBB\xBF)
        $this->assertTrue(str_starts_with($content, "\xEF\xBB\xBF"));

        // Verify multi-section headers & sanitization
        $this->assertStringContainsString('Section,Key/Code,Name/Period', $content);
        $this->assertStringContainsString('Executive Summary', $content);
        $this->assertStringContainsString('Monthly Trend', $content);
        $this->assertStringContainsString('Expense Category', $content);

        // Dangerous characters +DANGEROUS_CMD and =EVAL(1+1) must be sanitized with leading '
        $this->assertStringContainsString('\'+DANGEROUS_CMD', $content);
        $this->assertStringContainsString('\'=EVAL(1+1)', $content);

        // Verify AuditEvent finance_reports.exported was dispatched
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) {
            return $event->key === 'finance_reports.exported'
                && $event->payload['actor_id'] === $this->adminUser->id
                && $event->payload['currency'] === 'INR'
                && isset($event->payload['filename']);
        });
    }
}
