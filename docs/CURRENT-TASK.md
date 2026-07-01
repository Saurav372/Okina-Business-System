# Current Task

## Current Parent Task

C6.2 Notification implementation

## Current Subtask

C6.2.4 Notification log and delivery-attempt operations view

## Current Status

Not Started. C6.2.3 is completed and committed.

## Goal

Expose admin endpoints to list and view `NotificationLog` records and their child `NotificationDeliveryAttempt` details, gated by policy-based role permissions.

## Dependencies

- C6.2.1 Notification persistence and migration
- C6.2.3 Queued notification dispatch and channel delivery

## Required Deliverables

1. **Policy Gate (`App\Policies\NotificationLogPolicy`)**:
   - Gated by authorization rules. Requires `notifications.view` permission slug for `viewAny` and `view` actions.
   - Registers the policy in `AppServiceProvider`.
2. **Controller (`App\Http\Controllers\Admin\NotificationLogController`)**:
   - `index` action: Paginated list (1 to 100 limit, default 20), filtered by:
     - `channel`
     - `status`
     - `recipient_address`
     - `event_type`
     - `recipient_user_id`
     - `recipient_customer_id`
   - Eager loads relations `deliveryAttempts` to prevent N+1 queries.
   - `show` action: Exposes the full details of a specific log, including nested delivery attempts.
3. **Route Registration**:
   - `GET /admin/notification-logs` -> `NotificationLogController@index`
   - `GET /admin/notification-logs/{notification_log}` -> `NotificationLogController@show`
   - Under auth/dashboard.access middleware.
4. **Feature Tests**:
   - `tests/Feature/NotificationLogViewingTest.php` verifying:
     - Authentication/authorization gate restrictions (e.g. guests redirect/reject, roles with `notifications.view` pass, others return 403).
     - Query filters and bounds validation.
     - Nested delivery attempts returned in detailed view.

## Acceptance Criteria

- Operations endpoints are properly policy gated.
- Paginated listing with filtering and detailed shows work correctly.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration/Feature tests for log listing, pagination bounds, filters, detail shows, and role access verification.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `app/Policies/NotificationLogPolicy.php` (new policy)
- `app/Http/Controllers/Admin/NotificationLogController.php` (new controller)
- `routes/web.php` (route updates)
- `app/Providers/AppServiceProvider.php` (policy registration)
- `tests/Feature/NotificationLogViewingTest.php` (new test file)

## Tasks Not Included

- Channel notification isolation and database regression tests (C6.2.5).
