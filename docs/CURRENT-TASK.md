# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C3.1 CRM lead module

## Current Subtask

C3.1.6 Notes and activity timeline

## Current Status

Not Started. C3.1.5 is completed and verified.

## Goal

Allow staff to add freeform notes to a lead, expose the lead's activity timeline in chronological order (including system-generated `status_change` and `assignment` entries from C3.1.5), and ensure internal notes are never visible in customer-facing APIs.

## Dependencies

- C3.1.1 Lead data model and safe migration (Completed)
- C3.1.5 Lead lifecycle, ownership, and assignment (Completed — `LeadActivity` model exists)
- A2.3 Role and permission model (Completed)

## Required Deliverables

1. **Note creation endpoint**: `POST /admin/leads/{lead}/activities` — accepts `activity_type` (defaulting to `note`), `body`, and optional `subject`. Only types that are staff-creatable (`note`, `call`, `email`, `whatsapp`) are allowed through this endpoint. Gated by `leads.manage` permission.
2. **Activity timeline endpoint**: `GET /admin/leads/{lead}/activities` — returns all `LeadActivity` rows for a lead in chronological `occurred_at` order as a public-safe array. Gated by `leads.manage` permission.
3. **Customer-visibility guard**: The `customer_visible` flag on `LeadActivity` is `false` by default. No customer-facing API must expose activity entries.
4. **Feature tests**: `tests/Feature/LeadActivityTimelineTest.php` covering note creation, timeline retrieval order, authorization, and customer-visibility safety.

## Acceptance Criteria

- Staff can create a `note` (and other staff-initiated types) on a lead.
- Attempting to create a system-only type (e.g. `status_change`, `assignment`) through the public endpoint is rejected.
- Timeline returns entries in chronological order including system-generated entries.
- Unauthenticated/unauthorized requests return 401/403.
- No `lead_activities` data is exposed through any customer-facing route.

## Tests Required

- `tests/Feature/LeadActivityTimelineTest.php`
- Run via `php artisan test --filter=LeadActivityTimelineTest`

## Quality Requirements

- Enforce standard Laravel conventions.
- Keep controller lean; use `LeadActivity` model helpers where possible.
- Apply Laravel Pint formatting.

## Files Likely Affected

- `app/Http/Controllers/Admin/LeadActivityController.php` [NEW]
- `app/Http/Requests/Lead/StoreLeadActivityRequest.php` [NEW]
- `routes/web.php`
- `tests/Feature/LeadActivityTimelineTest.php` [NEW]

## Tasks Not Included

- Customer-facing note or timeline views (not planned for customer APIs).
- Quotation-related activity types (`quotation_created`, `quotation_sent`, etc.) — deferred to CRM quotation tasks.
- Full Filament CRM list/detail views (deferred to C3.1.7).