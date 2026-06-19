# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.6 Pending-order creation

## Current Status

B3.1.5 Bulk quantity detection was completed on 2026-06-19.

B3.1.6 Pending-order creation is now the active subtask.

## Goal

Create a backend pending order from the validated checkout state before payment attempt creation.

## Dependencies

- A5.1.4-A5.1.5 Shared order/payment domain model
- B3.1.1-B3.1.4 Cart storage, cart item validation, price recalculation, and customer/address validation

## Required Deliverables

- Backend pending-order creation
- Persistent pending order records created before payment attempts
- Checkout-to-order handoff payloads
- Test coverage for successful pending-order creation

## Acceptance Criteria

- Checkout creates a pending order before any payment attempt is started.
- The pending order reflects the validated customer, cart, pricing, and address state.
- Direct checkout flow can hand off cleanly to the next payment step.
- Later order item storage can reuse the pending-order record.

## Tests Required

- Pending-order creation tests
- Checkout handoff tests
- Order record shape tests

## Quality Requirements

- Follow existing Laravel and shared order conventions.
- Do not start order item/customization storage, payment attempts, duplicate checkout prevention, or failed checkout handling inside this subtask.
- Keep checkout-to-order handoff data public-safe where exposed.
- Reuse existing cart, customer, and pricing services instead of creating a parallel checkout model.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

- Order item/customization storage
- Payment-attempt creation
- Duplicate checkout prevention
- Failed checkout handling
- Payment webhook handling

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`
- `docs/orders-order-items-schema.md`
- `docs/feature-list.md`

Use `docs/AI-PROMPT-SEQUENCE.md` when deciding whether to inspect, implement, or review.
