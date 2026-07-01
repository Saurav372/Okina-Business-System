# Current Task

## Current Parent Task

C6.2 Notification implementation

## Current Subtask

C6.2.3 Queued notification dispatch and channel delivery

## Current Status

Not Started. C6.2.2 is completed and committed.

## Goal

Implement the asynchronous queued notification dispatch pipeline, including a queued Job (`SendNotificationJob`), a dispatcher service (`NotificationDispatcher`), and channel delivery adapters (e.g., Log/Mock adapters for `email`, `sms`, `whatsapp`, and `database`) that log attempts and handle failures cleanly without blocking core business transactions.

## Dependencies

- C6.2.1 Notification persistence and migration
- C6.2.2 Template management and safe variable rendering
- A4.3 Queue, job and retry foundation

## Required Deliverables

1. **Dispatcher Service (`App\Support\Notifications\NotificationDispatcher`)**:
   - Creates `NotificationLog` records with status `pending`.
   - Prevents duplicate notification creation using the unique `dedupe_key` constraint (catches unique constraint exceptions or checks early).
   - Dispatches `SendNotificationJob` asynchronously outside the database transaction (using `DB::afterCommit` or Laravel queue rules).
2. **Queued Job (`App\Jobs\SendNotificationJob`)**:
   - Implements `ShouldQueue`.
   - Handles template rendering using `NotificationRenderer`.
   - Routes delivery to the appropriate channel adapter based on `channel`.
   - Wraps delivery in `notification_delivery_attempts` logging (captures success, provider references, error messages, and response payloads).
   - Updates `NotificationLog` status (`sent` or `failed`).
3. **Channel Adapters**:
   - A factory/registry or interface `App\Support\Notifications\Channels\NotificationChannelInterface` with a `send(NotificationLog $log): array` method.
   - Implement lightweight adapters: `EmailChannel`, `SmsChannel`, `WhatsappChannel`, `DatabaseChannel` (writing to a database notification table or local log for development/testing).
4. **Feature Tests**:
   - `tests/Feature/NotificationDeliveryTest.php` verifying:
     - Dispatcher creates pending logs and queues the job.
     - Deduplication key prevents duplicate notifications.
     - Asynchronous queue execution successfully renders templates and delivers to adapters.
     - Delivery attempts are successfully appended to `notification_delivery_attempts`.
     - Log status updates to `sent` on success or `failed` on adapter failure.

## Acceptance Criteria

- Notifications are dispatched asynchronously.
- Log and attempt tables are updated correctly.
- Deduplication key unique constraints are respected.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration tests verifying dispatcher, queue execution, adapter routing, and delivery attempt logging.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `app/Jobs/SendNotificationJob.php` (new job)
- `app/Support/Notifications/NotificationDispatcher.php` (new service)
- `app/Support/Notifications/Channels/NotificationChannelInterface.php` (new interface)
- `app/Support/Notifications/Channels/...` (new adapters)
- `tests/Feature/NotificationDeliveryTest.php` (new test file)

## Tasks Not Included

- Operations admin view for notifications (C6.2.4) and isolation regression tests (C6.2.5).
