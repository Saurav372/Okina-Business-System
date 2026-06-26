# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.3 Expense management

## Current Subtask

C5.3.2 Expense entry

## Current Status

Not Started. C5.3.1 Expense categories is fully completed, verified, and committed.

## Next Subtask

C5.3.3 Expense approval rules

## Goal

Implement the database migration, Eloquent model, factories, form requests, admin REST endpoints, policies, and feature tests for recording and managing business expenses.

## Dependencies

- C5.3.1 Expense categories (Completed)
- C5.1 Finance payment and balance views (Completed)

## Required Deliverables

- Database migration for `expenses` table (fields: id, public_id, expense_category_id, amount_minor, currency, notes, recorded_by_user_id, reference, status, occurred_at, created_at, updated_at).
- Expense model and factory.
- Update `ExpenseCategory::isReferenced()` to check for linked expenses.
- Admin API endpoints to list, show, create, update, and delete expenses (protected by policies and role/permission checks).
- Form requests for input validation (e.g. validating category existence and activity status).

## Acceptance Criteria

- Expenses must have a category (only active, non-deleted categories), amount, currency (default to INR), occurred_at date, reference, notes, and status (e.g., draft, pending_approval, approved, rejected).
- Only authorized users with `finance.manage_expenses` or similar permissions can manage expenses.
- Public IDs are generated for public references (e.g., `EXP-[A-Z0-9]{12}`).
- `ExpenseCategory::isReferenced()` must return true if the category has associated expense records, blocking its deletion with validation errors.
- Soft-deleted expense categories cannot be linked to new expenses.

## Tests Required

- Automated feature tests asserting CRUD operations, role-based permissions protection, and relationship validation.
- Assert that deleting a category linked to an expense fails validation.
- Assert that creating an expense under an inactive or soft-deleted category fails validation.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Models/ExpenseCategory.php`
- `app/Models/Expense.php` (New)
- `database/migrations/..._create_expenses_table.php` (New)
- `app/Http/Controllers/Admin/ExpenseController.php` (New)
- `tests/Feature/ExpenseTest.php` (New)

## Tasks Not Included

- Expense approval rules and transitions (handled in C5.3.3).
- Expense permissions and reporting data (handled in C5.3.4 and C5.3.5).