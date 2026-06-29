# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.2 Vendors and purchases

## Current Subtask

C2.2.1 Vendor management

## Current Status

Completed. C2.2.1 Vendor management is fully completed, verified, and committed.

## Next Subtask

C2.2.2 Purchase order creation

## Goal

Implement the database schema, Eloquent model, policies, request validation, controller endpoints, and tests for managing vendors/suppliers. Authorized staff should be able to create, edit, show, list, and soft-delete/toggle statuses of vendors safely, while protecting procurement data from public/unauthorized access.

## Dependencies

- A2.3 Role and permission model (Completed)
- C2.1 Inventory movements and stock handling (Completed)

## Required Deliverables

- Create database migration for the `vendors` table matching the schema details in `inventory-vendors-purchases-schema.md` (unique `vendor_code`, `name`, `status`, contact details, `country_code` defaulting to 'IN', user tracking fields `created_by_user_id`/`updated_by_user_id`, timestamps, and soft deletes `deleted_at`).
- Create `App\Models\Vendor` Eloquent model with automatic `vendor_code` generation (e.g. prefix `VND-` followed by random uppercase characters), status enum casting (`active`, `inactive`, `blocked`), user tracking relations, and soft deletes.
- Create `App\Policies\VendorPolicy` guarding access based on `vendors.manage` or role permissions.
- Implement REST API controller `App\Http\Controllers\Admin\VendorController` under the admin route group prefix `/admin/vendors`.
- Implement request validation requests `StoreVendorRequest` and `UpdateVendorRequest` verifying name, uniqueness of code, formats of email/phone, and allowed status enums.
- Create `tests/Feature/VendorManagementTest.php` to validate:
  - Unauthorized roles are denied access (403).
  - Authorized roles can list (with paginated response), show, create (with auto-generated code if not provided), update, and toggle vendor statuses.
  - Deleting a vendor performs soft deletion.

## Acceptance Criteria

- Vendors can be successfully listed, created, updated, and soft-deleted/status-toggled by authorized roles.
- Non-authorized staff are strictly blocked with 403 Forbidden.
- Vendor code is guaranteed unique and matches format constraints.

## Tests Required

- Feature tests in `VendorManagementTest` verifying:
  - Role-based authorization gates.
  - CRUD operations and input validation constraints.
  - Unique code generation and integrity checks.
  - Soft deletion and status toggles.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `database/migrations/2026_06_29_000000_create_vendors_table.php` (new)
- `app/Models/Vendor.php` (new)
- `app/Policies/VendorPolicy.php` (new)
- `app/Http/Controllers/Admin/VendorController.php` (new)
- `routes/web.php`
- `tests/Feature/VendorManagementTest.php` (new)