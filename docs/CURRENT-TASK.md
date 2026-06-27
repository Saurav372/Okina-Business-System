# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.2 Stock-in

## Current Status

Not Started. C2.1.1 SKU stock balance is fully completed and committed.

## Next Subtask

C2.1.3 Stock-out

## Goal

Create the append-only `inventory_movements` table. Implement stock-in operations where manual adjustments or purchase receipts create traceable movement records and increase the SKU's stock balance atomically.

## Dependencies

- C2.1.1 SKU stock balance (Completed)

## Required Deliverables

- Create database migration for the `inventory_movements` table as defined in the schema plan.
- Create the `InventoryMovement` Eloquent model and define relationships.
- Add stock-in capability to `InventoryBalanceService` (e.g. `stockIn(ProductSku $sku, int $quantity, array $meta = []): void`).
- Ensure the service atomically:
  - Validates input quantity is positive.
  - Locks and updates the SKU's `InventoryItem` balance (increases `on_hand_quantity`).
  - Records a detailed `InventoryMovement` with `movement_type = 'stock_in'`, `direction = 'in'`, snapshots of before/after quantities, reason/meta, and optional idempotency keys.
  - Updates the cached `product_skus.stock_quantity` to match the new `available_quantity` within a DB transaction.
- Create feature tests verifying stock-in behavior, calculations, rollback integrity, and unique idempotency keys.

## Acceptance Criteria

- `inventory_movements` table is created and properly indexed.
- Stock-in updates both `inventory_items` and `product_skus` atomically.
- All stock changes append a matching trace record to `inventory_movements`.
- Negative or zero stock-in quantities are rejected with an exception.
- Duplicate operations using the same idempotency key are rejected or ignored.

## Tests Required

- Integration tests for manual stock-in.
- Tests verifying database rollback on failure.
- Idempotency key tests to prevent duplicate stock-ins.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Models/InventoryMovement.php` (new)
- `database/migrations/[timestamp]_create_inventory_movements_table.php` (new)
- `app/Services/InventoryBalanceService.php`
- `tests/Feature/InventoryStockInTest.php` (new)