# Current Task

## Current Parent Task

C3.2 Follow-up workflow

## Current Subtask

C3.2.2 Create, reschedule, complete, and cancel follow-ups

## Current Status

Not Started. C3.2.1 (Follow-up data model and ownership rules) is fully completed, verified, and committed.

## Goal

Implement the API endpoints, controller actions, request validations, policies, and feature tests for creating, rescheduling, completing, and cancelling lead follow-ups.

## Dependencies

- C3.2.1 Lead Follow-up Data Model

## Required Deliverables

1. Controller and route definitions for CRUD/action operations on follow-ups (e.g. POST `/admin/leads/{lead}/follow-ups`, PATCH `/admin/leads/{lead}/follow-ups/{follow_up}`, POST `/admin/leads/{lead}/follow-ups/{follow_up}/complete`, POST `/admin/leads/{lead}/follow-ups/{follow_up}/cancel`).
2. Request validation classes: `StoreLeadFollowUpRequest` and `UpdateLeadFollowUpRequest` (or unified).
3. Authorization logic (wiring up `LeadFollowUpPolicy` or using `LeadPolicy`).
4. Feature tests verifying creation, status transitions, validation rules, and permission checks.

## Acceptance Criteria

- Staff can create a new follow-up for a lead with a valid future `due_at` date.
- Rescheduling updates the `due_at` timestamp.
- Completing a follow-up transitions status to `completed`, updates `completed_at` to the current timestamp, and sets `completed_by_user_id` to the authenticated user.
- Cancelling transitions status to `cancelled`.
- Restricts actions on completed/cancelled follow-ups (terminal states).
- Unauthorized users are blocked with 403.
- Response payloads are public-safe (omitting internal database IDs).

## Tests Required

- Success/failure paths for creation, rescheduling, completion, and cancellation.
- Request validation tests (e.g., verifying `due_at` cannot be in the past on creation).
- Role/permission protection tests.

## Quality Requirements

- Eager loading relationships to avoid N+1 query risks.
- Clean Laravel Pint code formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `routes/api.php` or `routes/web.php` (admin routes)
- `app/Http/Controllers/Admin/LeadFollowUpController.php`
- `app/Http/Requests/Lead/StoreLeadFollowUpRequest.php`
- `app/Http/Requests/Lead/UpdateLeadFollowUpRequest.php`
- `app/Policies/LeadFollowUpPolicy.php`
- `tests/Feature/LeadFollowUpActionsTest.php`

## Tasks Not Included

- Dashboard views and lists for due-today/overdue follow-ups (C3.2.3).
- Notification scheduling/reminders (C3.2.4).
- Lead activity timeline integration (C3.2.5).