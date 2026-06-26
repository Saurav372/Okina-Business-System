# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.2 Refund management

## Current Subtask

C5.2.4 Full refund processing

## Current Status

In Progress. C5.2.3 is completed, verified, and committed.

## Next Subtask

C5.2.5 Refund payment record

## Goal

Implement the full refund execution logic. Ensure transition rules, policy gates, endpoints, controllers, and webhook integrations support transitioning full refunds to succeeded or failed states, ensuring that full refund amounts are correctly validated to equal the remaining refundable balance of the payment.

## Dependencies

- C5.2.2 Refund approval workflow (Completed)
- C5.2.3 Partial refund processing (Completed)
- A5.2.4 Full refund rules (Completed)

## Required Deliverables

- Backend logic/service updates to process a full refund (updating status and recording transaction/provider attributes).
- Validation and business logic checks ensuring full refunds correctly match and offset the parent payment balances.

## Acceptance Criteria

- Approved full refunds can transition to `processing` and eventually to `succeeded`.
- Recalculated balance accounts for the full refund amount correctly, matching the net paid amount.
- Validation rejects full refunds if the requested amount does not equal the remaining refundable balance.

## Tests Required

- Test suite verifying status updates, totals calculations, and validation rules for full refunds.

## Quality Requirements

- Zero N+1 query regression.
- Adhere strictly to authorization boundaries.

## Files Likely Affected

- `app/Http/Controllers/Admin/RefundController.php`
- `app/Models/Refund.php`
- `tests/Feature/PartialRefundTest.php` (new or updated)

## Tasks Not Included

- Refund payment record integrity details (handled in C5.2.5).