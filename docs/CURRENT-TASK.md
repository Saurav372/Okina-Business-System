# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.2 Vendors and purchases

## Current Subtask

C2.2.5 Stock receiving

## Current Status

Not Started. C2.2.4 Purchase status is fully completed, verified, and committed.

## Next Subtask

C2.2.6 Partial stock receiving

## Goal

Implement the API endpoint and business logic for receiving stock on a purchase order. When items are received, the system must update the `quantity_received` on `VendorOrderItem`, and atomically record an inventory movement that increases the SKU balance (using `InventoryBalanceService` or the core balance manager). This must ensure transaction safety, concurrency control, and audit logs.

## Dependencies

- C2.1.2 Inventory movement recording (Completed)
- C2.2.3 Purchase order items (Completed)
- C2.2.4 Purchase status (Completed)

## Required Deliverables

- Create a `POST /admin/purchase-orders/{purchase_order}/items/{item}/receive` or similar endpoint to receive a specific quantity of items on a purchase order line.
- Update `quantity_received` on the `VendorOrderItem`.
- Increment the stock balance of the product SKU by dispatching a stock-in movement using the established patterns in `InventoryBalanceService`.
- Transition the `VendorOrder` status to `partially_received` or `received` based on whether all items have been fully received.
- Ensure only purchase orders in `ordered` or `partially_received` status can receive stock.
- Handle concurrency and lock row updates.
- Dispatch structured `AuditEvent` dispatches after successful commit.
- Cover with tests verifying stock updates, status changes, validation constraints, and audit logs.

## Acceptance Criteria

- Stock receiving is only permitted on ordered or partially received purchase orders.
- Receiving stock increases the product SKU balance via the standard inventory balance logic.
- Total order status automatically updates to `received` when all items are fully received.
- Actions are fully authorized and audited.

## Tests Required

- Feature tests in `StockReceivingTest` or similar verifying:
  - Successful stock receiving and balance increment.
  - Automatic status transition when order is fully received.
  - Immutability guards on non-ordered POs.
  - Audit events.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Http/Controllers/Admin/VendorOrderItemController.php`
- `routes/web.php`
- `tests/Feature/StockReceivingTest.php`