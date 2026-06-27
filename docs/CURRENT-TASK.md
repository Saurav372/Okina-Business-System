# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.1 Inventory movements and stock handling

## Current Subtask

C2.1.1 SKU stock balance

## Current Status

Not Started. Parent task C5.3 Expense management is fully completed and committed.

## Next Subtask

C2.1.2 Stock-in

## Goal

Define and implement the `inventory_items` table to track SKU-level stock balances (`on_hand_quantity`, `reserved_quantity`, `available_quantity`). Expose these stock balance metrics per SKU, ensuring they are automatically initialized for new and existing SKUs.

## Dependencies

- A3.2.4 SKUs (Completed)

## Required Deliverables

- Create a database migration for the `inventory_items` table as defined in the schema plan.
- Create the `InventoryItem` Eloquent model and define relationships with `ProductSku`.
- Implement dynamic initialization (e.g., via a model observer or booting sequence) to ensure every `ProductSku` always has a corresponding `InventoryItem` row with default `0` quantities.
- Expose the stock balance metrics (`on_hand_quantity`, `reserved_quantity`, `available_quantity`) in SKU details and resources, ensuring no internal numeric IDs leak.
- Add unit and feature tests verifying that inventory item records are created alongside SKUs, and that balances are correctly queried.

## Acceptance Criteria

- The `inventory_items` table must contain: `id`, `product_sku_id` (unique), `on_hand_quantity` (default 0), `reserved_quantity` (default 0), and `available_quantity` (default 0).
- An `InventoryItem` record must be automatically created when a new `ProductSku` is created.
- Existing `ProductSku` records in the database must be safely backfilled during migration.
- Stock metrics are exposed in SKU resources, keeping database keys hidden.
- Test coverage must assert automated record creation on SKU inserts and retrieval of balances.

## Tests Required

- Model tests verifying `ProductSku` to `InventoryItem` relationships.
- Observer/creation tests verifying that inserting a `ProductSku` automatically creates its `InventoryItem` record.
- Integration tests ensuring that query responses containing SKUs include the correct stock balances.

## Quality Requirements

- Zero N+1 query regression.
- Code style matching Pint constraints.
- Static analysis check passing under PHPStan.

## Files Likely Affected

- `app/Models/ProductSku.php`
- `app/Models/InventoryItem.php` (new)
- `database/migrations/[timestamp]_create_inventory_items_table.php` (new)
- `tests/Feature/InventoryBalanceTest.php` (new)

## Tasks Not Included

- Stock movements record handling (handled in C2.1.2 / C2.1.3).
- Order reservations and automatic stock deductions (handled in C2.1.5).