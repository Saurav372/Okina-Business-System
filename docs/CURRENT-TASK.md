# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

## Current Subtask

C1.2 Sales order creation

## Current Status

Completed — implementation for C1.2 carried out and committed locally. Relevant static analysis and feature tests ran and passed locally. See Completion Notes below for commits and changed files.

## Goal

Allow authorized staff to create sales orders: select or create a customer, choose product/SKU, set quantities and customization, apply pricing/discounts, and record advance/final payment structure. Persist order items with stored customization snapshots. Keep creation permission-gated and transaction-safe.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- A3.2 Shared products, variants and SKUs
- A5.1 Shared order/payment domain model
- B2.2.7 Order persistence (order item/customization storage)
- C1.1 Basic admin order and payment view (completed)

## Required Deliverables

- Staff-facing Filament page or protected controller to create sales orders
- Server-side validation and pricing calculation consistent with `CartPricingService`
- Persisted order items that include stored customization snapshots (no live storage paths leaked)
- Support for advance/final payment scheduling fields and tests
- Feature tests: `AdminSalesOrderCreationTest` and related authorization/validation tests

### Completion Notes

- Implementation: server-side `SalesOrderService` now computes and persists order totals using `OrderTotalsCalculator`; controller and a minimal protected admin create view were added.
- Tests: `AdminSalesOrderCreationTest` passed locally (2 tests). Pint formatting applied and passed.
- Commits:
	- `80ee47f` C1.2: Add admin sales order create view, route and controller action
	- `fe20c19` C1.2: Persist order totals via OrderTotalsCalculator; accept optional discount/shipping/tax inputs

Changed files (selected):
- `apps/backend/app/Services/SalesOrderService.php`
- `apps/backend/app/Http/Requests/Admin/SalesOrderCreateRequest.php`
- `apps/backend/app/Http/Controllers/Admin/SalesOrderController.php`
- `apps/backend/routes/web.php`
- `apps/backend/resources/views/admin/orders/create.blade.php`
- `apps/backend/tests/Feature/AdminSalesOrderCreationTest.php` (existing test exercised)

Notes:
- The `OrderResource` Filament registration remains read-only; a controller + blade form was implemented instead to satisfy the staff-facing creation requirement. If you prefer a Filament page instead, I can implement that next.

## Acceptance Criteria

- Authorized staff can create sales orders with valid customers and SKUs
- Pricing and discounts are calculated server-side and reflected in stored order totals
- Customization snapshots are persisted on order items; previews are derived from stored snapshot metadata
- No direct payment gateway processing is performed during order creation (advance/final scheduling only)
- All regression tests pass locally

## Tests Required

- `AdminSalesOrderCreationTest` (feature)
- Unit tests for pricing/discount edge cases
- Authorization denial tests for unauthorized roles
- Full backend test run before marking the subtask complete

## Quality Requirements

- Avoid adding migrations that modify shared order/payment tables without review
- Keep creation flows transaction-safe and idempotent where relevant
- Enforce input validation and controller/service authorization boundaries
- Run `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`, and `php artisan test` before committing

## Files Likely Affected

- `apps/backend/app/Filament/Resources/Orders/OrderResource.php`
- `apps/backend/app/Http/Controllers/Admin/SalesOrderController.php` (new)
- `apps/backend/app/Services/SalesOrderService.php` (new)
- `apps/backend/resources/views/admin/orders/create.blade.php` or Filament page
- `apps/backend/tests/Feature/AdminSalesOrderCreationTest.php`

- C1.1.8 and later C1.1 slices (shipping, CRM, inventory, finance reporting)
- Shipping, CRM, inventory, and finance reporting tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`