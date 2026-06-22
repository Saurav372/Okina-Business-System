# Current Task

Use this file as the task-specific context for a coding session. Update it before starting each new subtask.

## Current Parent Task

B4 Customer Account and Tracking

## Current Subtask

None (B4.1 Customer dashboard is completed)

## Current Status

Completed. All deliverables for B4.1 Customer dashboard have been implemented, tested, and validated successfully.

## Goal

Provide a customer-facing portal where authenticated customers can manage their profiles, view and manage their shipping/billing addresses, view their complete order and payment history, view high-quality design customization snapshots with signed temporary preview links, and perform quick actions like reordering or contacting support. The portal must be fully secure, preventing access to other customers' data, and styled using premium aesthetics.

## Dependencies

- A2.2 Customer authentication
- A3.1 Shared customers and addresses
- A5.1 Shared order/payment domain model
- B3.1 Cart and checkout with pending order creation
- C1.1 Basic admin order and payment view

## Required Deliverables

1. **Dashboard Interface / View**: An updated premium dashboard view (`resources/views/customer/account.blade.php`) featuring a clean tabbed/card layout for profile, addresses, and order history.
2. **Address Management**: Controller actions, routes, and views/modals to list, create, edit, and delete customer addresses.
3. **Order and Payment History**: Display orders with customer-friendly status labels, payment status, totals, and detailed breakdown including item customization previews.
4. **Design Previews**: Integration of signed temporary URLs for uploaded customer design previews within order items.
5. **Interactive Actions**:
   - **Reorder**: A controller action to re-add items from a past order into the customer's cart.
   - **Support/Contact**: Direct links to WhatsApp or support email with contextual order information.
6. **Feature Tests**:
   - `CustomerDashboardTest` verifying auth protection, access control boundaries, profile/address rendering, and design file authorization.
   - `CustomerAddressTest` verifying CRUD operations on addresses.
   - `CustomerReorderTest` verifying the reorder action adds items back to the cart correctly.

## Acceptance Criteria

- Guests are redirected to the customer login screen.
- Authenticated customers can only view their own profile, addresses, orders, payments, and design previews.
- Address management allows adding/editing/deleting shipping/billing addresses, updating defaults correctly.
- Past orders show correct customer-friendly status labels and payment status computed from payment/refund records.
- Customization thumbnails display mockups safely using temporary signed routes.
- The "Reorder" action copies all items from a past order into the current session cart.
- The UI is responsive, premium, and clean.

## Tests Required

- `tests/Feature/CustomerDashboardTest.php`
- `tests/Feature/CustomerAddressTest.php`
- `tests/Feature/CustomerReorderTest.php`
- Full test suite run using `php artisan test` before completion.

## Quality Requirements

- Avoid N+1 queries by eager-loading relations (`orders.items`, `orders.payments`, `orders.refunds`, `addresses`).
- Use transaction safety for address updates and reorders.
- Apply Laravel Pint formatting.
- Ensure strict security checks in controllers/policies (i.e. customers cannot access other customers' records).

## Files Likely Affected

- `apps/backend/app/Http/Controllers/CustomerAuthController.php`
- `apps/backend/app/Http/Controllers/CustomerAddressController.php` (new)
- `apps/backend/app/Http/Controllers/CustomerOrderController.php` (new)
- `apps/backend/routes/web.php`
- `apps/backend/resources/views/customer/account.blade.php`
- `apps/backend/resources/views/customer/addresses/` (new)
- `apps/backend/resources/views/customer/orders/` (new)
- `apps/backend/tests/Feature/CustomerDashboardTest.php` (new)
- `apps/backend/tests/Feature/CustomerAddressTest.php` (new)
- `apps/backend/tests/Feature/CustomerReorderTest.php` (new)

## Reference Details

- `docs/PROJECT-CONTEXT.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- `docs/project-b-c-build-runway.md`
- `okina_craft_full_project_spec.md`