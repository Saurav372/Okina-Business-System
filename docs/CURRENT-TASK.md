# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3 Cart, checkout and website payment

## Current Subtask

B3.2 Website payment adapter implementation

## Current Status

B3.1.10 Failed checkout handling was completed on 2026-06-20.

B3.2 Website payment adapter implementation is now the active subtask.

## Goal

Use the shared payment service and gateway adapter to initiate and verify website payment for the pending order created by B3.1.

## Dependencies

- A5.3 Payment gateway service contract
- B3.1 Cart and checkout with pending order creation

## Required Deliverables

- Website payment initiation flow for a pending order
- Shared payment-service and gateway-adapter integration
- Public-safe payment attempt response behavior
- Test coverage for payment attempt creation and gateway handoff

## Acceptance Criteria

- Website payment starts through the shared gateway contract, not a hardcoded provider call.
- The pending order from B3.1 can be handed off to the gateway adapter.
- Payment attempt state remains traceable and public-safe.
- Failure handling can feed into later webhook processing.

## Tests Required

- Payment attempt tests
- Gateway adapter contract tests
- Checkout-to-payment handoff regression tests

## Quality Requirements

- Keep gateway logic provider-neutral.
- Do not start B3.3 webhook handling or refund work.
- Keep responses public-safe.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/tests/**`
- payment adapter classes

## Tasks Not Included

- Payment webhook handling
- Refund processing
- Finance reconciliation
- Notifications

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/AI-PROMPT-SEQUENCE.md`
