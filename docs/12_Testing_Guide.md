# Testing Guide

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** `apps/backend/tests/`, `apps/backend/phpunit.xml`, `apps/backend/phpstan.neon`, `apps/backend/pint.json`

---

## Test Suite Overview

| Metric | Value |
|---|---|
| Total tests | 792 |
| Total assertions | ~4,000+ |
| Test type | Feature tests (database-backed, `RefreshDatabase`) |
| Test database | SQLite (in-memory, configured in `phpunit.xml`) |
| Framework | PHPUnit 12 |

All tests live in `apps/backend/tests/Feature/`. There are no separate unit test files — business logic is tested through feature tests that exercise the full HTTP or service layer.

---

## Running Tests

### Full test suite
```powershell
cd apps/backend
php artisan test
# or
composer test
```

### Single test file
```powershell
php artisan test --filter=FinanceReportTest
```

### Single test method
```powershell
php artisan test --filter="test_outstanding_balance_excludes_cancelled_orders"
```

### Parallel (optional, if supported)
```powershell
php artisan test --parallel
```

---

## Code Style — Laravel Pint

Pint enforces the project's PHP code style (PSR-12 based with Laravel conventions).

### Check only (no changes)
```powershell
./vendor/bin/pint --test
```

### Auto-fix
```powershell
./vendor/bin/pint
```

### Fix specific files
```powershell
./vendor/bin/pint app/Services/FinanceReportService.php tests/Feature/FinanceReportTest.php
```

Configuration: `apps/backend/pint.json`

---

## Static Analysis — PHPStan

PHPStan performs static type analysis. The project must pass at the configured level with zero errors before any task is considered complete.

### Run analysis
```powershell
./vendor/bin/phpstan analyse
```

### Run on specific paths
```powershell
./vendor/bin/phpstan analyse app/Services/ app/Models/
```

Configuration: `apps/backend/phpstan.neon`

---

## Test Database

Tests use SQLite in-memory mode. This is configured in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

`RefreshDatabase` is used in every feature test — the database is fully migrated and rolled back for each test class, ensuring complete isolation.

---

## Test Patterns Used in This Codebase

### Creating test data with specific timestamps

Laravel's `created_at` is not in the `$fillable` array. To seed historical data:

```php
// Wrong — created_at is silently discarded
Payment::create(['created_at' => now()->subMonths(3), ...]);

// Correct — set directly and save
$payment = new Payment([...]);
$payment->created_at = now()->subMonths(3);
$payment->save();
```

### Qualifying columns in joins

When joining multiple tables that share column names (e.g. `status`), always qualify:

```php
->where('orders.status', 'confirmed')  // not just ->where('status', ...)
```

### Testing rollback behavior

```php
while (Schema::hasTable('table_name')) {
    $this->artisan('migrate:rollback', ['--step' => 1]);
}
```

### Acting as a specific user role

```php
$user = User::factory()->create();
$user->assignRole('finance_staff');
$this->actingAs($user, 'web')
     ->getJson('/admin/finance/report')
     ->assertOk();
```

---

## Quality Gates

Before any task is marked complete, all three quality gates must pass:

| Gate | Command | Passing State |
|---|---|---|
| Tests | `php artisan test` | All tests pass, zero failures |
| Code style | `./vendor/bin/pint --test` | Zero style violations |
| Static analysis | `./vendor/bin/phpstan analyse` | Zero errors |

---

## CI Integration

For CI environments (GitHub Actions, etc.):

```yaml
- name: Run tests
  run: |
    cd apps/backend
    composer install --no-interaction
    cp .env.example .env
    php artisan key:generate
    php artisan test --no-interaction

- name: Check code style
  run: cd apps/backend && ./vendor/bin/pint --test

- name: Static analysis
  run: cd apps/backend && ./vendor/bin/phpstan analyse
```

---

## Test Coverage by Module

| Module | Test File | Tests |
|---|---|---|
| Auth / Permissions | `FinanceBoundaryTest`, `FinanceAccessPolicyTest` | 8 |
| Orders | `AdminOrderDetailTest`, `OrderAuditingTest` | 12 |
| Payments | `ManualPaymentRecordingTest`, `PaymentStatusRecalculationIntegrationTest` | 9 |
| Refunds | `RefundRequestTest`, `RefundApprovalTest`, `PartialRefundTest`, `RefundPaymentRecordTest` | 24+ |
| Finance Ledger | `FinanceLedgerFilterTest` | 6 |
| Finance Reports | `FinanceReportTest` | 7 |
| Expenses | `ExpenseCategoryTest`, `ExpenseTest` | 45+ |
| Expense Reports | `ExpenseReportingTest` | 6 |
| Inventory | `InventoryItemTest`, `InventoryStockInTest`, `InventoryStockOutTest`, etc. | 42+ |
| Vendors | `VendorManagementTest`, `PurchaseOrderCreationTest`, etc. | 35+ |
| Audit | `AuditTableDesignTest`, `OrderAuditingTest`, `AuditEventIntegrationsTest`, `AuditSensitiveDataMaskingTest`, `AuditViewingPermissionsTest`, `AuditRetentionTest` | 35+ |
| Notifications | `NotificationSchemaTest`, `NotificationTemplateRenderingTest`, `NotificationDeliveryTest`, `NotificationLogViewingTest`, `NotificationIsolationTest` | 30+ |
| Google Sheets | `GoogleSheetsConnectionTest`, `GoogleSheetsPayloadMappingTest`, `GoogleSheetsSyncPipelineTest`, `GoogleSheetsSyncLogTest` | 24+ |
| Backup / Security | `BackupRestoreTest`, `SecurityReviewTest` | 9 |
| CRM | `LeadManagementTest`, `QuotationTest` | 20+ |
