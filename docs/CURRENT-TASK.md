# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C2.2 Vendors and purchases

## Current Subtask

C2.2.7 Purchase payment tracking

## Current Status

Not Started. C2.2.5 (Stock receiving) and C2.2.6 (Partial stock receiving) are fully completed, verified, and committed.

## Next Subtask

C2.2.8 Vendor-order history

## Goal

Implement the API endpoint and business logic for tracking vendor payments and updating/reconciling the payment status (`payment_status`) on a purchase order (transitioning through unpaid, partially_paid, paid). This should register corresponding cash outflow movements, update vendor balances, emit audit events, and enforce authorization guards.

## Dependencies

- C5.1 Finance Payment And Balance Views
- C2.2.2 Purchase order creation

## Required Deliverables

- Detailed implementation plan to transition payment status and record payment transactions.