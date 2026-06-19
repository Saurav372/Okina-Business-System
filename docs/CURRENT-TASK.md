# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B2.2 Upload and simple mockup preview

## Current Subtask

B2.2.6 Cart persistence

## Current Status

B3.1.1 Cart storage was completed on 2026-06-19.

B2.2.6 Cart persistence is now the active subtask.

## Goal

Persist the safe customization snapshot from the product page/upload flow into cart data without exposing private file paths.

## Dependencies

- B2.2.5 Customization metadata structure
- B3.1.1 Cart storage

## Required Deliverables

- Cart item shape that accepts the B2.2.5 customization snapshot
- Cart persistence rules for uploaded file references and mockup metadata
- Validation that cart items still reference public products/SKUs safely
- Safe response shape for later checkout and order item persistence

## Acceptance Criteria

- Customization metadata remains attached to cart items across the approved cart storage flow.
- Cart payloads remain public-safe and avoid raw storage paths.
- Uploaded file references remain private and resolvable through signed access rules.
- The cart structure remains compatible with later order item customization snapshots.

## Tests Required

- Cart persistence tests
- Customization snapshot retention tests
- Public-safe cart payload tests

## Quality Requirements

- Follow existing Laravel and Astro conventions.
- Do not start order persistence or admin file access inside this subtask.
- Keep uploaded file references private and public-safe.
- Reuse the B2.2.5 customization snapshot shape instead of creating another metadata format.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`

## Tasks Not Included

- Order persistence
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