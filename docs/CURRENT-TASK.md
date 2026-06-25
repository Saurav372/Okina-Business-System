# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.1 Finance payment and balance views

## Current Subtask

C5.1.4 Shared balance calculation presentation

## Current Status

Completed. C5.1.3 is implemented and verified. Eager loaded financial columns are added, clamping outstanding balance to zero, throwing descriptive LogicExceptions for missing relations, and validating the domain invariant for succeeded refunds.

## Next Subtask

C5.1.5 Finance filters, totals, and pagination

## Goal

Extend the order details presentation (via OrderDetailCatalog) to show:
- Paid amount (`paid_amount_minor`): sum of payments where status is `succeeded`.
- Refunded amount (`refunded_amount_minor`): sum of refunds where status is `succeeded`.
- Outstanding balance (`outstanding_balance_minor`): calculated as `total_amount_minor - paid_amount_minor + refunded_amount_minor`.

## Dependencies

- C5.1.1 Finance access boundary and sensitive-field policy (Completed)
- C5.1.2 Payment and refund ledger list (Completed)
- C1.1 Basic admin order and payment view (Completed)

## Required Deliverables

- Updated `OrderDetailCatalog.php` returning:
  - `paid_amount_minor`
  - `refunded_amount_minor`
  - `outstanding_balance_minor`
  within the `amounts` block of the summarized order payload.
- Automated tests verifying correct calculation of outstanding balance on order detail summary.

## Acceptance Criteria

- Sum of paid amounts only includes payments with a status of `succeeded`.
- Sum of refunded amounts only includes refunds with a status of `succeeded`.
- The outstanding balance uses the formula: `total_amount_minor - paid_amount_minor + refunded_amount_minor`.
- Eager load payments and refunds.
- Admin order details endpoint displays these fields in the returned JSON.

## Tests Required

- Integration/feature tests in `AdminOrderDetailTest.php` (or similar) asserting that the detail summary includes:
  - `paid_amount_minor`
  - `refunded_amount_minor`
  - `outstanding_balance_minor`
- Verify outstanding balance calculation logic with mock/factory orders, succeeded/failed/pending payments, and succeeded/failed refunds.

## Quality Requirements

- Ensure no N+1 query issue (payments and refunds must be eager-loaded).
- Maintain correct scoping and policy gates.

## Files Likely Affected

- `app/Support/Admin/OrderDetailCatalog.php`
- `tests/Feature/AdminOrderDetailTest.php`

## Tasks Not Included

- Interactive staff payments/refunds creation or management.

## Reference Details

- Route: `GET /admin/orders/{public_id}` (served by `Admin/OrderController@show` / `OrderDetailCatalog`).
- Calculated financial values must be derived from related `Payment` and `Refund` models having a status of `succeeded`.