# Current Task

## Current Parent Task

C6.3 Google Sheets backup sync

## Current Subtask

C6.3.1 Sheets connection configuration and access boundary

## Current Status

Not Started. Parent task C6.2 is completed and committed.

## Goal

Implement connection settings, credentials storage, and access boundaries for Google Sheets integration, ensuring that configuration is secure, properly validated, and segregated.

## Dependencies

- C6.2 Notification implementation (Completed)

## Required Deliverables

1. **Configuration File (`config/sheets.php`)**:
   - Define Google Sheets integration credentials structure (client email, private key, spreadsheet ID, and sheets/tabs mapping).
2. **Connection & Access Boundaries**:
   - Implement `GoogleSheetsClient` / connection service to handle Google API credentials resolution.
   - Enforce access boundaries: configuration settings are protected, and integration secrets are stored securely and never leaked.
   - Implement connectivity check helper/methods.
3. **Tests (`tests/Feature/GoogleSheetsConnectionTest.php`)**:
   - Verify config resolution, connectivity check mocks, and policy/access gates.

## Acceptance Criteria

- Configuration is safely resolved and connection is validatable.
- Policy gates properly restrict access to Google Sheets settings.
- All tests pass cleanly.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration/Feature tests for config loading, connectivity check helper, and access policies in `tests/Feature/GoogleSheetsConnectionTest.php`.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `config/sheets.php` (new config)
- `app/Support/GoogleSheets/GoogleSheetsClient.php` (new client service)
- `tests/Feature/GoogleSheetsConnectionTest.php` (new test file)

## Tasks Not Included

- Per-record mapping (C6.3.2).
- Google Sheets queued sync pipeline (C6.3.3).
