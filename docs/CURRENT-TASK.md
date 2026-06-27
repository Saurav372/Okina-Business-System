# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.6 Cancellation stock reversal

## Current Status

Not Started. C2.1.5 Order stock deduction is fully completed, verified, and committed.

## Next Subtask

C2.1.7 Low-stock warning

## Goal

Implement cancellation stock reversal to restore previously deducted stock back to the SKU's available balance when an order is cancelled. Reversal creates a corresponding `cancellation_reversal` trace (direction `in`) in `inventory_movements` atomically, prevents double reversal using order and item identity checks, and emits standard audit events.

## Dependencies

- C2.1.1 SKU stock balance (Completed)
- C2.1.4 Manual adjustment (Completed)
- C2.1.5 Order stock deduction (Completed)

## Required Deliverables

- Extend `InventoryMovementReason` enum to include `ORDER_CANCELLATION = 'order_cancellation'`.
- Implement public `reverseOrderStock(Order $order, array $options = []): array` in `InventoryBalanceService`.
- For each item in the order, eager load the SKU and its `InventoryItem` relation before the transaction.
- Sort the order items by `sku.inventoryItem.id` to establish stable lock ordering (prevent deadlocks).
- Run the entire loop inside a database transaction with a deadlock retry strategy (e.g. 3 attempts).
- For each order item:
  - Lock the row using a fresh query to guarantee row lock, throwing `InventoryItemNotFoundException` if missing.
  - Locate the corresponding `order_deduction` movement for this `order_item_id`. If none exists, skip it.
  - Check if a `cancellation_reversal` movement already exists for this `order_item_id` to prevent double reversal (idempotency check).
  - If a reversal already exists, collect it (for idempotency return) and skip creating a new one. **Existing movements MUST NOT emit new `inventory.stock_moved` events.**
  - If no reversal exists, call `recordMovement` to return the deducted quantity back to stock:
    - `sku` = `$item->sku`
    - `quantity` = quantity from the original deduction movement
    - `type` = `InventoryMovementType::CANCELLATION_REVERSAL`
    - `direction` = `InventoryDirection::IN`
    - `reason` = `InventoryMovementReason::ORDER_CANCELLATION`
    - `options` = merged options with `['order_id' => $order->id, 'order_item_id' => $item->id]`
- Return the collected/created movement records.
- Create feature tests in `InventoryCancellationReversalTest` verifying cancellation reversal, rollback, idempotency, negative stock updates, and audit event emission.

## Acceptance Criteria

- Reversing stock increases `inventory_items.on_hand_quantity` and parent `product_skus.stock_quantity` back to initial balances.
- Each reversal records a trace in `inventory_movements` with `movement_type = 'cancellation_reversal'` and `direction = 'in'`.
- Double reversal is prevented; repeated calls return the original movements without writing duplicate rows or duplicate audit events.
- Empty orders return `[]` immediately.
- Dispatches the `inventory.stock_moved` audit event for each item.

## Tests Required

- Integration tests verifying successful stock reversal for all deducted items in an order.
- Idempotency tests verifying repeat executions do not duplicate movements or emit extra audit events.
- Audit event validation.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Enums/InventoryMovementReason.php`
- `app/Services/InventoryBalanceService.php`
- `tests/Feature/InventoryCancellationReversalTest.php` (new)