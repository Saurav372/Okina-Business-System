# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B2.2 Upload and simple mockup preview

## Current Subtask

B2.2.7 Order persistence

## Current Status

B2.2.6 Cart persistence was completed on 2026-06-19.

B2.2.7 Order persistence is the next subtask, but it is blocked until B3.1.6 Pending-order creation is completed.

## Goal

Persist the public-safe customization snapshot from cart items into order item data once pending-order creation exists.

## Dependencies

- B2.2.6 Cart persistence
- B3.1.6 Pending-order creation

## Required Deliverables

- Order item shape that accepts the cart customization snapshot
- Persistence rules that copy cart item customization into pending order items
- Validation that order items preserve public product/SKU/customization snapshots
- Safe response/admin-ready shape for later admin file access

## Acceptance Criteria

- Customization metadata from cart items remains attached to order items.
- Order item payloads retain public-safe uploaded file and mockup references.
- Private storage paths remain excluded from customer-facing order payloads.
- The order structure remains compatible with later admin file access.

## Tests Required

- Order persistence tests
- Cart-to-order customization snapshot tests
- Public-safe order item payload tests

## Quality Requirements

- Do not implement B2.2.7 until B3.1.6 Pending-order creation is complete.
- Do not start admin file access inside this subtask.
- Reuse the B2.2.5/B2.2.6 customization snapshot shape instead of creating another metadata format.
- Keep uploaded file references private and public-safe.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

- Pending-order creation
- Admin file access
- Payment or checkout gateway work

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/orders-order-items-schema.md`
- `docs/feature-list.md`

Use `docs/AI-PROMPT-SEQUENCE.md` when deciding whether to inspect, implement, or review.