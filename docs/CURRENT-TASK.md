# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

B3.1 Cart and checkout with pending order creation

## Current Subtask

B3.1.3 Price recalculation

## Current Status

B3.1.2 Cart item validation was completed on 2026-06-19.

B3.1.3 Price recalculation is now the active subtask.

## Goal

Recalculate cart line totals and checkout totals on the backend so pricing cannot be trusted from the client.

## Dependencies

- A3.2 Shared products, categories, variants and SKUs
- A5.1 Shared order/payment domain model
- B3.1.2 Cart item validation

## Required Deliverables

- Backend price recalculation for cart items
- Server-side subtotal and total calculation based on current product and SKU prices
- Public-safe price fields in cart and checkout payloads
- Test coverage for pricing changes, totals, and stale price scenarios

## Acceptance Criteria

- Cart totals are computed by backend rules, not client-supplied values.
- Price changes in products or SKUs are reflected when the cart is recalculated.
- Public cart payloads remain safe and do not expose internal identifiers or private file paths.
- Later customer/address validation, bulk detection, and pending-order creation can reuse the recalculated cart shape.

## Tests Required

- Price recalculation tests
- Cart total tests
- Stale price and updated price tests
- Public-safe payload tests

## Quality Requirements

- Follow existing Laravel and shared cart/product conventions.
- Do not start customer/address validation, bulk quantity detection, pending-order creation, payment attempts, duplicate checkout prevention, or failed checkout handling inside this subtask.
- Keep uploaded file references private and public-safe.
- Reuse existing order total support instead of creating a parallel pricing format.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/routes/**`
- `apps/backend/tests/**`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`

## Tasks Not Included

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
