# Current Task

## Current Parent Task

C6.2 Notification implementation

## Current Subtask

C6.2.1 Notification persistence and migration

## Current Status

Not Started. C6.1 Parent Task is completed and verified.

## Goal

Create database migrations and Eloquent models for notification templates, notification logs, and notification delivery attempts, enforcing constraints, indexes, and relationships as defined in the schema design.

## Dependencies

- None (core database architecture is already set up).

## Required Deliverables

1. **Database Migrations**:
   - Create tables: `notification_templates`, `notification_logs`, `notification_delivery_attempts` with all designated columns, indexes, foreign keys, and CHECK/UNIQUE constraints.
2. **Eloquent Models**:
   - `NotificationTemplate` with status and channel casts, user relationships, and default timestamp attributes.
   - `NotificationLog` with status, recipient_type, channel casts, and relationships to templates, users, and customers.
   - `NotificationDeliveryAttempt` with relationships and status/response snapshots.
3. **Automated Tests**:
   - `tests/Feature/NotificationSchemaTest.php` verifying schema structure, unique constraints (e.g. template key/channel/locale/version and dedupe keys), relationships, and migrations up/down clean execution.

## Acceptance Criteria

- Database tables are successfully created with correct indexes and constraints.
- Pint formatting and PHPStan analysis pass with zero errors.

## Tests Required

- Integration tests verifying schema creation, model relationships, and constraint handling.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `database/migrations/[timestamp]_create_notifications_tables.php` (new migration)
- `app/Models/NotificationTemplate.php` (new model)
- `app/Models/NotificationLog.php` (new model)
- `app/Models/NotificationDeliveryAttempt.php` (new model)
- `tests/Feature/NotificationSchemaTest.php` (new test file)

## Tasks Not Included

- Template variable rendering, queued job dispatch listeners, and third-party delivery adapters (C6.2.2 - C6.2.5).
