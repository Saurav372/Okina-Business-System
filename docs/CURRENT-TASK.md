# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.2 Refund management

## Current Subtask

C5.2.1 Refund request creation

## Current Status

Not Started. C5.1 is completed and all its regression tests are verified.

## Next Subtask

C5.2.2 Refund approval workflow

## Goal

Implement the refund request model, migrations, and creation endpoint so that staff (with appropriate permissions) can request refunds against a successful payment/order.

## Dependencies

- A5.2 Cancellation and refund rules (Completed)
- C5.1 Finance payment and balance views (Completed)

## Required Deliverables

- Migration to support refund requests (with tracking status, reasons, and link to payment/order).
- RefundRequest model and relationships.
- Endpoint/controller logic to submit a new refund request.
- Validation ensuring a refund request is made against a valid succeeded payment/order, and does not exceed the paid amount.

## Acceptance Criteria

- A refund request can be successfully created when validation rules are satisfied.
- Refund request status defaults to pending approval.
- Unauthorized users or staff without `refunds.manage` or `refunds.request` cannot create requests.

## Tests Required

- Integration and unit tests covering successful refund request creation.
- Validation tests checking bounds (requesting more than paid, duplicate requests, invalid payments).
- Policy/permission gating tests.

## Quality Requirements

- Zero N+1 query regression.
- Adhere strictly to authorization boundaries.

## Files Likely Affected

- `app/Models/RefundRequest.php`
- `database/migrations/xxxx_xx_xx_create_refund_requests_table.php`
- `app/Http/Controllers/Admin/RefundRequestController.php`
- `tests/Feature/RefundRequestTest.php`

## Tasks Not Included

- Approval workflow logic (which belongs to C5.2.2).
- Processing/distributing payments via gateway (part of refund execution).