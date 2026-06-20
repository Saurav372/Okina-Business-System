# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

Project C Operations Admin

## Current Subtask

C1.1.1 Admin order resource and authorization boundary

## Current Status

Phase C documentation readiness was completed on 2026-06-20.

C1.1.1 was completed on 2026-06-20. C1.1.2 is the next unblocked subtask when you are ready for the next approval gate.

## Goal

Create the permissioned, read-only Filament order-resource boundary for website orders. Do not add operational status changes, payment/refund actions, shipping, inventory, CRM, or finance reporting.

## Dependencies

- A2.1 Admin authentication
- A2.3 Role and permission model
- B3.1.6 Pending-order creation
- B3.1 Cart and checkout completion evidence
- B3.3 Payment webhook completion evidence

## Required Deliverables

- Permissioned admin order resource shell
- Read-only resource registration with no create, edit, delete, status, payment, refund, or shipping actions
- Focused authorization and resource-registration tests

## Acceptance Criteria

- Only users with the approved order-view permission can reach the resource.
- The resource registers no write actions.
- Shared order and payment records are unchanged.
- C1.1.2 through C1.1.6 remain out of scope.

## Tests Required

- Admin resource access tests
- Authorization denial tests
- Read-only action-registration tests
- Shared order/payment regression tests

## Quality Requirements

- Keep payment secrets and sensitive finance fields restricted.
- Do not create a migration or modify shared order/payment data.
- Run relevant backend tests after implementation.

## Tasks Not Included

- C1.1.2 and later C1.1 slices
- Shipping, CRM, inventory, payment, refund, and finance tasks

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`

