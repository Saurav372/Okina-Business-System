# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C1.3 Quotations and bulk-order conversion

## Current Subtask

C1.3.6 Quotation revision

## Current Status

Not Started. C1.3.5 is completed and verified.

## Goal

Implement the quotation revision tracking system, setting up the revisions history schema and model to record term snapshots whenever a quotation is revised.

## Dependencies

- C1.3.5 Customer approval (Completed)

## Required Deliverables

1. **Revision snapshotting schema & model**: Create `quotation_revisions` table and model.
2. **Revision logic**: Implement logic to snapshot quotation totals, contact snapshots, and items when revision status is changed.
3. **Feature tests**: Validate revision logging and history.