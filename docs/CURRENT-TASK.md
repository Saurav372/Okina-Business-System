# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1.3 Read-only order detail and customer/address snapshots

## Current Status

Phase C documentation readiness was completed on 2026-06-20.

C1.1.2 was completed on 2026-06-20. C1.1.3 is the next unblocked subtask and requires explicit implementation approval.

## Goal

Create the read-only order detail surface for the admin order view, including stored customer and address snapshots from the order record. Do not add payment/refund history, shipping, inventory, CRM, or finance reporting.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- B3.1.6 Pending-order creation
- B3.1 Cart and checkout completion evidence
- B3.3 Payment webhook completion evidence
- C1.1.1 Admin order resource and authorization boundary
- C1.1.2 Website order index, scopes, and filters

## Required Deliverables

- Read-only order detail source for website orders
- Stored customer and address snapshot presentation from the order record
- Focused detail-view tests

## Acceptance Criteria

- Only users with the approved order-view permission can reach the detail surface.
- The detail surface remains read-only with no write actions.
- Customer and address snapshots render from the stored order snapshots, not live relation lookups.
- Shared order and payment records are unchanged.
- C1.1.4 through C1.1.6 remain out of scope.

## Tests Required

- Admin resource access tests
- Order detail and snapshot tests
- Authorization denial tests
- Shared order/payment regression tests

## Quality Requirements

- Keep payment secrets and sensitive finance fields restricted.
- Do not create a migration or modify shared order/payment data.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/Filament/Resources/Orders/OrderResource.php`
- `apps/backend/app/Support/Admin/OrderIndexCatalog.php`
- `apps/backend/tests/Feature/AdminOrderIndexTest.php`
- `apps/backend/tests/Feature/AdminOrderDetailTest.php`
- `apps/backend/app/Support/Admin/OrderDetailCatalog.php`
- Related order summary or snapshot helpers if a new shared helper is required

## Tasks Not Included

- C1.1.4 and later C1.1 slices
- Shipping, CRM, inventory, payment, refund, and finance tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`
