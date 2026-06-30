# Current Task

## Current Parent Task

C3.2 Follow-up workflow

## Current Subtask

C3.2.1 Follow-up data model and ownership rules

## Current Status

Not Started. C2.2 (Vendors and purchases) is fully completed, verified, and committed.

## Goal

Implement the database migration, Eloquent model, relationships, factory, and unit tests for lead follow-ups (`lead_follow_ups` table) in accordance with the CRM and Quotations Schema Plan.

## Dependencies

- C3.1 CRM Lead Module
- A2.3 Role and Permission Model

## Required Deliverables

1. A database migration creating the `lead_follow_ups` table with composite indexes and constraints.
2. `App\Models\LeadFollowUp` Eloquent model with relations, backed enums, and local query scopes.
3. `Database\Factories\LeadFollowUpFactory` with state states.
4. Unit/Feature tests in `tests/Unit/LeadFollowUpTest.php` or `tests/Feature/LeadFollowUpTest.php`.

## Acceptance Criteria

- Migration runs and rolls back cleanly without data leakage or index duplication.
- `LeadFollowUp` supports statuses: `pending`, `completed`, `snoozed`, `cancelled`.
- Model has working relations: `lead`, `assignedTo`, `completedBy`, `createdBy`.
- Database enforces FK constraints on lead and user relations.
- Enforces unique nullable constraint on `notification_key`.
- Local query scopes for `pending`, `completed`, `overdue`, and `dueToday` are defined.

## Tests Required

- Migration schema and constraint tests.
- Model relationship and scope verification.
- Factory states verification.

## Quality Requirements

- No N+1 query vulnerability.
- Clean Laravel Pint code formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `app/Models/LeadFollowUp.php`
- `database/migrations/*_create_lead_follow_ups_table.php`
- `database/factories/LeadFollowUpFactory.php`
- `tests/Feature/LeadFollowUpTest.php`

## Tasks Not Included

- API controller routes or form request validations for creating/rescheduling/completing/cancelling follow-ups (C3.2.2).
- UI or dashboard lists for staff due-today or overdue follow-ups (C3.2.3).
- Scheduler logic or queued notifications (C3.2.4).