# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.3 Expense management

## Current Subtask

C5.3.5 Expense reporting data

## Current Status

Not Started. C5.3.4 Expense permissions is fully completed, verified, and committed.

## Next Subtask

C5.4.1 Report scopes, date ranges, and authorization policy

## Goal

Ensure that expense entries are properly aggregated and available for financial reports. Expose optimized query scopes or a service layer that allows retrieval of expense totals grouped by category, status, and date range, enforcing correct role-based permission boundaries.

## Dependencies

- C5.3.1 Expense categories (Completed)
- C5.3.2 Expense entry (Completed)
- C5.3.3 Expense approval rules (Completed)
- C5.3.4 Expense permissions (Completed)

## Required Deliverables

- Implement query scopes or query methods on the `Expense` model/service to fetch expense summaries (e.g., total amount by category, total amount by status, monthly/daily trends within a date range).
- Expose these summaries through an authorized endpoint or admin reporting catalog helper, ensuring only users with `finance.view_reports` or `finance.manage_expenses` (or relevant roles like Super Admin, Admin, and Finance Staff) can access them.
- Add or expand tests asserting that the reporting data aggregates correctly (calculating correct decimal sums, respecting date ranges, and category filters).
- Verify that N+1 queries are prevented when loading categories for the report grouping.

## Acceptance Criteria

- Reporting queries must allow filtering by `start_date`, `end_date`, `status`, and `expense_category_id`.
- The aggregate response must return correct sums rounded properly (standard 2 decimal places).
- Unauthorized roles (Sales, Inventory, Production Staff) must be blocked from accessing expense report queries/endpoints (returning `403`).
- Eager loading of categories is enforced to prevent N+1 query regressions during aggregation.

## Tests Required

- Integration/feature tests for expense reporting queries/endpoints.
- Tests validating calculation accuracy of aggregated amounts under various filters (date range, status, categories).
- Permission matrix tests verifying only authorized roles can retrieve the report aggregates.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Models/Expense.php`
- `app/Policies/ExpensePolicy.php`
- `tests/Feature/ExpenseReportingTest.php` (new or expanded)

## Tasks Not Included

- Rendering full graphical charts (handled in the frontend / UI tasks).
- Broad financial reconciliation reports (handled in C5.4).