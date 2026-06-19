# Okina Craft Feature List

## A1

Feature ID:

A1

Feature Name:

Architecture and Database

Project:

Platform A: Shared Backend and Core Services

Purpose:

Create the repository shape, Laravel backend foundation, Astro frontend boundary, modular monolith structure, database plan, and migration direction.

Workflow:

Define domain boundaries -> finalize ERD -> create module structure -> create base migrations -> verify backend/frontend can build.

Business Rules:

- One Laravel backend.
- One shared database.
- Do not create separate backends for website, CRM, inventory, or finance.
- Database design must support later phases without rewriting early tables.

Dependencies:

None.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- ERD approved.
- Module structure documented.
- Base app structure agreed.
- Migration plan covers customers, products, SKUs, orders, payments, inventory, files, leads, quotations, audit, and notifications.

## A2

Feature ID:

A2

Feature Name:

Authentication and Permissions

Project:

Platform A: Shared Backend and Core Services

Purpose:

Secure customer and staff access across website and admin.

Workflow:

Define auth guards -> create roles -> create permissions -> protect admin/customer routes -> test access rules.

Business Rules:

- Staff permissions must be role-based.
- Finance and cost/profit data must be restricted.
- Staff cannot delete core records unless explicitly permitted.
- Customer accounts cannot access another customer's orders or files.

Dependencies:

A1.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Customer authentication works.
- Admin authentication works.
- Role policies protect admin screens and APIs.
- Permission tests exist for restricted modules.

## A3

Feature ID:

A3

Feature Name:

Shared Business Data

Project:

Platform A: Shared Backend and Core Services

Purpose:

Define shared customer, address, product, category, variant, and SKU records used by website and admin.

Workflow:

Create shared tables -> create admin management -> expose safe API data -> use same IDs in carts, orders, inventory, and reports.

Business Rules:

- Products must have SKU structure from the beginning.
- Product status and visibility must be separate.
- Order items must reference SKUs, not only text descriptions.
- Customer records must be reusable for website and sales orders.

Dependencies:

A1 and A2.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Products, variants, SKUs, categories, customers, and addresses are shared.
- Public product visibility follows status and visibility rules.
- Admin can manage core shared data.

## A4

Feature ID:

A4

Feature Name:

Platform Services

Project:

Platform A: Shared Backend and Core Services

Purpose:

Provide shared services for files, settings, queues, jobs, logs, notifications, integration safety, idempotency, and retries.

Workflow:

Create services -> define events -> queue external integrations -> log failures -> provide retry and deduplication behavior.

Business Rules:

- External services must not block core saves.
- Uploads must be private and validated.
- Settings must not be hardcoded when business users need control.
- Duplicate job/request handling is required.

Dependencies:

A1.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Upload service is secure.
- Settings foundation exists.
- Queue/job pattern exists.
- Notification events are defined.
- Audit event/interface contract is defined so core modules can emit audit events before permanent storage is implemented.
- Idempotency rules are documented for checkout, payments, inventory, notifications, Sheets sync, and retries.

## A5

Feature ID:

A5

Feature Name:

Shared Order and Payment Domain

Project:

Platform A: Shared Backend and Core Services

Purpose:

Define the shared operational rules used by website checkout, admin orders, sales orders, payments, cancellations, refunds, and tracking.

Workflow:

Define order types -> define quotation types -> define order statuses -> define payment states -> define payment service -> define cancellation/refund rules.

Business Rules:

- Order status and payment status are separate.
- Payment status is calculated from payment records.
- Website checkout creates a pending order before payment.
- Quotations are not official orders until approved.
- Gateway webhooks must be idempotent.

Dependencies:

A1, A3, A4.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Order types are Website Order and Sales Order.
- Quotation types are Bulk Quotation and Manual Quotation.
- Payment states are Unpaid, Partially Paid, Paid, Partially Refunded, Refunded.
- Order statuses are Pending Payment, Confirmed, In Production, Ready to Ship, Shipped, Delivered, Cancelled, Refunded.
- Shared payment service contract is documented.
- Cancellation and refund rules are documented.

## B1

Feature ID:

B1

Feature Name:

Catalog and Product Discovery

Project:

Project B: Customer Ecommerce Website

Purpose:

Show public product and category pages using shared backend data.

Workflow:

Fetch category/product API data -> render listing/detail pages -> show status/availability -> route bulk-only items to enquiry flow.

Business Rules:

- Draft and private products must not be publicly visible.
- Out-of-stock products may be visible but not directly purchasable.
- Bulk-only products or variants route to enquiry/quotation flow.

Dependencies:

A3 and A4.

Affected Projects:

Project B, Project C.

Acceptance Criteria:

- Product listing and detail pages use backend data.
- Visibility rules are respected.
- Product pages include SEO fields and WhatsApp/contextual enquiry options.

## B2

Feature ID:

B2

Feature Name:

Product Customization and Mockup Preview

Project:

Project B: Customer Ecommerce Website

Purpose:

Allow customers to select product options, size-wise quantities, print position, print method, upload design files, and see a simple mockup preview.

Workflow:

Customer selects product options -> uploads design -> preview generated -> customization saved to cart/order.

Business Rules:

- Invalid print method combinations must be blocked.
- Upload limit is 5 MB in v1.
- Original uploaded file must always be stored privately.
- Canva-style editor is not required in v1.

Dependencies:

A3, A4, A5.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Size-wise quantity totals are correct.
- Uploads are secure.
- Customization data persists from product page to cart to order.
- Admin can view uploaded design and mockup metadata.

## B3

Feature ID:

B3

Feature Name:

Cart, Checkout and Website Payment

Project:

Project B: Customer Ecommerce Website

Purpose:

Support mixed carts, logged-in checkout, pending order creation, payment attempt creation, gateway payment, webhook verification, and payment state update.

Workflow:

Cart -> checkout validation -> pending order -> payment attempt -> gateway payment -> webhook verification -> payment record update -> admin/customer order update.

Business Rules:

- Checkout must not call Cashfree directly.
- Checkout creates pending order before payment.
- Backend recalculates prices and validates cart.
- 25+ quantity uses bulk enquiry/quotation workflow.
- Payment webhooks must be idempotent.

Dependencies:

A2, A3, A4, A5, B1, B2.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Mixed cart works.
- Pending order is created safely.
- Payment attempt is created through shared payment service.
- Payment webhook updates payment state without duplicating records.
- Failed or abandoned payment leaves a traceable pending order/payment attempt.

## B4

Feature ID:

B4

Feature Name:

Customer Account and Tracking

Project:

Project B: Customer Ecommerce Website

Purpose:

Let customers manage account details, addresses, orders, uploaded designs, and order tracking.

Workflow:

Customer logs in -> views orders -> opens tracking -> sees customer-friendly status, payment summary, courier details, and support actions.

Business Rules:

- Customers can see only their own orders/files.
- Customer-facing status should be clean wording based on shared order status.
- Courier details can come from admin entry or tracking provider after shipment.

Dependencies:

A2, A5, B3, C1, C4.

Affected Projects:

Project B, Project C.

Acceptance Criteria:

- Customer dashboard works.
- Tracking uses shared order/payment/shipping data.
- Customer cannot access another customer's records.

## C1

Feature ID:

C1

Feature Name:

Orders, Sales Orders and Quotations

Project:

Project C: Business Operations Admin

Purpose:

Manage website orders, sales-created orders, quotations, bulk enquiry conversion, order status updates, and payment visibility.

Workflow:

Admin views orders -> updates status/payment records where allowed -> creates quotations -> converts approved quotations to sales orders.

Business Rules:

- Admin order management depends on A5, not Cashfree.
- Quotations are not orders until approved.
- Bulk enquiry flow is Lead -> Quotation -> Approval -> Sales Order -> Advance Payment.
- Sensitive changes must create audit records.

Dependencies:

A2, A3, A4, A5.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Website pending/paid orders appear in admin.
- Sales orders can be created manually.
- Quotations can be created and converted after approval.
- Status changes are audited.

## C2

Feature ID:

C2

Feature Name:

Inventory, Vendors and Purchases

Project:

Project C: Business Operations Admin

Purpose:

Track SKU stock availability, stock movements, vendor records, purchases, stock-ins, low stock, consumed stock, and negative stock rules.

Workflow:

Order created -> stock availability warning shown if needed -> purchased stock added -> consumed stock deducted -> adjustments audited.

Business Rules:

- Inventory rules live in C2 for V1.
- Low stock and insufficient stock should warn staff.
- Negative stock may be allowed only if business rules permit it.
- Stock adjustments and movements must be auditable and idempotent.
- Checkout should not be blocked by a complete reservation system.

Dependencies:

A3, C1.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- SKU stock is traceable.
- Product orderability and stock warnings work.
- Vendor stock-in updates inventory.
- Stock changes are audited.

## C3

Feature ID:

C3

Feature Name:

CRM and Staff Workflow

Project:

Project C: Business Operations Admin

Purpose:

Manage leads, sources, assignments, follow-ups, staff work queues, lead conversion, and sales activity.

Workflow:

Lead captured -> assigned -> followed up -> quoted -> approved -> converted to customer/order.

Business Rules:

- Sales staff can work assigned leads but cannot delete core records.
- UTM/referrer/page data must be preserved where available.
- Follow-up reminders should not block lead saves.

Dependencies:

A2, A3, A4, C1.

Affected Projects:

Project B, Project C.

Acceptance Criteria:

- Leads are captured from website and manual entry.
- Lead assignment and follow-up workflow works.
- Lead conversion keeps shared data clean.

## C4

Feature ID:

C4

Feature Name:

Production and Shipping

Project:

Project C: Business Operations Admin

Purpose:

Operate the simple V1 staff workflow for production status and shipping details.

Workflow:

Order confirmed -> in production -> ready to ship -> shipped -> delivered or cancelled.

Business Rules:

- Staff update only the main order status.
- Design approval is stored as order information: approved yes/no, approved at, approved by, and design notes.
- Courier name, tracking number, tracking URL, shipping date, and delivery date can be manually entered.
- Customer tracking should show clean status wording.

Dependencies:

A5, C1.

Affected Projects:

Project B, Project C.

Acceptance Criteria:

- Order can be marked In Production, Ready to Ship, Shipped, Delivered, or Cancelled.
- Courier name, tracking number, tracking URL, shipping date, and delivery date can be stored.
- Customer tracking reflects production/shipping updates.

## C5

Feature ID:

C5

Feature Name:

Finance and Reporting

Project:

Project C: Business Operations Admin

Purpose:

Manage payment records, outstanding balances, refunds, expenses, financial reporting, and protected finance views.

Workflow:

Payments recorded -> balance calculated -> refunds tracked -> expenses tracked separately -> reports generated for permitted users.

Business Rules:

- Payment status comes from payment records.
- Refunds must not erase original payment history.
- Refunds and expenses are separate workflows.
- Cost/profit data must be permission-protected.
- Finance changes must be audited.

Dependencies:

A2, A5, C1.

Affected Projects:

Platform A, Project C.

Acceptance Criteria:

- Payment records and outstanding balances are accurate.
- Refunds are tracked.
- Expenses are tracked separately from refunds.
- Restricted users cannot view protected finance data.
- Reports match source records.

## C6

Feature ID:

C6

Feature Name:

Automation, Audit and Hardening

Project:

Project C: Business Operations Admin

Purpose:

Add reliable notifications, Google Sheets backup sync, logs, retries, immutable audit logs, backups, security checks, and regression gates.

Workflow:

Core action saved -> event triggered -> job queued -> external service attempted -> result logged -> retry if safe -> audit sensitive changes.

Business Rules:

- Google Sheets is backup/reporting only, not source of truth.
- Failed automation must not block core actions.
- Audit logs are immutable.
- Sensitive data must be masked.
- Retention and backup rules must be defined.

Dependencies:

A4, A5, C1, C2, C3, C5.

Affected Projects:

Platform A, Project B, Project C.

Acceptance Criteria:

- Notifications use defined triggers/templates.
- Sheets sync is queued and deduplicated.
- Failed jobs can retry safely.
- Audit records cannot be edited or deleted by staff.
- Security and regression checklist is documented.
