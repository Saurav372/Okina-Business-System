# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.3 Expense management

## Current Subtask

C5.3.3 Expense approval rules

## Current Status

Not Started. C5.3.2 Expense entry is fully completed, verified, and committed.

## Next Subtask

C5.3.4 Expense permissions

## Goal

Implement status transition rules, approval policies, action endpoints, and feature tests for submitting, approving, and rejecting recorded expenses.

## Dependencies

- C5.3.2 Expense entry (Completed)
- A2.3 Role and permission model (Completed)

## Required Deliverables

- Update the `Expense` model with status transition constraints, guards, and action methods (e.g. submit, approve, reject).
- Action endpoints to submit, approve, and reject expenses.
- ExpensePolicy rules checking role-based transition rights.
- Form requests or actions to handle transition details (like reason for rejection).

## Acceptance Criteria

- Expenses begin in `'draft'` state.
- Transition rules are strictly enforced:
  - `draft` can be submitted to become `pending_approval`.
  - `pending_approval` can be approved to become `approved` OR rejected to become `rejected`.
  - `rejected` can be submitted to become `pending_approval`.
  - `approved` is a terminal state and cannot be changed.
  - Invalid transitions are rejected at both controller/API level (HTTP 422) and domain model layer (LogicException).
- Only authorized users with appropriate permissions (e.g., `'finance.approve_expenses'` for approving/rejecting, and `'finance.manage_expenses'` for submitting/drafting) can perform these actions.
- Action history (who transitioned, when, and reasons if any) should be recorded.

## Tests Required

- Feature tests verifying valid state transitions (`draft` -> `pending_approval` -> `approved` / `rejected`).
- Feature tests verifying invalid state transitions are blocked (e.g. `approved` -> `draft` or `rejected` -> `approved`).
- Feature tests verifying role-based permission gates on submit, approve, and reject endpoints.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Models/Expense.php`
- `app/Policies/ExpensePolicy.php`
- `app/Http/Controllers/Admin/ExpenseController.php`
- `tests/Feature/ExpenseTest.php`

## Tasks Not Included

- Expense permissions restrictions (handled in C5.3.4).
- Expense reporting data (handled in C5.3.5).