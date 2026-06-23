# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

C1 Orders, Sales Orders and Quotations

## Current Subtask

C1.2.7 Order editing rules

## Current Status

Completed. We have implemented the sales order editing rules, controller update action, route bindings, and audit event emission, and all feature tests pass successfully.

## Goal

Allow staff with appropriate permissions to edit sales orders under strict editing rules. When a sales order is edited, the system must ensure the edits are permission-protected, recalculate totals accurately, and emit audit events with sanitized payloads (redacting sensitive fields according to the audit payload policy).

## Dependencies

- C1.2.6 Sales order creation (Completed)
- A4.6 Audit event/interface contract (Completed)

## Required Deliverables

1. **Controller & Route Updates**: Implement an edit/update controller action and route for sales orders (`apps/backend/app/Http/Controllers/Admin/SalesOrderController.php`).
2. **Editing Rules & Validation**: Validate sales order edits (e.g. status constraints, allowed items, pricing adjustments).
3. **Audit Event Emission**: Emit a standard Laravel event when a sales order is edited, carrying the event key (`orders.order_edited`), actor, and sanitized payload.
4. **Feature Tests**:
   - `SalesOrderEditTest` verifying permissions, editing constraints, totals recalculation, and audit event emission.

## Acceptance Criteria

- Only authorized staff (with `orders.edit` or appropriate permissions) can edit sales orders.
- Editing a sales order recalculates totals (subtotal, tax, shipping, discount, total) correctly.
- When an edit occurs, an audit event is emitted containing the order ID, actor ID, and the changed fields, sanitized recursively via the `AuditPayloadPolicy`.
- All tests compile and pass.

## Tests Required

- `tests/Feature/SalesOrderEditTest.php`
- Run the full test suite via `php artisan test`.

## Quality Requirements

- Recalculate totals using the shared `OrderTotalsCalculator`.
- Keep the database transaction-safe for edits and item replacements.
- Apply Laravel Pint formatting.

## Files Likely Affected

- `apps/backend/app/Http/Controllers/Admin/SalesOrderController.php`
- `apps/backend/app/Services/SalesOrderService.php`
- `apps/backend/routes/web.php`
- `apps/backend/tests/Feature/SalesOrderEditTest.php` (new)
- `apps/backend/app/Support/Audit/AuditEventCatalog.php`

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`