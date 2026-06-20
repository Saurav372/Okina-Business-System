# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1.7 Authorized admin design-file access bridge

## Current Status

Phase C documentation readiness was completed on 2026-06-20.

C1.1.5 was completed on 2026-06-20. C1.1.7 is the next unblocked subtask and is approved to proceed.

## Goal

Provide an authorized admin design-file access bridge so Filament admin users with the correct permission can view order-linked design uploads and their public-safe previews. Ensure access uses signed, time-limited URLs or a permission-checked proxy endpoint so private storage paths and originals remain protected. Keep the admin order detail surface strictly read-only and verify no write mutations are possible from file access paths.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- A4.1 File upload service (private storage + signed preview flow)
- B2.2.6/7 Customization snapshot persistence and order item storage
- C1.1.3 Read-only order detail and customer/address snapshots
- C1.1.5 Order item and customization snapshot presentation

## Required Deliverables

- Secure admin file-access bridge that delivers public-safe previews for design files linked to orders (signed URLs or permission-checked proxy)
- Permission gating so only authorized staff (e.g. `files.view` or `orders.view`) can request previews
- Regression test suite verifying file access does not expose private storage paths or allow file-mutation (download of originals must require elevated privileges)
- Confirmation that `items` and `customization_snapshot` still render solely from stored `OrderItem` records and that preview links are generated from snapshots, not live storage paths
- Updated tests: `AdminDesignFileAccessTest`, enhancements to `AdminOrderDetailTest` to assert preview links and permission checks

## Acceptance Criteria

- Only users with the approved `orders.view` and/or `files.view` permission can request preview access for design files from the admin UI.
- Preview links served to the admin are time-limited signed URLs or served through a permission-checked proxy endpoint that never reveals raw private storage paths.
- No file upload, modification, or deletion is possible from the admin preview surface; attempts to mutate must return 403.
- `items` and `customization_snapshot` continue to render solely from stored `OrderItem` records; preview generation uses stored snapshot metadata.
- Regression tests for permission gating and file-access privacy pass locally.

## Tests Required

- Admin file-access tests (permission gating for preview/proxy endpoints)
- Detail-view regression tests asserting no write actions and safe preview rendering
- Order detail and item snapshot tests (assert preview links generated from sanitized snapshot metadata)
- Authorization denial tests for attempted file/mutation requests
- Full backend test run before completion

## Quality Requirements

- Keep payment secrets and sensitive finance fields restricted.
- Do not create a migration or modify shared order/payment data.
- Ensure the scope guard is enforced at both Filament registration and controller/service boundaries.
- Run targeted and full backend tests after changes.

## Files Likely Affected

- `apps/backend/app/Filament/Resources/Orders/OrderResource.php`
- `apps/backend/app/Support/Admin/OrderDetailCatalog.php`
- `apps/backend/app/Services/FileAccessService.php` (new or existing proxy)
- `apps/backend/app/Support/Products/CustomizationSnapshotBuilder.php` (preview link helper)
- `apps/backend/tests/Feature/AdminDesignFileAccessTest.php`
- `apps/backend/tests/Feature/AdminOrderDetailTest.php` (enhancements)
- Related order item or customization helpers if a shared presenter is adjusted

## Tasks Not Included

- C1.1.8 and later C1.1 slices (shipping, CRM, inventory, finance reporting)
- Shipping, CRM, inventory, and finance reporting tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`