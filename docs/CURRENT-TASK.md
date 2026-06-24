# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.1 Finance payment and balance views

## Current Subtask

C5.1.1 Finance access boundary and sensitive-field policy

## Current Status

Not Started. Preparing implementation plan.

## Next Subtask

C5.1.2 Payment and refund ledger list

## Goal

Define and enforce the authorization policy and sensitive field protection rules for payments and refunds, ensuring only staff with explicit permissions (e.g. `finance.view` or `finance.manage`) can view sensitive fields like gateway fees, net amount, or overall financial reports.

## Dependencies

- A2.3 Role and permission model (Completed)
- C1.1 Basic admin order and payment view (Completed)

## Reference Details

- Admin role and permissions are stored in database.
- Policy check rules should block unauthorized roles from retrieving sensitive columns (`gateway_fee_minor`, `net_amount_minor`, etc.) when querying payment data.
- User permissions: `payments.view`, `payments.record`, `finance.view_sensitive`.