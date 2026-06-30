# Current Task

## Current Parent Task

C6.1 Immutable audit log

## Current Subtask

C6.1.1 Audit table design

## Current Status

Not Started. C3.2 is fully completed, verified, and committed.

## Goal

Create database migrations, Eloquent models, relationships, and unit tests for `audit_logs` and `audit_log_related_records` tables, ensuring they match all security constraints (e.g. append-only, FK cascades, nullability, unique keys, composite query indexes).

## Dependencies

- A1.1.8 Files/audit/notifications schema plan
- A4.6 Audit contracts/interfaces
- C1.1 Basic admin order and payment view

## Required Deliverables

1. Database migration to create the `audit_logs` and `audit_log_related_records` tables.
2. Eloquent models `AuditLog` and `AuditLogRelatedRecord` with appropriate:
   - Relationships (`actorUser`, `actorCustomer`, `relatedRecords`, `auditLog`).
   - Attribute casts (`old_values` => 'array', `new_values` => 'array', `metadata` => 'array', `occurred_at` => 'datetime').
3. Model-level safety constraints:
   - Make the models effectively append-only (prevent update/delete/forceDelete via Eloquent model events or custom methods).
4. Unit/Feature tests checking migration integrity, schema structure, nullability constraints, foreign key configurations, unique constraints, and append-only immutability.

## Acceptance Criteria

- Database migrations run and roll back cleanly.
- `AuditLog` table has unique index on `event_id`, unique nullable index on `idempotency_key`, and foreign keys referencing `users` and `customers`.
- `AuditLogRelatedRecord` has foreign key referencing `audit_logs` with cascade on delete (if audit log is cleaned up, but audit log is append-only).
- Models cast JSON fields to arrays.
- Any attempt to update or delete `AuditLog` or `AuditLogRelatedRecord` via Eloquent throws an exception, preserving immutability.
- Code conforms to Pint formatting.
- PHPStan static analysis passes with zero errors.

## Tests Required

- Migration success and rollback integrity tests.
- Model relationships and cast checks.
- Append-only immutability validation (asserting exceptions are thrown on delete, update, restore, or forceDelete).
- Index and constraint checks.

## Quality Requirements

- Clean Laravel Pint formatting.
- PHPStan analysis with zero errors.

## Files Likely Affected

- `database/migrations/*_create_audit_logs_table.php`
- `app/Models/AuditLog.php`
- `app/Models/AuditLogRelatedRecord.php`
- `tests/Feature/AuditTableDesignTest.php`

## Tasks Not Included

- Hooking up audit listeners to orders, payments, inventory, or customers (handled in subsequent subtasks C6.1.2 - C6.1.6).
- Audit viewing permissions/resource interface (C6.1.8).
- Audit retention job execution (C6.1.9).