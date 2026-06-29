# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.8 Movement history and audit

## Current Status

Completed. C2.1.8 Movement history and audit is fully completed, verified, and committed.

## Next Subtask

C2.2.1 Vendor management

## Goal

Ensure all inventory movements are traceable, support comprehensive query filtering (by SKU, order, vendor order, type), and reliably emit `AuditEvent` dispatches on `inventory.stock_moved` with complete payload verification.

## Dependencies

- C2.1.2 Stock-in (Completed)
- C2.1.3 Stock-out (Completed)
- C2.1.4 Manual adjustment (Completed)
- C2.1.5 Order stock deduction (Completed)
- C2.1.6 Cancellation stock reversal (Completed)
- C2.1.7 Low-stock warning (Completed)
- A4.6 Audit event/interface contract (Completed)

## Required Deliverables

- Expose a movement history query interface or helper method in `InventoryBalanceService` (or dedicated query class) supporting:
  - Filtering by `product_sku_id` / `sku_code`.
  - Filtering by `order_id` / `order_item_id`.
  - Filtering by `vendor_order_id` / `vendor_order_item_id` / `purchase_stock_in_id`.
  - Filtering by `movement_type` and `direction`.
  - Filtering by `occurred_at` date ranges.
  - Pagination and chronological sorting (newest first or oldest first).
- Verify that every stock movement operation (stockIn, stockOut, adjust, deductOrderStock, reverseOrderStock) dispatches the `AuditEvent` with the payload schema:
  - `movement_public_id`
  - `sku_public_id`
  - `movement_type`
  - `direction`
  - `reason`
  - `quantity`
  - `before_on_hand`
  - `after_on_hand`
  - `before_reserved`
  - `after_reserved`
  - `actor_user_id`
- Create `tests/Feature/InventoryMovementHistoryTest.php` to validate:
  - History query filters, pagination, and sorting.
  - Audit event dispatches across all 5 movement types with exact payload checks.
  - Idempotency checks block duplicate event emissions.

## Acceptance Criteria

- History queries correctly return expected movements matching filters.
- Every movement type records an immutable trace and dispatches an audit event.
- Zero duplicate audit events emitted when an operation is skipped due to idempotency.

## Tests Required

- Feature tests in `InventoryMovementHistoryTest` verifying:
  - History listing and filters.
  - Complete coverage of `AuditEvent` payload structures for each movement type.
  - Idempotency duplicate event avoidance.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Services/InventoryBalanceService.php`
- `tests/Feature/InventoryMovementHistoryTest.php` (new)