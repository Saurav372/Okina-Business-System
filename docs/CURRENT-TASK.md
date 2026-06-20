# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.10 Failed checkout handling

## Current Status

B3.1.9 Duplicate checkout prevention was completed on 2026-06-20.

B3.1.10 Failed checkout handling is now the active subtask.

## Goal

Ensure an unsuccessful checkout handoff leaves a traceable pending order and payment attempt without creating duplicate records or starting payment gateway processing.

## Dependencies

- B3.1.6 Pending-order creation
- B3.1.8 Payment-attempt creation
- B3.1.9 Duplicate checkout prevention

## Required Deliverables

- Traceable failed-checkout state handling for the existing pending order and payment attempt
- Public-safe failure response behavior
- Test coverage for the failed-checkout path and retry safety

## Acceptance Criteria

- A failed checkout handoff leaves the pending order and payment attempt traceable.
- The failure path does not create duplicate orders or payment attempts.
- The checkout response remains public-safe and actionable for the customer.
- Later payment-adapter and webhook tasks can build on the recorded failure state.

## Tests Required

- Failed checkout path tests
- Duplicate/retry safety tests
- Pending order and payment-attempt regression tests

## Quality Requirements

- Reuse the existing pending order, payment-attempt, and idempotency rules.
- Do not implement payment gateway initiation, webhook processing, refunds, or notification delivery in this subtask.
- Keep checkout responses public-safe where exposed.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

- Payment gateway initiation
- Payment webhook handling
- Refund processing
- Notification delivery

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`
- `docs/feature-list.md`

Use `docs/AI-PROMPT-SEQUENCE.md` when deciding whether to inspect, implement, or review.