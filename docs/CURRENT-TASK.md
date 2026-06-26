# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.3 Expense management

## Current Subtask

C5.3.1 Expense categories

## Current Status

Not Started. C5.2 Parent Task is fully completed, verified, and committed.

## Next Subtask

C5.3.2 Expense entry

## Goal

Ensure that expense categories are defined, stored in the database, and manageable by authorized users (finance staff/admin) as part of the backend database schema and admin API endpoints.

## Dependencies

- C5.1 Finance payment and balance views (Completed)

## Required Deliverables

- Database migration for `expense_categories` table (fields: id, public_id, name, code, description, is_active, created_at, updated_at).
- ExpenseCategory model and factory.
- Seeders for default expense categories (e.g. shipping, raw_materials, marketing, printing_supplies, utilities, other).
- Admin API endpoints to list, show, create, update, and toggle active status of expense categories (guarded by policies and appropriate roles/permissions).
- Form requests for input validation.

## Acceptance Criteria

- Expense categories must have a name, code (unique slug), description, and active status.
- Only authorized users with `finance.manage` or `expenses.manage` or similar permissions can create/edit categories.
- Public IDs are generated for public references.
- Categories can be soft-deleted or marked inactive instead of hard deleted if referenced.

## Tests Required

- Automated feature tests asserting CRUD operations and role-based permissions protection.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Models/ExpenseCategory.php`
- `database/migrations/..._create_expense_categories_table.php`
- `app/Http/Controllers/Admin/ExpenseCategoryController.php`
- `tests/Feature/ExpenseCategoryTest.php`

## Tasks Not Included

- Expense entries linking to these categories (handled in C5.3.2).