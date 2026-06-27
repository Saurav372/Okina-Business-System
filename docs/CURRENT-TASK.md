# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.3 Stock-out

## Current Status

Not Started. C2.1.2 Stock-in is fully completed and committed.

## Next Subtask

C2.1.4 Manual adjustment

## Goal

Expose a public `stockOut` API on `InventoryBalanceService` to decrease a SKU's stock balance and record a matching trace in the append-only `inventory_movements` table atomically, reusing the unified `recordMovement` primitive.

## Dependencies

- C2.1.2 Stock-in (Completed)

## Required Deliverables

- Implement public `stockOut(ProductSku $sku, int $quantity, InventoryMovementReason $reason, array $options = []): InventoryMovement` in `InventoryBalanceService`.
- Enforce the invariant that `stockOut` calls delegate to `recordMovement`, ensuring every mutation creates exactly one immutable movement record.
- Validate input quantity is positive (throws `InvalidArgumentException` if <= 0).
- Create feature tests in `InventoryStockOutTest` verifying stock-out operations, calculations, rollback integrity, enums casting, and idempotency.

## Acceptance Criteria

- Stock-out decreases both `inventory_items.on_hand_quantity` and the parent `product_skus.stock_quantity` atomically.
- All stock-out events record a matching trace in `inventory_movements` with `movement_type = 'stock_out'` and `direction = 'out'`.
- Negative or zero stock-out quantities are rejected with an exception.
- Standard invariants are protected (e.g. throwing an exception if on-hand goes negative, unless `allow_negative_stock` is enabled).
- Retries with the same `idempotency_key` return the original movement safely.

## Tests Required

- Integration tests for manual stock-out.
- Tests verifying database rollback on failure.
- Invariant tests verifying stock-out fails if it makes on-hand quantity negative (unless negative stock is allowed).
- Idempotency key tests.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Services/InventoryBalanceService.php`
- `tests/Feature/InventoryStockOutTest.php` (new)