# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.2 Refund management

## Current Subtask

C5.2.6 Payment-status recalculation

## Current Status

Not Started. C5.2.5 is completed, verified, and committed.

## Next Subtask

C5.2.7 Refund audit trail

## Goal

Ensure that order/payment status is recalculated correctly when refunds are processed, transitioning the payment status to `partially_refunded` or `refunded` based on the remaining net paid amount, with refund states taking priority over paid states.

## Dependencies

- C5.2.5 Refund payment record (Completed)

## Required Deliverables

- Backend logic/service updates (or model methods) ensuring that when a payment or refund record is saved/updated/deleted, the associated order's dynamic payment status correctly resolves to unpaid, partially_paid, paid, partially_refunded, or refunded.
- Tests verifying that transition to refunded or partially refunded states updates the order state correctly.

## Acceptance Criteria

- The calculated payment status behaves as follows:
  - `unpaid`: paid_total = 0 and refund_total = 0.
  - `partially_paid`: paid_total > 0, paid_total < order_total, and refund_total = 0.
  - `paid`: paid_total >= order_total and refund_total = 0.
  - `partially_refunded`: refund_total > 0 and net_paid > 0.
  - `refunded`: refund_total > 0 and net_paid = 0.
- Order details catalog and admin view presentation display the recalculated payment status correctly.

## Tests Required

- Automated test suite verifying the dynamic payment status calculation across all states: unpaid, partially_paid, paid, partially_refunded, and refunded.

## Quality Requirements

- Zero N+1 query regression.
- Adhere strictly to the domain calculation rules defined in `PaymentStateRecalculationRules`.

## Files Likely Affected

- `app/Support/Payments/PaymentStateRecalculationRules.php`
- `tests/Feature/PaymentStateRecalculationRulesTest.php`
- `app/Models/Order.php`

## Tasks Not Included

- Refund audit events permanent storage and validation (handled in C5.2.7).