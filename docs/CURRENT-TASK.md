# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1.5 Order item and customization snapshot presentation

## Current Status

Phase C documentation readiness was completed on 2026-06-20.

C1.1.4 was completed on 2026-06-20. C1.1.5 is the next unblocked subtask and requires explicit implementation approval.

## Goal

Create the read-only order item and customization snapshot presentation for the admin order detail view. Do not add payment/refund history, shipping, inventory, CRM, or finance reporting.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- B2.2.7 Order persistence
- B3.1.7 Order item/customization storage
- C1.1.1 Admin order resource and authorization boundary
- C1.1.2 Website order index, scopes, and filters
- C1.1.3 Read-only order detail and customer/address snapshots
- C1.1.4 Payment, refund, and payment-attempt history

## Required Deliverables

- Read-only order item and customization snapshot source for the admin order detail page
- Stored item and customization presentation from shared order items and snapshots
- Focused detail-view tests

## Acceptance Criteria

- Only users with the approved order-view permission can reach the detail surface.
- The detail surface remains read-only with no write actions.
- Order items and customization snapshots render from stored order-item records, not live product/cart lookups.
- Shared order/payment records are unchanged.
- C1.1.6 and later C1.1 slices remain out of scope.

## Tests Required

- Admin resource access tests
- Order detail and item snapshot tests
- Authorization denial tests
- Shared order/item regression tests

## Quality Requirements

- Keep payment secrets and sensitive finance fields restricted.
- Do not create a migration or modify shared order/payment data.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/Filament/Resources/Orders/OrderResource.php`
- `apps/backend/app/Support/Admin/OrderDetailCatalog.php`
- `apps/backend/tests/Feature/AdminOrderDetailTest.php`
- `apps/backend/tests/Feature/AdminOrderResourceBoundaryTest.php`
- Related order item or customization helpers if a shared presenter is required

## Tasks Not Included

- C1.1.6 and later C1.1 slices
- Shipping, CRM, inventory, and finance reporting tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`