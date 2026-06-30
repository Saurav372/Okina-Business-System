# Current Task

## Current Parent Task

C6.1 Immutable audit log

## Current Subtask

C6.1.5 Audit viewing permissions

## Current Status

Not Started. C6.1.1, C6.1.2, C6.1.3, and C6.1.4 are fully completed, verified, and committed.

## Goal

Define and enforce role-based access control (RBAC) gates for retrieving or viewing the immutable audit logs. Ensure only staff members with the explicit permission (e.g. `audit.view` or `audit.manage`) can view, search, or inspect logs.

## Dependencies

- C6.1.1 Audit table design
- A2.3 Role and permission model

## Required Deliverables

1. Audit viewing authorization gates:
   - A policy or gate check (e.g., `AuditLogPolicy` mapped to `AuditLog`) enforcing viewing permissions.
   - Integration with endpoints or Filament resources exposing audit logs.
2. Feature/integration tests verifying:
   - Staff with authorized permissions can access/view audit log resources.
   - Unauthorized staff (e.g., generic staff, unauthorized roles) receive 403 Forbidden.
   - Guests/unauthenticated requests receive 401 Unauthorized.

## Acceptance Criteria

- All audit log retrieval is protected by authorization checks.
- Zero authorization leaks (no unprivileged user can read any audit log content).
- Test coverage ensures all staff roles are validated against the access matrix.
- Pint formatting and PHPStan static analysis pass with zero errors.

## Tests Required

- Integration/Feature tests verifying role-based access control on audit viewing endpoints.
- Authorization matrix validation for Super Admin, Admin, and other staff roles.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `app/Policies/AuditLogPolicy.php` (new policy)
- `app/Providers/AppServiceProvider.php` (policy registration if needed)
- `tests/Feature/AuditViewingPermissionsTest.php` (new test file)

## Tasks Not Included

- Retention rules (C6.1.6).
- Google Sheets sync (C6.3).
