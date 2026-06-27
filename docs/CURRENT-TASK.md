# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.4 Manual adjustment

## Current Status

Not Started. C2.1.3 Stock-out is fully completed, verified, and committed.

## Next Subtask

C2.1.5 Order stock deduction

## Goal

Expose a public `adjust` API on `InventoryBalanceService` to manually adjust a SKU's stock balances (on-hand and/or reserved), record a matching trace in the append-only `inventory_movements` table atomically, and emit a standardized `inventory.stock_moved` audit event.

## Dependencies

- C2.1.1 SKU stock balance (Completed)
- C2.1.3 Stock-out (Completed)
- A4.6 Audit event/interface contract (Completed)

## Required Deliverables

- Implement public `adjust(ProductSku $sku, int $newOnHand, int $newReserved, InventoryMovementReason $reason, array $options = []): InventoryMovement` in `InventoryBalanceService`.
- Validate that the adjustment changes at least one balance (on-hand or reserved). If no balance changes, throw an `InvalidArgumentException`.
- Delegate the updates to the unified `recordMovement` primitive inside `InventoryBalanceService` under `InventoryDirection::ADJUST` direction and `InventoryMovementType::MANUAL_ADJUSTMENT` type.
- Emit an `App\Events\AuditEvent` with the key `inventory.stock_moved`, the current authenticated user as the actor (or custom user via options), and a payload containing `movement_public_id`, `sku_public_id`, `movement_type`, `quantity`, `before_balance` (e.g. before on-hand), `after_balance` (e.g. after on-hand), and `reason` (reason code).
- Create feature tests in `InventoryManualAdjustmentTest` verifying manual adjustments, validation rules, transactional integrity, enums casting, audit event emission, and idempotency.

## Acceptance Criteria

- Adjusting balances updates `inventory_items.on_hand_quantity`, `inventory_items.reserved_quantity`, and the parent `product_skus.stock_quantity` atomically.
- The operation records a matching trace in `inventory_movements` with `movement_type = 'manual_adjustment'` and `direction = 'adjust'`.
- The operation dispatches the `inventory.stock_moved` audit event.
- Retries with the same `idempotency_key` return the original movement safely and do not emit duplicate audit events.

## Tests Required

- Integration tests for manual adjustment (on-hand change only, reserved change only, both changes).
- Verification of database rollback on failure.
- Verification of audit event emission.
- Idempotency key tests.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Services/InventoryBalanceService.php`
- `tests/Feature/InventoryManualAdjustmentTest.php` (new)