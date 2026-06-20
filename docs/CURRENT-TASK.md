# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1.4 Payment, refund, and payment-attempt history

## Current Status

Phase C documentation readiness was completed on 2026-06-20.

C1.1.3 was completed on 2026-06-20. C1.1.4 is the next unblocked subtask and requires explicit implementation approval.

## Goal

Create the read-only payment, refund, and payment-attempt history surface for the admin order detail view. Do not add shipping, inventory, CRM, or finance reporting.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- A5.1 Shared order/payment domain model
- A5.2 Cancellation and refund rules
- A5.3 Payment gateway service contract
- B3.1 Cart and checkout completion evidence
- B3.3 Payment webhook completion evidence
- C1.1.1 Admin order resource and authorization boundary
- C1.1.2 Website order index, scopes, and filters
- C1.1.3 Read-only order detail and customer/address snapshots

## Required Deliverables

- Read-only payment/refund/attempt history source for the admin order detail page
- Stored payment, refund, and payment-attempt presentation from shared order/payment records
- Focused detail-view tests

## Acceptance Criteria

- Only users with the approved order-view permission can reach the detail surface.
- The detail surface remains read-only with no write actions.
- Payment, refund, and payment-attempt history render from stored records, not live relation lookups.
- Shared order/payment records are unchanged.
- C1.1.5 and later C1.1 slices remain out of scope.

## Tests Required

- Admin resource access tests
- Order detail and history tests
- Authorization denial tests
- Shared order/payment regression tests

## Quality Requirements

- Keep payment secrets and sensitive finance fields restricted.
- Do not create a migration or modify shared order/payment data.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/Filament/Resources/Orders/OrderResource.php`
- `apps/backend/app/Support/Admin/OrderDetailCatalog.php`
- `apps/backend/tests/Feature/AdminOrderDetailTest.php`
- `apps/backend/tests/Feature/AdminOrderResourceBoundaryTest.php`
- Related order/payment detail helpers if a shared presenter is required

## Tasks Not Included

- C1.1.5 and later C1.1 slices
- Shipping, CRM, inventory, and finance reporting tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`