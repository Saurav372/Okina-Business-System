# Current Task

## Current Parent Task

C6.2 Notification implementation

## Current Subtask

C6.2.5 Notification isolation and regression tests

## Current Status

Not Started. C6.2.4 is completed and committed.

## Goal

Verify that notification dispatching is completely isolated from the business transactions, so that notification-related failures (e.g., missing templates, template rendering exceptions, or transport adapter errors) do not roll back or block the parent business transaction. In addition, verify that transaction-safe queueing (`afterCommit`) and unique constraint deduplication prevent duplicate dispatches and deliveries.

## Dependencies

- C6.2.1 Notification persistence and migration
- C6.2.3 Queued notification dispatch and channel delivery
- C6.2.4 Notification log and delivery-attempt operations view

## Required Deliverables

1. **Feature Tests (`tests/Feature/NotificationIsolationTest.php`)**:
   - Verify that when a business transaction publishes an event that triggers a notification, and the template is missing or rendering throws an exception, the business transaction still commits successfully.
   - Verify that when a template has rendering/whitelist errors, the business transaction commits successfully and the notification status is updated to failed/skipped.
   - Verify that when an adapter throws a transport exception during queued delivery execution, the job records the failure and respects retries without rolling back the database state of the attempt.
   - Verify that the `afterCommit` dispatcher hook prevents notifications from being dispatched/sent if the business transaction rolls back.
   - Verify that deduplication logic (via `dedupe_key` unique constraints) successfully prevents duplicate deliveries under concurrent dispatch attempts.

## Acceptance Criteria

- Operations endpoints are properly policy gated.
- Notification pipeline failures must not roll back source transactions.
- All tests pass cleanly.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration/Feature tests for notification dispatch isolation, transaction rollback safety, failure resilience, and concurrent deduplication in `tests/Feature/NotificationIsolationTest.php`.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `tests/Feature/NotificationIsolationTest.php` (new test file)

## Tasks Not Included

- Google Sheets backup sync (C6.3).
