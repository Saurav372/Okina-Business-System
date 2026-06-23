# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C3.1 CRM lead module

## Current Subtask

C3.1.3 Website/bulk lead capture endpoint

## Current Status

Not Started. C3.1.2 (Manual lead capture) is completed. We now need to implement the public website bulk enquiry endpoint where prospective customers can submit their contact details, UTM attribution data, and custom printing requirements, which gets captured as a lead in the backend database.

## Goal

Create a public API endpoint allowing guests on the customer-facing website to submit bulk enquiries. The endpoint must validate contact information, support UTM and attribution fields, perform spam/duplicate submission checks within a short window, write to the `leads` table with source `website_bulk_enquiry` and status `new`, and return a public-safe response.

## Dependencies

- C3.1.1 Lead data model and safe migration (Completed)
- C3.1.2 Manual lead capture (Completed)

## Required Deliverables

1. **Public Route**: Register `POST /catalog/leads` (or similar prefix) in `routes/api.php` under public/guest access.
2. **Form Request**: Create `app/Http/Requests/Lead/StorePublicLeadRequest.php` validating source (must be `website_bulk_enquiry`), contact name, email, phone, details, and optional UTM fields.
3. **Controller**: Create `app/Http/Controllers/Api/PublicLeadController.php` with a `store` method.
4. **Duplicate Prevention**: Implement duplicate checking logic (e.g. check for identical contact email/phone and product interest submitted within the last 5 minutes) to return 429 or custom duplicate warning instead of inserting duplicate rows.
5. **Feature Test**: Create `tests/Feature/WebsiteLeadCaptureTest.php` to verify successful public capture, validation failures, duplicate submission blocking, and public-safe response shape.

## Acceptance Criteria

- `POST /api/catalog/leads` (or matching public route) with valid payload creates a lead record and returns HTTP 201 with public-safe fields.
- The public response does not expose internal numeric IDs.
- Submitting the same request payload (email, phone, product interest) within 5 minutes returns a validation or throttle failure (e.g., HTTP 429 or 422 with a clear duplicate message).
- Standard guest requests do not require authentication or permissions.

## Tests Required

- `tests/Feature/WebsiteLeadCaptureTest.php`
- Run via `php artisan test --filter=WebsiteLeadCaptureTest`

## Quality Requirements

- Enforce standard Laravel validations.
- Apply Laravel Pint formatting.
- Exclude internal database IDs.

## Files Likely Affected

- `apps/backend/routes/api.php` (new route)
- `apps/backend/app/Http/Controllers/Api/PublicLeadController.php` (new)
- `apps/backend/app/Http/Requests/Lead/StorePublicLeadRequest.php` (new)
- `apps/backend/tests/Feature/WebsiteLeadCaptureTest.php` (new)

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/crm-quotations-schema.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`