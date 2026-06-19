# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.8 Payment-attempt creation

## Current Status

B3.1.7 Order item/customization storage was completed on 2026-06-19.

B3.1.8 Payment-attempt creation is now the active subtask.

## Goal

Create the first payment-attempt record for a pending order so checkout can hand off to the shared payment flow with a traceable payment link.

## Dependencies

- A5.3.3 Payment attempt contract and rules
- B3.1.6 Pending-order creation

## Required Deliverables

- Backend payment-attempt creation
- Payment attempt record linked to the pending order
- Payment-attempt handoff data for checkout
- Test coverage for linked attempt creation and checkout handoff

## Acceptance Criteria

- Checkout creates or prepares a payment attempt after the pending order is created.
- The payment attempt is linked to the pending order.
- Checkout can hand off to the shared payment flow without duplicating the order.
- Later duplicate-prevention and failure-path tasks can reuse the attempt record.

## Tests Required

- Payment attempt tests
- Checkout handoff regression tests
- Pending order linkage tests

## Quality Requirements

- Follow the shared payment-contract and gateway-agnostic rules.
- Do not implement duplicate checkout prevention, failed checkout handling, or webhook processing inside this subtask.
- Keep exposed payment-attempt data public-safe where exposed.
- Reuse the validated pending order record instead of rebuilding order data from scratch.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/database/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

- Duplicate checkout prevention
- Failed checkout handling
- Payment webhook handling
- Payment reconciliation and refund processing

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`
- `docs/feature-list.md`

Use `docs/AI-PROMPT-SEQUENCE.md` when deciding whether to inspect, implement, or review.