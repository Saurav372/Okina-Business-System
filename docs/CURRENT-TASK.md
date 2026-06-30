# Current Task

## Current Parent Task

C6.1 Immutable audit log

## Current Subtask

C6.1.6 Retention rules

## Current Status

Not Started. C6.1.1 through C6.1.5 are fully completed, verified, and committed.

## Goal

Define and implement an audit log retention policy. Provide an automated Artisan command or scheduler job (e.g. `audit:prune`) to safely delete/archive audit logs older than a configurable number of days (e.g. 365 days or custom config value) without exceeding memory limits.

## Dependencies

- C6.1.1 Audit table design

## Required Deliverables

1. Retention policy configuration and command:
   - A configuration variable (e.g. in `config/audit.php` or `settings` service) specifying the retention period in days.
   - An Artisan command `audit:prune` that queries and deletes audit logs older than the retention threshold.
   - Streaming/chunking deletions to keep memory and lock durations low.
2. Integration with scheduler in `routes/console.php`.
3. Feature/Unit tests verifying:
   - Logs older than the retention threshold are successfully deleted.
   - Logs newer than the threshold are strictly preserved.
   - Cascade deletes for `audit_log_related_records` work cleanly (via database constraints or clean deletes).

## Acceptance Criteria

- Old audit logs are automatically pruned according to the retention settings.
- The pruning command uses chunking/lazy execution to protect DB performance.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration tests verifying pruning commands delete the correct database rows.
- Regression tests confirming younger records remain intact.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `app/Console/Commands/PruneAuditLogs.php` (new command)
- `routes/console.php` (schedule definition)
- `tests/Feature/AuditRetentionTest.php` (new test file)

## Tasks Not Included

- Future audit backup/archiving mechanisms (C6.4.1).
