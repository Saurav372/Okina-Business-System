# Current Task

## Current Parent Task

C6.4 Backup, security, and regression gates

## Current Subtask

C6.4.2 Comprehensive application security review

## Current Status

Not Started. C6.4.1 (Backup and restore implementation) is fully completed and verified by tests. Ready to begin C6.4.2.

## Goal

Implement a backup and restore utility that archives the SQL database and private uploads together, along with a validation/restore routine that successfully restores the application state.

## Dependencies

- A1.1 — Core database schema (Completed)
- A4.1 — File upload service (Completed)

## Required Deliverables

1. **Backup Command/Service**:
   - An artisan command (e.g. `system:backup` or `backup:run`) that exports the database (SQL dump) and archives it together with the private storage files (from `storage/app/private` or the configured disk) into a single zip/tarball file.
   - Cleans up old backup archives to prevent disk exhaustion.

2. **Restore Command/Service**:
   - An artisan command (e.g. `system:restore` or `backup:restore`) that takes a backup archive, restores the database schema and data, and restores the private uploads to their correct directory.
   - Restores the application to a fully working, verified state.

3. **Verification Tests**:
   - Automated tests simulating the backup and restore process.
   - Verify that data inserted before backup exists after restore, and uploaded files are correctly restored and accessible.

## Acceptance Criteria

- The backup process compiles the database and file storage into a single portable archive.
- The restore process cleanly resets the current state and restores the archived state perfectly.
- Handled safely in the test environment (using SQLite memory/file databases and local storage).

## Tests Required

- `tests/Feature/BackupRestoreTest.php`:
  - Verify `backup` command successfully creates a single archive containing database and files.
  - Verify `restore` command successfully restores data and files.
  - Verify system integrity after restore.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan static analysis with zero errors.

## Files Likely Affected

- `app/Console/Commands/BackupSystem.php` (new)
- `app/Console/Commands/RestoreSystem.php` (new)
- `tests/Feature/BackupRestoreTest.php` (new)
