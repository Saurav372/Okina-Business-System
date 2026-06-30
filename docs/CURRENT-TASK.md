# Current Task

## Current Parent Task

C3.2 Follow-up workflow

## Current Subtask

C3.2.3 Due-today and overdue staff views

## Current Status

Not Started. C3.2.2 (Create, reschedule, complete, and cancel follow-ups) is fully completed, verified, and committed.

## Goal

Implement the API endpoints, controller index action, query parameter filtering, sorting, pagination, and feature tests to surface due-today and overdue follow-ups for staff.

## Dependencies

- C3.2.1 Lead Follow-up Data Model
- C3.2.2 Create, reschedule, complete, and cancel follow-ups

## Required Deliverables

1. Route definition and controller action for listing lead follow-ups (e.g. `GET /admin/follow-ups` or `GET /admin/leads/follow-ups`).
2. Query parameter filtering support for status (e.g. pending, completed), scope (e.g. overdue, due_today), and assignment (e.g. assigned to current user).
3. Pagination and default descending order by `due_at`.
4. Authorization logic ensuring staff can view follow-ups.
5. Feature tests checking filtering, due today, overdue, pagination, and role-based visibility.

## Acceptance Criteria

- Staff can list follow-ups filtered by `status` (e.g., pending, completed).
- Staff can request overdue follow-ups (where status is pending and `due_at` is in the past).
- Staff can request due-today follow-ups (where status is pending and `due_at` is within the current day).
- Results are paginated and sorted descending by `due_at` by default.
- Access is restricted to users with `leads.view` or `leads.manage` permissions.
- Exposes structured JSON responses using the defined nested user relation objects formatting.

## Tests Required

- List retrieval filtering by status.
- Filter overdue and due-today follow-ups (using `Carbon::setTestNow` to ensure timezone/time robustness).
- Filter by assignee (`assigned_to_user_id`).
- Pagination and sorting verification.
- Gated authorization (unauthorized users get 403).

## Quality Requirements

- Relationship eager loading (`assignedTo`, `completedBy`, `createdBy`) to prevent N+1 queries.
- Clean Laravel Pint code formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `routes/web.php`
- `app/Http/Controllers/Admin/LeadFollowUpController.php`
- `tests/Feature/LeadFollowUpViewsTest.php`

## Tasks Not Included

- Reminder event scheduling/notifications (C3.2.4).
- Lead activity timeline integration (C3.2.5).