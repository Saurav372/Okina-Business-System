# Current Task

## Current Parent Task

C6.2 Notification implementation

## Current Subtask

C6.2.2 Template management and safe variable rendering

## Current Status

Not Started. C6.2.1 is completed and committed.

## Goal

Implement template management and a safe rendering service that resolves placeholders (e.g. `{{ variable }}`) in subjects and bodies, restricting variables to a whitelist (`allowed_variables`) and sanitizing any sensitive data to prevent security leaks.

## Dependencies

- C6.2.1 Notification persistence and migration

## Required Deliverables

1. **Notification Rendering Engine**:
   - A service `App\Support\Notifications\NotificationRenderer` that accepts a template (`NotificationTemplate`) and a key-value payload.
   - Validates/filters payload variables against the template's `allowed_variables` whitelist.
   - Replaces placeholders in the format `{{ variable }}` in both `subject_template` and `body_template`.
   - Recursively masks or strips sensitive fields (e.g. keys containing `password`, `token`, `secret`, `cvv`, `card`) present in the payload.
2. **Feature Tests**:
   - `tests/Feature/NotificationTemplateRenderingTest.php` verifying:
     - Successful placeholder replacement in subject and body.
     - Filtering of variables not present in `allowed_variables`.
     - Strict sanitization of sensitive payload keys.
     - Graceful handling of missing placeholders.

## Acceptance Criteria

- Placeholder rendering works correctly.
- Variable whitelisting and sensitive data sanitization prevent leaks.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration/Unit tests for the template rendering service verifying whitelisting, placeholder resolution, and sanitization.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `app/Support/Notifications/NotificationRenderer.php` (new service)
- `tests/Feature/NotificationTemplateRenderingTest.php` (new test file)

## Tasks Not Included

- Dispatch listeners, delivery queue workers, and provider adapters (C6.2.3 - C6.2.5).
