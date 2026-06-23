# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C1.3 Quotations and bulk-order conversion

## Current Subtask

C1.3.7 Sales-order conversion

## Current Status

Completed. C1.3.7 is implemented and verified. All 379 tests pass.

## Next Subtask

C1.3.8 Advance-payment recording

## Goal

Allow staff to record advance (partial) payments against a newly created sales order that originated from a quotation conversion.

## Dependencies

- C1.3.7 Sales-order conversion (Completed)
- C5.1 Finance payment and balance views (Not Started — advance payment recording is a prerequisite step)

## Reference Details

- Sales orders start in `confirmed` status after quotation conversion.
- Advance payments are recorded as `Payment` records against the `Order`.
- Payment status recalculation rules are implemented in `A5.1.4` and `A5.2.5`.