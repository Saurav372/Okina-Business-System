# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.5 Order stock deduction

## Current Status

Not Started. C2.1.4 Manual adjustment is fully completed, verified, and committed.

## Next Subtask

C2.1.6 Cancellation stock reversal

## Goal

Implement order stock deduction to decrease stock balances of SKUs in an order when staff executes the deduction. Record a matching `order_deduction` trace (direction `out`) for each order item in `inventory_movements` atomically, prevent duplicate/double deduction using order and item identity checks, and emit standard audit events.

## Dependencies

- C2.1.1 SKU stock balance (Completed)
- C2.1.4 Manual adjustment (Completed)
- C1.1 Basic admin order and payment view (Completed)

## Required Deliverables

- Implement public `deductOrderStock(Order $order, array $options = []): array` in `InventoryBalanceService`.
- For each item in the order, acquire a pessimistic lock (`SELECT ... FOR UPDATE`) on the corresponding `InventoryItem` row inside a database transaction.
- Query `inventory_movements` to check if stock has already been deducted for this specific `order_item_id` (using type = `order_deduction`) to ensure idempotency and prevent double deductions.
- Call the internal `recordMovement` primitive for each undeducted order item:
  - `type` = `InventoryMovementType::ORDER_DEDUCTION`
  - `direction` = `InventoryDirection::OUT`
  - `quantity` = order item quantity
  - `options` = merge options with `['order_id' => $order->id, 'order_item_id' => $item->id]`
- Ensure database transaction rolls back completely if any order item fails deduction (e.g., due to insufficient stock, unless negative stock is enabled).
- Dispatch the standard `inventory.stock_moved` audit event for each successfully recorded deduction movement.
- Create feature tests in `InventoryOrderDeductionTest` verifying order stock deduction, error cases, transactional integrity, idempotency, and audit event emission.

## Acceptance Criteria

- Order stock deduction decreases `inventory_items.on_hand_quantity` and parent `product_skus.stock_quantity` for all order items atomically.
- Each order item deduction records a matching trace in `inventory_movements` with `movement_type = 'order_deduction'` and `direction = 'out'`.
- Double execution on the same order is prevented; repeated calls return the original movements without throwing errors or creating duplicate rows.
- Standard invariants are protected (throws `InsufficientStockException` if stock goes below zero, unless `allow_negative_stock` is enabled).
- Rolled back atomically if any item fails to deduct.
- Dispatches the `inventory.stock_moved` audit event for each item.

## Tests Required

- Integration tests verifying successful stock deduction for all items in a website or sales order.
- Invariant tests verifying stock deduction fails and rolls back completely if any single item's balance is insufficient.
- Idempotency tests verifying repeat executions do not duplicate movements or emit extra audit events.
- Audit event validation.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Services/InventoryBalanceService.php`
- `tests/Feature/InventoryOrderDeductionTest.php` (new)