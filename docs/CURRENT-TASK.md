# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.2 Vendors and purchases

## Current Subtask

C2.2.4 Purchase status

## Current Status

Not Started. C2.2.3 Purchase order items is fully completed, verified, and committed.

## Next Subtask

C2.2.5 Stock receiving

## Goal

Implement purchase order status transition API endpoints — allowing authorized staff to advance a purchase order's `status` through the approved state machine: `draft → ordered → partially_received → received → closed` (and `cancelled` from applicable states). All transitions must enforce the status-transition rules already defined on `VendorOrder`, timestamp key lifecycle moments (`ordered_at`, `received_at`, `cancelled_at`), and emit audit events.

## Dependencies

- C2.2.2 Purchase order creation (Completed) — `VendorOrder` model, status transition logic, and all domain exceptions already implemented.
- C2.2.3 Purchase order items (Completed) — items must exist before `draft → ordered` is practically meaningful.

## Required Deliverables

- Implement a `POST /admin/purchase-orders/{purchase_order}/status` endpoint on `VendorOrderController` (or a dedicated action controller) that:
  - Requires `purchases.manage` permission (via `VendorOrderPolicy@update`).
  - Accepts a `status` field validated against the allowed `VendorOrderStatus` enum values.
  - Calls `VendorOrder::transitionStatusTo()` (already implemented) and saves the record.
  - Calls `VendorOrder::transitionPaymentStatusTo()` if a `payment_status` transition is requested simultaneously.
  - Dispatches a structured `AuditEvent` with key `purchase_orders.status_updated` inside `DB::afterCommit`.
  - Returns the updated purchase order as JSON.
- Add a new route in `routes/web.php` for the status update endpoint.
- Create `tests/Feature/PurchaseOrderStatusTest.php` covering:
  - Valid transitions from each allowed state.
  - Rejection of invalid transitions (e.g. `draft → received`) with 422.
  - Rejection of transition to same state.
  - Audit event payload validation.
  - Permission guard (403 without `purchases.manage`).
  - Timestamp fields (`ordered_at`, `received_at`, `cancelled_at`) are stamped on the correct transitions.

## Acceptance Criteria

- Only permitted transitions allowed by `STATUS_TRANSITIONS` are accepted; invalid transitions return 422.
- Each allowed transition updates the status and the appropriate lifecycle timestamp atomically.
- `purchases.manage` permission is required; 403 returned otherwise.
- An `AuditEvent` with key `purchase_orders.status_updated` is dispatched after commit for each successful transition.

## Tests Required

- Feature tests in `PurchaseOrderStatusTest` verifying:
  - All valid status transitions.
  - Invalid transition rejection.
  - Duplicate/same-state transition handling.
  - Permission enforcement.
  - Timestamp stamping per transition.
  - Audit event dispatch validation.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Http/Controllers/Admin/VendorOrderController.php` (add `updateStatus` action)
- `routes/web.php` (add status route)
- `tests/Feature/PurchaseOrderStatusTest.php` (new)