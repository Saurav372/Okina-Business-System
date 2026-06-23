# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C1 Orders, Sales Orders and Quotations

## Current Subtask

C1.2.8 Order confirmation

## Current Status

Not Started. We need to plan and implement the order confirmation flow and verification rules.

## Goal

Allow staff with appropriate permissions to confirm website orders, transitioning them from `pending_payment` to `confirmed` status. The system must enforce status transitions, validate permissions, record timestamps (`confirmed_at`), and ensure the confirmation flow adheres to the business rules.

## Dependencies

- C1.2.6 Sales order creation (Completed)
- A5.1.2 Order status definitions (Completed)

## Required Deliverables

1. **Validation & Transition Logic**: Verify that a website order can be transitioned from `pending_payment` to `confirmed` through the admin endpoint or action, enforcing proper status flow limits (e.g. preventing confirmation of already cancelled/delivered/refunded orders).
2. **Permission Guarding**: Ensure only authorized roles/permissions can confirm an order.
3. **Audit Event Emission**: Verify if confirmation triggers an audit event, or implement one if needed according to standard specifications.
4. **Feature Tests**:
   - `OrderConfirmationTest` or extending existing tests verifying transition rules, permissions, and database timestamp updates.

## Acceptance Criteria

- Staff with correct permissions can confirm an order (transition status to `confirmed`).
- Confirming an order sets `confirmed_at` to the current timestamp.
- Orders in terminal states (cancelled, delivered, refunded) cannot be confirmed.
- All tests compile and pass.

## Tests Required

- `tests/Feature/OrderConfirmationTest.php`
- Run the full test suite via `php artisan test`.

## Quality Requirements

- Ensure database transactions and locks are utilized where necessary.
- Apply Laravel Pint formatting.

## Files Likely Affected

- `apps/backend/app/Http/Controllers/Admin/AdminOrderActionController.php`
- `apps/backend/tests/Feature/OrderConfirmationTest.php` (new)

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`