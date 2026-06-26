# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.2 Refund management

## Current Subtask

C5.2.5 Refund payment record

## Current Status

Not Started. C5.2.4 is completed, verified, and committed.

## Next Subtask

C5.2.6 Payment-status recalculation

## Goal

Ensure that processing a refund registers/links the refund record with the original payment (via `payment_id`) and that the original payment record remains completely intact (unmodified status, amount, and provider details), ensuring financial auditability and ledger integrity.

## Dependencies

- C5.2.3 Partial refund processing (Completed)
- C5.2.4 Full refund processing (Completed)

## Required Deliverables

- Database and domain assertions verifying the relationship between `Refund` and `Payment`.
- Verification that original `Payment` records are never deleted, voided, or modified during refund request, approval, processing, or completion.
- API and database query checks verifying that both payment and refund entries are retained as distinct ledger records.

## Acceptance Criteria

- All refunds must refer to a valid, succeeded `Payment` (represented by `payment_id`).
- Completing (succeeding) a refund must not alter the `status`, `amount_minor`, or other core columns of the original `Payment` record.
- In database queries and ledger responses, both the payment and the refund must co-exist as distinct entries.

## Tests Required

- Automated test suite verifying payment record integrity after a refund transitions to succeeded.
- Tests asserting that refund records link to correct payments and do not mutate parent payment details.

## Quality Requirements

- Zero N+1 query regression.
- Strict compliance with database constraints and relationship integrity.

## Files Likely Affected

- `app/Models/Refund.php`
- `app/Models/Payment.php`
- `tests/Feature/RefundPaymentRecordTest.php` (new)

## Tasks Not Included

- Automated recalculation of order payment status fields (handled in C5.2.6).