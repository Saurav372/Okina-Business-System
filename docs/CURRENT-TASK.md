# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.2 Refund management

## Current Subtask

C5.2.3 Partial refund processing

## Current Status

Not Started. C5.2.2 is completed, verified, and committed.

## Next Subtask

C5.2.4 Full refund processing

## Goal

Implement the partial refund execution logic. Transition the refund status from `approved` to `processing` and eventually to `succeeded` or `failed` once processed by the gateway adapter (or marked manually as succeeded/failed), ensuring that partial refund totals are correctly calculated.

## Dependencies

- C5.2.2 Refund approval workflow (Completed)
- A5.2.3 Partial refund rules (Completed)

## Required Deliverables

- Backend logic/service updates to process a partial refund (updating status and recording transaction/provider attributes).
- Validation and business logic checks ensuring partial refunds correctly offset the parent payment balances.

## Acceptance Criteria

- Approved partial refunds can transition to `processing` and eventually to `succeeded`.
- Recalculated balance accounts for the partial refund amount correctly.

## Tests Required

- Test suite verifying status updates, totals calculations, and validation rules for partial refunds.

## Quality Requirements

- Zero N+1 query regression.
- Adhere strictly to authorization boundaries.

## Files Likely Affected

- `app/Http/Controllers/Admin/RefundController.php`
- `app/Models/Refund.php`
- `tests/Feature/PartialRefundTest.php` (new or updated)

## Tasks Not Included

- Full refund processing details (handled in C5.2.4).