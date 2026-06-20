# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1.6 Read-only scope guard and regression verification

## Current Status

Phase C documentation readiness was completed on 2026-06-20.

C1.1.5 was completed on 2026-06-20. C1.1.6 is the next unblocked subtask and requires explicit implementation approval.

## Goal

Ensure the admin order detail surface remains strictly read-only and protected by a scope guard, and perform regression verification that the detail presenter does not permit or perform any write mutations to shared order, payment, refund, shipping, inventory, or file records. Provide focused regression tests that assert the read-only UI and server boundaries and verify item/customization snapshots render from stored data only.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- B2.2.7 Order persistence
- B3.1.7 Order item/customization storage
- C1.1.1 Admin order resource and authorization boundary
- C1.1.2 Website order index, scopes, and filters
- C1.1.3 Read-only order detail and customer/address snapshots
- C1.1.4 Payment, refund, and payment-attempt history
- C1.1.5 Order item and customization snapshot presentation

## Required Deliverables

- Read-only scope guard enforcement for the admin order detail Filament resource and supporting presenter(s)
- Regression test suite verifying no write actions are present or permitted from the admin detail surface (server + UI assertions)
- Confirmation that `items` and `customization_snapshot` render solely from stored `OrderItem` records
- Updated or added tests capturing attempted write-action denial

## Acceptance Criteria

- Only users with the approved `orders.view` permission can reach the detail surface.
- No write actions (create/edit/delete/status/payment/refund/shipping) are available in the Filament resource or callable endpoints from the admin detail surface.
- Server-side scope guard denies unauthorized write attempts with 403 and does not mutate shared order/payment/refund/inventory/file records.
- `items` and `customization_snapshot` values are rendered from stored `OrderItem` records and sanitized for public-safe output.
- Existing shared records remain unchanged by detail-view code paths.
- Tests asserting the above pass locally.

## Tests Required

- Admin resource access tests (permission gating)
- Detail-view regression tests asserting absence/blocking of write actions (server + integration)
- Order detail and item snapshot tests (existing coverage to be extended)
- Authorization denial tests for attempted write requests
- Full backend test run before completion

## Quality Requirements

- Keep payment secrets and sensitive finance fields restricted.
- Do not create a migration or modify shared order/payment data.
- Ensure the scope guard is enforced at both Filament registration and controller/service boundaries.
- Run targeted and full backend tests after changes.

## Files Likely Affected

- `apps/backend/app/Filament/Resources/Orders/OrderResource.php`
- `apps/backend/app/Support/Admin/OrderDetailCatalog.php`
- `apps/backend/app/Support/Admin/AdminResourceCatalog.php`
- `apps/backend/tests/Feature/AdminOrderDetailTest.php`
- `apps/backend/tests/Feature/AdminOrderResourceBoundaryTest.php`
- Related order item or customization helpers if a shared presenter is adjusted

## Tasks Not Included

- C1.1.7 and later C1.1 slices (shipping, CRM, inventory, finance reporting)
- Shipping, CRM, inventory, and finance reporting tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`