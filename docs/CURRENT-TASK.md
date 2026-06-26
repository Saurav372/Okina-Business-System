# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C5.2 Refund management

## Current Subtask

C5.2.2 Refund approval workflow

## Current Status

Not Started. C5.2.1 is completed, verified, and committed.

## Next Subtask

C5.2.3 Partial refund processing

## Goal

Implement the refund approval endpoint (`POST /admin/refunds/{refund}/approve`) and workflow, allowing authorized staff (with the `refunds.approve` permission) to transition a refund request from `requested` to `approved`.

## Dependencies

- C5.2.1 Refund request creation (Completed)
- A2.3 Role and permission model (Completed)

## Required Deliverables

- Endpoint/route: `POST /admin/refunds/{refund}/approve`.
- Update `RefundPolicy` so that `approve` is gated strictly by the `refunds.approve` permission.
- Controller logic:
  - Transition refund status from `requested` to `approved`.
  - Record the `approved_by_user_id` and `approved_at` timestamp.
  - Emit an `AuditEvent` with key `refunds.refund_approved` containing refund details.
- Validation ensuring that only refunds in `requested` status can be approved.

## Acceptance Criteria

- Staff with `refunds.approve` can successfully approve requested refunds.
- Unauthorized users or staff without `refunds.approve` are blocked (HTTP 403).
- Only refunds in `requested` state can be approved (other states reject with HTTP 422).

## Tests Required

- Integration tests covering successful approval.
- Unauthorized rejection tests.
- Transition rule constraints tests (rejecting approval of already approved, succeeded, failed, or cancelled refunds).
- Audit event emission assertion.

## Quality Requirements

- Zero N+1 query regression.
- Adhere strictly to authorization boundaries.

## Files Likely Affected

- `app/Http/Controllers/Admin/RefundController.php`
- `app/Policies/RefundPolicy.php`
- `routes/web.php`
- `tests/Feature/RefundApprovalTest.php` (new)

## Tasks Not Included

- Gateway execution of the refund (belongs to C5.2.3 and C5.2.4).