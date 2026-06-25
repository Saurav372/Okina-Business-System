# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.1 Finance payment and balance views

## Current Subtask

C5.1.4 Shared balance calculation presentation

## Current Status

In Progress. Preparing to refactor `OrderDetailCatalog` to calculate outstanding balance using the shared `OrderTotalsCalculator` instead of local calculations.

## Next Subtask

C5.1.5 Finance filters, totals, and pagination

## Goal

Refactor the order details summary balance calculations in `OrderDetailCatalog` to leverage the shared `OrderTotalsCalculator` and `OrderTotals` contract.

## Dependencies

- C5.1.3 Order payment detail and balance panel (Completed)
- A5.1.4 Order totals and balances (Completed)

## Required Deliverables

- Refactored `OrderDetailCatalog.php` utilizing `OrderTotalsCalculator` to compute `paid_amount_minor`, `refunded_amount_minor`, and `outstanding_balance_minor`.
- Verification/test suite confirming correctness under the shared calculator framework.

## Acceptance Criteria

- Outstanding balance is computed using the shared `OrderTotalsCalculator`.
- Presentation output remains correct and maps directly to:
  - `paid_amount_minor` -> `$totals->paidAmountMinor()`
  - `refunded_amount_minor` -> `$totals->refundAmountMinor()`
  - `outstanding_balance_minor` -> `$totals->outstandingAmountMinor()`
- All existing tests in `AdminOrderDetailTest` continue to pass.

## Tests Required

- Run existing tests in `AdminOrderDetailTest.php` and verify they pass.
- Add additional assertions or tests if needed to verify integration with `OrderTotalsCalculator`.

## Quality Requirements

- Ensure no query performance regressions (N+1 queries).
- Strictly adhere to the shared contract boundary.

## Files Likely Affected

- `app/Support/Admin/OrderDetailCatalog.php`
- `tests/Feature/AdminOrderDetailTest.php`

## Tasks Not Included

- Modification of core calculator formulas or adding new fields to the schema.

## Reference Details

- Shared calculator class: `App\Support\Orders\OrderTotalsCalculator`.
- Shared value object: `App\Support\Orders\OrderTotals`.