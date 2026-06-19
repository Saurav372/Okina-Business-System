# Indexes, IDs and Migration Sequence Plan

Task: A1.1.9 Indexes, IDs, migrations

Status: Planning draft

## Scope

This document consolidates the A1.1 schema drafts into a public ID, uniqueness, index, foreign key, delete behavior, and migration sequencing plan.

It does not implement Laravel migrations, public ID generators, database benchmarks, application models, policies, controllers, seed data, or deployment steps.

## Design Goals

- Internal primary keys remain unsigned big integers.
- Customer/admin-facing records use stable public IDs.
- Public IDs are unique, immutable, and safe to expose.
- Critical lookup paths have planned indexes.
- Duplicate-risk flows have unique constraints or idempotency keys.
- Historical business records are preserved through restrict/no-action, soft delete, or status transitions.
- Migration order respects cross-table dependencies and avoids circular FK traps.
- Implementation can start from these schema drafts without re-deciding the whole database shape.

## Public ID Standard

Use numeric primary keys internally and public IDs for user-facing/search-facing records.

General rules:

- Keep `id` as the internal FK and join key.
- Add `public_id` only where humans, customers, admins, external systems, or support workflows need a stable visible ID.
- Do not expose internal numeric IDs in public APIs unless unavoidable and explicitly approved.
- Generate public IDs transactionally.
- Treat public IDs as immutable after creation.
- Keep `public_id` columns at `varchar(40)` unless a table-specific integration needs more.
- Use uppercase prefixes and sortable sequence parts.
- Final sequence locking/generator implementation belongs to later implementation work.

Suggested formats:

| Domain | Table | Public ID Column | Suggested Format | Notes |
|---|---|---|---|---|
| Customers | `customers` | `public_id` | `CUS26-000001` | Customer support/admin lookup. |
| Orders | `orders` | `public_id` | `OD26-000001` | Official order ID. Do not create from quotation before conversion. |
| Leads | `leads` | `public_id` | `LD26-000001` | CRM lead lookup. |
| Quotations | `quotations` | `public_id` | `QT26-000001` | Quote sent to customer/admin. |
| Vendors | `vendors` | `vendor_code` | `VND-000001` or human code | Vendor code can be business-assigned. |
| Vendor Orders | `vendor_orders` | `public_id` | `PO26-000001` | Purchase order ID. |
| Files | `files` | `public_id` | `FILE26-000001` | File metadata lookup; not a signed access token. |
| Audit | `audit_logs` | `event_id` | ULID/UUID | Event ID for append-only audit event. |
| Payments | `payments` | `receipt_number` | `RCPT26-000001` | Optional receipt/reference when needed. |

Records that usually do not need public IDs:

- `roles`, `permissions`, and pivot tables use stable slugs or FK pairs.
- `product_variants` use `(product_id, code)`.
- `product_skus` use `sku_code`.
- Status/history/detail tables usually use parent public IDs plus timestamps/revision numbers.
- Notification and delivery records can use internal IDs plus dedupe/provider references unless a support UI needs a visible ID later.

## Unique Constraints and Idempotency Keys

### Core Identity

| Table | Required Unique Rule |
|---|---|
| `users` | `email` |
| `roles` | `slug`; optionally `(guard_name, slug)` if package-compatible guards are used |
| `permissions` | `slug`; optionally `(guard_name, slug)` if package-compatible guards are used |
| `role_user` | `(user_id, role_id)` |
| `role_permissions` | `(role_id, permission_id)` |
| `customers` | `public_id` |
| `customer_accounts` | `customer_id`; `normalized_email` |
| `products` | `slug` |
| `product_variants` | `(product_id, code)` |
| `product_skus` | `sku_code`; `(product_id, variant_key)`; nullable `barcode` if used |

### Orders, Payments, and Refunds

| Table | Required Unique Rule |
|---|---|
| `orders` | `public_id`; nullable `idempotency_key` if stored directly |
| `payment_attempts` | nullable `idempotency_key`; nullable `(provider, gateway_order_id)`; nullable `(provider, gateway_payment_id)` |
| `payments` | nullable `(provider, provider_payment_id)`; nullable `receipt_number` |
| `refunds` | nullable `(provider, provider_refund_id)` |
| `payment_webhook_logs` | nullable `(provider, provider_event_id)` |

### Inventory and Purchases

| Table | Required Unique Rule |
|---|---|
| `inventory_items` | `product_sku_id` |
| `inventory_movements` | nullable `idempotency_key` |
| `inventory_reservations` | nullable `idempotency_key` |
| `vendors` | `vendor_code` |
| `vendor_orders` | `public_id` |
| `purchase_stock_ins` | nullable `idempotency_key` |

### CRM and Quotations

| Table | Required Unique Rule |
|---|---|
| `leads` | `public_id` |
| `lead_follow_ups` | nullable `notification_key` |
| `quotations` | `public_id`; nullable `converted_order_id`; nullable `conversion_idempotency_key` |
| `quotation_approval_events` | nullable `idempotency_key` |
| `quotation_revisions` | `(quotation_id, revision_number)` |

### Files, Audit, and Notifications

| Table | Required Unique Rule |
|---|---|
| `files` | `public_id`; nullable `(storage_disk, storage_path)` |
| `file_access_grants` | `token_hash` |
| `audit_logs` | `event_id`; nullable `idempotency_key` |
| `notification_templates` | `(template_key, channel, locale, version)` |
| `notification_logs` | nullable `dedupe_key` |
| `notification_delivery_attempts` | `(notification_log_id, attempt_number)`; nullable `(provider, provider_message_id)` |

### Open Uniqueness Decisions

- Customer login email strict uniqueness is decided: use `unique(customer_accounts.normalized_email)`.
- Customer contact email and phone strict uniqueness remain duplicate-detection/search decisions for A3.1.
- Default address uniqueness may require generated columns or transaction-level application enforcement if MySQL support is limited.
- Vendor payment reference uniqueness depends on finance policy.
- Duplicate lead detection may use email, phone, idempotency, or a future deduplication table.

## Critical Index Coverage

This section summarizes the lookup paths that must remain covered during implementation. Individual schema drafts contain more detailed table-level index lists.

### Authentication and Permissions

- User login lookup: `unique(users.email)`.
- Staff filters: `index(users.status, users.deleted_at)`.
- Role and permission lookup: role/permission slug indexes.
- Permission screens: `index(permissions.group, permissions.is_sensitive)`.
- Pivot membership: `role_user(user_id, role_id)`, `role_permissions(role_id, permission_id)`.

### Customers and Addresses

- Customer support lookup: `unique(customers.public_id)`.
- Customer login lookup: `unique(customer_accounts.normalized_email)`.
- Customer-to-account mapping: `unique(customer_accounts.customer_id)`.
- Duplicate detection/contact lookup: `index(customers.email)`, `index(customers.phone)`.
- Admin filters: `index(customers.status, customers.deleted_at)`.
- Customer address loading: `index(customer_addresses.customer_id, customer_addresses.deleted_at)`.
- Default address lookup: indexes on `(customer_id, is_default_shipping)` and `(customer_id, is_default_billing)`.

### Catalog and SKUs

- Public catalog listing: `index(products.status, products.visibility, products.published_at)`.
- Category listing: `index(products.primary_category_id, products.status, products.visibility)`.
- Product detail SKUs: `index(product_skus.product_id, product_skus.status, product_skus.sort_order)`.
- SKU selection/search: `unique(product_skus.sku_code)`.
- Variant uniqueness: `unique(product_skus.product_id, product_skus.variant_key)`.
- Simple low-stock lookup before full inventory: `index(product_skus.track_stock, product_skus.stock_quantity)`.

### Orders and Order Items

- Order support lookup: `unique(orders.public_id)`.
- Customer order history: `index(orders.customer_id, orders.created_at)`.
- Admin queue: `index(orders.status, orders.created_at)`.
- Website/sales filters: `index(orders.order_type, orders.status, orders.created_at)`.
- Order item loading: `index(order_items.order_id, order_items.sort_order)`.
- SKU traceability: `index(order_items.product_sku_id)`.
- Status timeline: `index(order_status_histories.order_id, order_status_histories.changed_at)`.

### Payments, Refunds, and Webhooks

- Payment attempts by order: `index(payment_attempts.order_id, payment_attempts.created_at)`.
- Provider attempt lookup: gateway order/payment ID indexes.
- Payment history: `index(payments.order_id, payments.paid_at)`.
- Finance queue: `index(payments.status, payments.paid_at)`.
- Refund history: `index(refunds.order_id, refunds.created_at)` and `index(refunds.payment_id, refunds.created_at)`.
- Webhook dedupe: `unique(payment_webhook_logs.provider, payment_webhook_logs.provider_event_id)`.
- Webhook review queue: `index(payment_webhook_logs.processing_status, payment_webhook_logs.received_at)`.

### Inventory, Vendors, and Purchases

- SKU balance: `unique(inventory_items.product_sku_id)`.
- Low-stock lookup: `index(inventory_items.low_stock_threshold, inventory_items.available_quantity)`.
- Movement history: `index(inventory_movements.product_sku_id, inventory_movements.occurred_at)`.
- Order stock trace: indexes on `inventory_movements.order_id` and `order_item_id`.
- Purchase receiving trace: indexes on `vendor_order_id`, `vendor_order_item_id`, and `purchase_stock_in_id`.
- Vendor history: `index(vendor_orders.vendor_id, vendor_orders.created_at)`.
- Purchase status queues: `index(vendor_orders.status, vendor_orders.expected_at)`.

### CRM and Quotations

- Lead queues: `index(leads.status, leads.created_at)`.
- Staff work list: `index(leads.assigned_to_user_id, leads.status, leads.created_at)`.
- Follow-up queue: `index(lead_follow_ups.assigned_to_user_id, lead_follow_ups.status, lead_follow_ups.due_at)`.
- Quotation lookup: `unique(quotations.public_id)`.
- Quotation status/expiry: `index(quotations.status, quotations.valid_until)`.
- Customer quote history: `index(quotations.customer_id, quotations.created_at)`.
- Conversion safety: unique nullable `converted_order_id` and `conversion_idempotency_key`.
- Revision history: `unique(quotation_revisions.quotation_id, quotation_revisions.revision_number)`.

### Files, Audit, and Notifications

- File lookup: `unique(files.public_id)`.
- Storage safety: `unique(files.storage_disk, files.storage_path)`.
- Business file panels: indexes on `file_links.order_id`, `order_item_id`, `customer_id`, `quotation_id`, and `related_type/related_id`.
- Signed access: `unique(file_access_grants.token_hash)` and expiry indexes.
- Audit lookup: `unique(audit_logs.event_id)`, subject indexes, actor indexes, action/module time indexes.
- Notification queue: `index(notification_logs.status, notification_logs.scheduled_at)`.
- Notification dedupe: `unique(notification_logs.dedupe_key)`.
- Delivery retry: `index(notification_delivery_attempts.status, notification_delivery_attempts.retry_at)`.

## Foreign Key and Delete Behavior Summary

Use these defaults unless a table-specific schema draft says otherwise:

- Staff actor/editor FKs to `users.id`: set null on delete.
- Customer ownership/history FKs to `customers.id`: restrict/no-action for orders, payments, quotations, files, and audit unless an approved anonymization policy replaces the row safely.
- Core business history tables: restrict/no-action on hard delete.
- Mutable admin-managed entities such as users, customers, products, SKUs, vendors, leads, and files: prefer status or soft delete over hard delete.
- Order, payment, refund, inventory movement, purchase receiving, quotation approval, audit, notification, and delivery records: do not hard-delete through normal admin screens.
- Product/SKU references from order items, quotation items, inventory, and purchase records: restrict/no-action.
- File bytes may be cleaned up only when file records, links, protection windows, audit needs, and backup rules allow it.
- Corrections should use status changes, revisions, reversal movements, void/cancel states, or audit records instead of destructive deletion.

## Phase-Safe Laravel Migration Sequence

This sequence is the recommended implementation order for the A1.1 schema groups.

### Phase 1: Auth Foundation

1. Create Laravel standard auth support tables selected by the scaffold, such as password reset tables if needed.
2. Create `users`.
3. Create `roles`.
4. Create `permissions`.
5. Create `role_user`.
6. Create `role_permissions`.

Reason: users are referenced by nearly every admin, audit, file, CRM, and finance table.

### Phase 2: Shared Customer Data

7. Create `customers`.
8. Create `customer_addresses`.
9. Create `customer_accounts`.

Reason: orders, quotations, files, notifications, and audit records can all reference customers. Website authentication belongs to `customer_accounts`, not staff/admin `users` or credential fields on `customers`.

### Phase 3: Product and SKU Identity

10. Create `products`.
11. Create `product_variants`.
12. Create `product_skus`.

Reason: order items, quotation items, inventory, and vendor purchase items depend on SKU identity.

If product categories are split into their own future task, keep `products.primary_category_id` nullable and add that FK when categories exist.

### Phase 4: Orders

13. Create `orders`.
14. Create `order_items`.
15. Create `order_status_histories`.

Reason: payments, refunds, files, inventory movement, notifications, audit, and quotations-to-orders depend on order identity.

### Phase 5: Payments and Refunds

16. Create `payment_attempts`.
17. Create `payment_schedules`.
18. Create `payments`.
19. Create `refunds`.
20. Create `payment_webhook_logs`.

Reason: payment/refund history depends on orders and becomes a reference target for files, audit, notifications, and finance.

### Phase 6: Inventory, Vendors, and Purchases

21. Create `inventory_items`.
22. Create `vendors`.
23. Create `vendor_orders`.
24. Create `vendor_order_items`.
25. Create `purchase_stock_ins`.
26. Create `inventory_movements`.
27. Create `inventory_reservations`.
28. Create `low_stock_alerts`.
29. Create `vendor_payments`.

If `purchase_stock_ins` and `inventory_movements` create circular FK timing, create the nullable reference column first and add the FK after both tables exist.

### Phase 7: CRM and Quotations

30. Create `leads`.
31. Create `lead_activities`.
32. Create `lead_follow_ups`.
33. Create `quotations`.
34. Create `quotation_items`.
35. Create `quotation_approval_events`.
36. Create `quotation_revisions`.

Reason: quotations depend on customers, users, SKUs, and orders for conversion links.

### Phase 8: Files, Audit, and Notifications

37. Create `files`.
38. Create `file_links`.
39. Create `file_access_grants`.
40. Create `audit_logs`.
41. Create `audit_log_related_records`.
42. Create `notification_templates`.
43. Create `notification_logs`.
44. Create `notification_delivery_attempts`.

If some file links target future tables that do not exist yet, use nullable dedicated columns only after the target migration exists, or rely temporarily on `(related_type, related_id)`.

### Phase 9: Cross-Schema FK Tightening

45. Add delayed FKs that were impossible during the first pass because of optional module timing.
46. Add generated-column or filtered uniqueness strategies only after confirming the production MySQL/MariaDB version.
47. Backfill derived/cache fields where needed.
48. Run migration order review and rollback review before implementation moves past planning.

## Backfill and Transition Notes

### SKU Stock Balance

`product_skus.stock_quantity` is useful for simple V1 stock visibility. Full inventory should decide whether:

- `inventory_items` becomes the operational source and `product_skus.stock_quantity` is maintained as a cache, or
- `product_skus.stock_quantity` is retired from business logic after inventory movement implementation.

Do not let both fields drift independently.

### Payment Status

Payment status should be calculated from `payments` and `refunds`.

If a cached payment status is later stored on `orders`, it must be derived and recalculated from payment/refund facts.

### Quotation to Order

Do not create `orders.public_id` before quotation approval and conversion.

When a quotation converts, the created order should use:

- `orders.order_type = sales_order`
- `orders.order_source = quotation`
- `quotations.converted_order_id = orders.id`
- a conversion idempotency key to prevent duplicate sales orders

### Files

File records are metadata only. Actual file bytes live in private storage.

Signed access grants store token hashes, not raw tokens.

### Audit and Notifications

Audit and notification events should be created after core business writes in a transactionally safe way. Notification failures should not roll back business records.

## Parent A1.1 Readiness Notes

The A1.1 schema set now covers:

- Users, roles, permissions, and role assignments.
- Customers and addresses.
- Products, variants, and SKUs.
- Orders and order items.
- Payments, schedules, webhook logs, and refunds.
- Inventory, vendors, purchase orders, receiving, and vendor payments.
- CRM leads, follow-ups, quotations, revisions, approvals, and conversion tracking.
- Files, signed access, audit logs, notification templates/logs, and delivery attempts.
- Public IDs, lookup indexes, unique rules, delete behavior, and migration sequence.

Parent-level completion still requires a final parent completion check using the A1.1 completion gate.

## Review Checklist

### Migration Order Review

- Users are created before staff/audit/ownership references.
- Customers are created before orders, quotations, files, notifications, and audit customer references.
- Products and SKUs are created before order items, quotation items, inventory, and purchase items.
- Orders are created before payments, refunds, inventory order movement references, and conversion links.
- Files, audit, and notifications are late enough to reference earlier business records.
- Delayed FK additions are identified where module timing may require them.

Result: Pass.

### Index Review

- Critical lookup paths for auth, customer search, catalog, checkout/orders, payments, inventory, CRM, files, audit, notifications, and reporting are covered.
- Unique constraints cover stable identity and duplicate-risk flows.
- Queue/retry/status screens have status/time indexes.

Result: Pass.

### Public ID Review

- Customer/admin-facing records have public ID recommendations.
- Internal detail/history records rely on parent public IDs and internal IDs.
- Public IDs are immutable and unique.
- Official order IDs are not created before quotation conversion.

Result: Pass.

### Unique Constraint Review

- Core slugs, SKU codes, public IDs, provider references, receipt numbers, revision numbers, and dedupe keys are identified.
- Open uniqueness decisions are explicitly listed instead of guessed.

Result: Pass.

### Foreign Key and Delete Behavior Review

- Business history tables preserve records through restrict/no-action or status-based correction.
- Actor user FKs can set null while preserving historical labels.
- Soft delete is limited to mutable/admin-managed entities.
- SKU/order/payment/file/audit history is protected from normal destructive deletes.

Result: Pass.

### Idempotency Readiness Review

- Checkout/order creation, payment attempts, webhooks, inventory movements, receiving, quotation conversion, approval events, audit events, notifications, and delivery attempts have duplicate-prevention paths.
- Shared idempotency foundation can build on these per-table keys later.

Result: Pass.

### Parent A1.1 Schema Consistency Review

- All A1.1 schema groups have been represented in the consolidated plan.
- Cross-module references are sequenced.
- Remaining open decisions are deferred to the relevant future tasks without blocking this planning artifact.

Result: Pass.

## Open Decisions for Future Tasks

- Final public ID generator implementation and locking strategy.
- Final MySQL/MariaDB version, which affects generated/filtered uniqueness options.
- Customer contact email/phone strict uniqueness after customer duplicate-detection rules are finalized.
- Whether default address uniqueness is enforced through generated columns or transaction logic.
- Whether a shared idempotency table is added in A4.5 or per-table keys remain sufficient for V1.
- Whether order payment status is calculated on read or cached as a derived field.
- Final audit retention/anonymization policy.
- Final notification channels and provider callback strategy.
- Final file retention and cleanup policy.
