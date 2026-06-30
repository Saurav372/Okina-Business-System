# Current Task

## Current Parent Task

C3.2 Follow-up workflow

## Current Subtask

C3.2.6 Follow-up permissions and retry-safe regression tests

## Current Status

Not Started. C3.2.5 (Lead activity timeline integration) is fully completed, verified, and committed.

## Goal

Create a comprehensive permission matrix and regression test suite verifying role-based access control (leads.view and leads.manage) and retry-safety of terminal state transitions.

## Dependencies

- C3.2.1 Lead Follow-up Data Model
- C3.2.2 Create, reschedule, complete, and cancel follow-ups
- C3.2.3 Due-today and overdue staff views
- C3.2.4 Reminder event scheduling and notification handoff
- C3.2.5 Lead activity timeline integration

## Required Deliverables

1. A comprehensive test suite (`tests/Feature/LeadFollowUpPermissionsAndRegressionTest.php`) verifying:
   - **Permission Matrix**: Roles with `leads.manage` can perform all actions; roles with `leads.view` can only list; users without these permissions receive 403; unauthenticated users receive 401.
   - **Retry-Safety / Terminal States**: Verify that calling complete/cancel on already completed/cancelled follow-ups returns 422, preserving terminal states and preventing duplicate activity logs.
   - **Data Leakage Protection**: Verify that unauthorized users receive 403 (or appropriate auth errors) when trying to access endpoints, and check that binding non-owned follow-ups returns 404 (due to scoped bindings).

## Acceptance Criteria

- All permission matrix tests pass cleanly.
- Terminal state mutations return 422 on subsequent retries.
- Unauthenticated requests receive 401.
- Unauthorized requests receive 403.
- Scope route model bindings return 404 on cross-lead access.
- Code matches Pint formatting standards.
- PHPStan analysis contains zero errors.

## Tests Required

- Full role-based authorization matrix tests (Super Admin, Sales Staff, Production/Inventory Staff, Unauthenticated).
- Scoped route model binding mismatch tests (e.g. Lead A with Follow-up B).
- Retry state transition tests (double complete, double cancel, cancel after complete, complete after cancel, update on completed).
- Assertion that blocked operations do not generate duplicate timeline activity logs.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `tests/Feature/LeadFollowUpPermissionsAndRegressionTest.php`

## Tasks Not Included

- Future notification engines (C6.2).
- Sales dashboard UI implementation.