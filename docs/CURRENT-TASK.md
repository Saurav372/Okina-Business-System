# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.1 Finance payment and balance views

## Current Subtask

C5.1.2 Payment and refund ledger list

## Current Status

In Progress. Confirming list endpoints and verifying formatting/tests.

## Next Subtask

C5.1.3 Order payment detail and balance panel

## Goal

Provide a paginated list of all payment and refund records (the financial ledger) for authorized staff. The listings must respect user visibility permissions and sensitive field boundary rules.

## Dependencies

- C5.1.1 Finance access boundary and sensitive-field policy (Completed)
- C1.1 Basic admin order and payment view (Completed)

## Reference Details

- Route: `GET /admin/payments` (PaymentController@index) and `GET /admin/refunds` (RefundController@index).
- Must utilize PaymentResource and RefundResource to shape output, hide database keys, and conditionally omit sensitive amounts.
- Relationship eager loading is required to avoid N+1 queries.