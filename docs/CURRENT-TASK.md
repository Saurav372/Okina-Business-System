# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.3 Expense management

## Current Subtask

C5.3.4 Expense permissions

## Current Status

Not Started. C5.3.3 Expense approval rules is fully completed, verified, and committed.

## Next Subtask

C5.3.5 Expense reporting data

## Goal

Ensure that restricted users cannot see protected expense data. Enforce fine-grained permission boundaries so that only authorized roles can read, create, update, delete, or transition expenses.

## Dependencies

- A2.3 Role and permission model (Completed)
- C5.3.2 Expense entry (Completed)
- C5.3.3 Expense approval rules (Completed)

## Required Deliverables

- Audit and harden the existing `ExpensePolicy` to ensure all actions (viewAny, view, create, update, delete, submit, approve, reject) are correctly gated.
- Verify that unauthorized staff roles receive `403` on all expense endpoints.
- Confirm that the `finance.manage_expenses` and `finance.approve_expenses` permission assignments across all roles in `AccessControlSeeder.php` are correct and complete.
- Add or expand tests asserting that each staff role's access boundaries are exactly right (no over-permission, no under-permission).

## Acceptance Criteria

- Only users with `finance.manage_expenses` can: list, show, create, update, delete, and submit expenses.
- Only users with `finance.approve_expenses` can: approve and reject expenses.
- All other roles (Sales Staff, Inventory Staff, Production Staff, etc.) receive `403` on all expense endpoints.
- No expense data leaks to unauthorized users (existence leakage is prevented — no `404` vs `403` discrepancy on expense lookups by public_id).
- Permission grants in `AccessControlSeeder.php` match exactly the roles that should have access.

## Tests Required

- Feature tests for each staff role asserting they can or cannot access each expense endpoint.
- Tests covering: `GET /admin/expenses`, `GET /admin/expenses/{id}`, `POST /admin/expenses`, `PUT /admin/expenses/{id}`, `DELETE /admin/expenses/{id}`, `POST /admin/expenses/{id}/submit`, `POST /admin/expenses/{id}/approve`, `POST /admin/expenses/{id}/reject`.
- Tests confirming that unauthorized users get `403` and cannot distinguish between "not found" and "forbidden" for valid public IDs.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Policies/ExpensePolicy.php`
- `database/seeders/AccessControlSeeder.php`
- `tests/Feature/ExpenseTest.php`

## Tasks Not Included

- Expense reporting data (handled in C5.3.5).
- Audit log integration (handled in C6.1).