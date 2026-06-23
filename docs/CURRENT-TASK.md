# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C3.1 CRM lead module

## Current Subtask

C3.1.7 Lead authorization, list/detail views, and regression tests

## Current Status

Not Started. C3.1.6 is completed and verified.

## Goal

Ensure only authorized staff roles can list, view, create, assign, and update leads. The lead list view must return paginated public-safe summaries, and the detail view must return full details for the lead. All CRM module tests must pass to verify complete regression safety.

## Dependencies

- A2.3 Role and permission model (Completed)
- C3.1.1 Lead data model and safe migration (Completed)
- C3.1.5 Lead lifecycle, ownership, and assignment (Completed)
- C3.1.6 Notes and activity timeline (Completed)

## Required Deliverables

1. **Lead listing endpoint**: `GET /admin/leads` — returns paginated list of leads with safe summary fields. Gated by `leads.view` or `leads.manage` permission.
2. **Lead detail endpoint**: `GET /admin/leads/{lead}` — returns full detail view of a single lead. Gated by `leads.view` or `leads.manage` permission.
3. **Authorization checks**: Gated via `LeadPolicy` (e.g. `viewAny`, `view`). Unauthorized users must receive HTTP 403, and guest requests must get HTTP 401.
4. **Feature tests**: `tests/Feature/LeadDetailListTest.php` covering listing, detail retrieval, pagination, public-safe responses, and authorization checks.

## Acceptance Criteria

- Staff with `leads.view` or `leads.manage` permissions can retrieve paginated lead list and individual lead details.
- Unauthorized requests receive 403 Forbidden. Unauthenticated requests receive 401 Unauthorized.
- Responses are public safe and do not expose internal database IDs (`id`, `customer_id`, `created_by_user_id`).
- All tests in the CRM lead module pass successfully.

## Tests Required

- `tests/Feature/LeadDetailListTest.php`
- Run via `php artisan test --filter=LeadDetailListTest`

## Quality Requirements

- Enforce standard Laravel conventions.
- Keep controller lean.
- Apply Laravel Pint formatting.

## Files Likely Affected

- `app/Http/Controllers/Admin/LeadController.php`
- `routes/web.php`
- `tests/Feature/LeadDetailListTest.php` [NEW]