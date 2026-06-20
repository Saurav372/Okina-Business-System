# Current Task

Use this file as the task-specific context for a coding session.

Update this file before starting each new subtask. Keep it narrow.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1 Basic admin order and payment view

## Current Status

B3.3 Payment webhook handling was completed on 2026-06-20.

C1.1 Basic admin order and payment view is now the active subtask.

## Goal

Show website pending and paid orders, payment records, status, customer data, order items, and uploaded design references for authorized staff.

## Dependencies

- A2.3 Role and permission model
- A5.1 Shared order/payment domain model
- B3.1 Cart and checkout with pending order creation
- B3.3 Payment webhook handling

## Required Deliverables

- Admin order and payment view surface
- Website order/payment summaries for staff
- Customer, item, and upload visibility in the admin context
- Public-safe payment and refund record presentation

## Acceptance Criteria

- Authorized staff can inspect website orders and their payment history.
- Payment status is derived from the shared records created by checkout and webhook handling.
- Restricted finance data stays hidden from unauthorized roles.
- Admin order and payment views remain consistent with the shared order/payment model.

## Tests Required

- Admin order tests
- Payment record tests
- Access control tests
- Shared order/payment regression tests

## Quality Requirements

- Keep finance and sensitive payment details restricted.
- Do not start shipping workflow, CRM, inventory, or refund processing tasks yet.
- Keep shared order/payment data consistent with the website checkout and webhook flows.
- Run relevant backend tests after implementation.

## Files Likely Affected

- `apps/backend/app/**`
- `apps/backend/tests/**`
- admin resources, pages, and view components

## Tasks Not Included

- Shipping workflow
- CRM lead and quotation work
- Inventory movements
- Refund processing
- Notifications

## Reference Details

Use:

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`