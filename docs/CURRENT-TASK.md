# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C1.3 Quotations and bulk-order conversion

## Current Subtask

C1.3.5 Customer approval

## Current Status

Not Started. C1.3.4 is completed and verified.

## Goal

Provide the logic and database recording mechanism for capturing customer approvals (e.g. through a public-facing or staff-recorded approval event) before order conversion can proceed.

## Dependencies

- C1.3.4 Quotation status (Completed)

## Required Deliverables

1. **Approval recording mechanism**: Setup recording of customer approval events in `quotation_approval_events` table.
2. **Approval Verification**: Enforce that a quote cannot be converted to an order without recorded approval.
3. **Feature tests**: Validate approval lifecycle and events.