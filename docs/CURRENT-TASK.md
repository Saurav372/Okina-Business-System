# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.2 Cart item validation

## Current Status

B3.1.1 Cart storage was completed on 2026-06-19.

B3.1.2 Cart item validation is now the active subtask.

## Goal

Validate cart items against current public product, SKU, and customization rules before checkout can continue.

## Dependencies

- A3.2 Shared products, categories, variants and SKUs
- B2.1 Customization option APIs
- B3.1.1 Cart storage

## Required Deliverables

- Checkout/cart validation that rejects unavailable products and SKUs
- Validation that cart customization options still match the selected SKU/product rules
- Public-safe validation errors for customer checkout flows
- Test coverage for invalid SKU, option, quantity, and customization cases

## Acceptance Criteria

- Invalid products, SKUs, disabled direct checkout SKUs, and invalid customization selections cannot proceed toward checkout.
- Valid cart items continue to pass without changing the stored cart snapshot shape.
- Validation errors are customer-safe and do not expose internal database IDs or private file paths.
- Later price recalculation and pending-order creation can reuse the validated cart item shape.

## Tests Required

- Cart validation tests
- Invalid SKU/product tests
- Invalid customization option tests
- Public-safe error payload tests

## Quality Requirements

- Follow existing Laravel and shared cart/product conventions.
- Do not start price recalculation, address validation, pending-order creation, payment attempts, or order item storage inside this subtask.
- Keep uploaded file references private and public-safe.
- Reuse existing customization validation rules rather than creating a second rule format.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`

## Tasks Not Included

- Price recalculation
- Customer and address validation
- Bulk quantity detection
- Pending-order creation
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
- `docs/project-b-c-build-runway.md`
- `docs/orders-order-items-schema.md`
- `docs/feature-list.md`

Use `docs/AI-PROMPT-SEQUENCE.md` when deciding whether to inspect, implement, or review.