# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3 Cart, checkout and website payment

## Current Subtask

B3.3 Payment webhook handling

## Current Status

B3.2 Website payment adapter implementation was completed on 2026-06-20.

B3.3 Payment webhook handling is now the active subtask.

## Goal

Authenticate and process webhook events for the initiated website payments created in B3.2.

## Dependencies

- A4.5 Idempotency foundation
- A5.3 Payment gateway service contract
- B3.2 Website payment adapter implementation

## Required Deliverables

- Webhook authentication and event parsing
- Payment-attempt matching and single-update behavior
- Public-safe payment-state updates for paid, failed, and refund events
- Test coverage for webhook idempotency and retry logging

## Acceptance Criteria

- Authenticated webhook events update payment state exactly once.
- Duplicate webhook deliveries do not create duplicate payment records.
- Failed and refund events stay public-safe and traceable.
- Retry and logging behavior can support later reconciliation work.

## Tests Required

- Webhook authentication tests
- Event parsing tests
- Payment-attempt matching tests
- Duplicate webhook prevention tests
- Payment status recalculation tests
- Failed/refund logging tests

## Quality Requirements

- Keep gateway logic provider-neutral.
- Keep webhook state transitions public-safe and idempotent.
- Do not start C1.1 admin payment-view work yet.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/tests/**`
- webhook handler and parser classes

## Tasks Not Included

- Admin payment-view work
- Finance reconciliation
- Notifications

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/AI-PROMPT-SEQUENCE.md`