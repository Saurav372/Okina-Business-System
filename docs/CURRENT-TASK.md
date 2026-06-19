# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.6 Pending-order creation

## Current Status

B3.1.1 Cart storage was completed on 2026-06-19.

B3.1.6 Pending-order creation is now the active subtask.

## Goal

Create the pending order before payment starts and keep it compatible with the existing cart snapshot data.

## Dependencies

- A5.1.7 Shared order/payment domain model
- B3.1.1 Cart storage
- B3.1.2 Cart item validation
- B3.1.3 Price recalculation
- B3.1.4 Customer and address validation

## Required Deliverables

- Pending order record creation
- Checkout flow that creates the order before payment attempt creation
- Validation that the pending order uses the approved shared order/payment shape
- Safe payload shape for later payment, webhook, and admin order views

## Acceptance Criteria

- Checkout creates a pending order before any payment attempt starts.
- The pending order preserves public-safe cart data and shared order fields.
- The order structure remains compatible with later payment, webhook, and admin steps.
- Duplicate checkout protection and failure handling can build on the created order record.

## Tests Required

- Pending order tests
- Checkout flow tests
- Public-safe order payload tests

## Quality Requirements

- Follow existing Laravel and shared order conventions.
- Do not start payment-attempt creation inside this subtask.
- Keep the cart snapshot compatible with later order item storage.
- Preserve public-safe shared order data.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

- Payment-attempt creation
- Duplicate checkout prevention
- Failed checkout handling
- Order item/customization storage
- Payment webhook handling

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/orders-order-items-schema.md`
- `docs/feature-list.md`

Use `docs/AI-PROMPT-SEQUENCE.md` when deciding whether to inspect, implement, or review.