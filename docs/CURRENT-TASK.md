# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C3.1 CRM lead module

## Current Subtask

C3.1.4 Source, referrer, page, and UTM attribution storage

## Current Status

Not Started. C3.1.3 (Website/bulk lead capture endpoint) is completed. We now need to verify and consolidate the UTM and referrer/page attribution storage and rules.

## Goal

Ensure that UTM fields (`utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`), `referrer_url`, and `landing_page_url` are validated, stored in the database, excluded from public-facing customer API responses, but fully retrievable for internal/admin surfaces.

## Dependencies

- C3.1.1 Lead data model and safe migration (Completed)
- C3.1.3 Website/bulk lead capture endpoint (Completed)

## Required Deliverables

1. **Attribution Validation & Storage Check**: Verify that all attribution fields are stored accurately in the `leads` database table upon public submission.
2. **Exclusion Check**: Verify that public/guest API endpoints do not leak these attribution fields in JSON responses.
3. **Feature/Unit Tests**: Consolidate tests in `tests/Feature/WebsiteLeadCaptureTest.php` and `tests/Unit/LeadModelTest.php` to thoroughly assert attribution validation rules, storage correctness, and client-side exclusion.

## Acceptance Criteria

- Public/website bulk lead capture validates and persists UTM parameters and referrer/landing page URLs in the database.
- Public responses for lead creation completely exclude UTM and page attribution fields.
- Attempting to pass extremely long or invalid format strings into attribution fields is rejected by validation (422).

## Tests Required

- `tests/Feature/WebsiteLeadCaptureTest.php`
- Run via `php artisan test --filter=WebsiteLeadCaptureTest`

## Quality Requirements

- String length limits for UTM values: max 120 (source/medium) or 160 (campaign/content/term).
- URL validation for referrer and landing page URLs: max 2048 characters.
- Apply Laravel Pint formatting.

## Files Likely Affected

- `apps/backend/app/Http/Requests/Lead/StorePublicLeadRequest.php`
- `apps/backend/tests/Feature/WebsiteLeadCaptureTest.php`
- `apps/backend/tests/Unit/LeadModelTest.php`