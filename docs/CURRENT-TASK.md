# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.7 Order item/customization storage

## Current Status

B3.1.6 Pending-order creation was completed on 2026-06-19.

B3.1.7 Order item/customization storage is now the active subtask.

## Goal

Store validated cart items and customization snapshots on the pending order so the order record can carry item-level history before payment attempt creation.

## Dependencies

- B2.2.6 Cart persistence
- B3.1.6 Pending-order creation

## Required Deliverables

- Backend order item/customization storage
- Order item records linked to the pending order
- Item snapshots for SKU, quantity, price, and customization
- Test coverage for order item storage and customization persistence

## Acceptance Criteria

- Pending orders persist item rows before payment attempts begin.
- Order item rows preserve SKU, price, quantity, and customization snapshots.
- The stored item data is safe for later admin, payment, and tracking flows.
- Later order and file bridge tasks can reuse the persisted item structure.

## Tests Required

- Order item storage tests
- Customization snapshot persistence tests
- Pending order handoff regression tests

## Quality Requirements

- Follow existing Laravel and shared order conventions.
- Do not start payment attempts, duplicate checkout prevention, or failed checkout handling inside this subtask.
- Keep any exposed item data public-safe where exposed.
- Reuse the validated cart and pending-order record instead of rebuilding order data from scratch.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/database/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

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