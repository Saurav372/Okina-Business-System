# Okina Craft Dependency and Impact Register

## Dependency Register

| Source Task | Affected Task | Relationship | Impact |
|---|---|---|---|
| A1.1 Final ERD and schema plan | All implementation tasks | Blocks | Critical |
| A1.2 Repository and app scaffold plan | A1.3 Modular backend structure | Requires | High |
| A1.4 Environment and hosting readiness check | A1.5 Repository and app scaffold implementation | Requires | Satisfied for local scaffold. Composer is available through `tools/composer/composer.phar`; MySQL Server 8.0.46 is running locally on `127.0.0.1:3306`; npm/cache metadata works with approved escalation/outside the non-escalated Codex sandbox; production hosting is deferred for A1.5 local scaffold only. |
| A1.5 Repository and app scaffold implementation | A2.1 Admin authentication | Requires | Critical |
| A1.5 Repository and app scaffold implementation | A2.2 Customer authentication | Requires | Critical |
| A2.1 Admin authentication | C1.1 Basic admin order and payment view | Requires | Critical |
| A2.2 Customer authentication | B3.1 Cart and checkout with pending order creation | Requires | Satisfied for customer session/access foundation. Checkout still requires A3.1 customer addresses, A3.2 catalog/SKUs, A5.1 order/payment domain, B2.2 customization data, and A4.5 idempotency before B3.1 can start safely. |
| A2.3 Role and permission model | C5.1 Finance payment and balance views | Blocks protected access | High |
| A3.1 Shared customers and addresses | B3.1 Cart and checkout with pending order creation | Provides data | Critical |
| A3.1 Shared customers and addresses | C1.2 Sales order creation | Provides data | High |
| A3.2 Shared products, categories, variants and SKUs | B1.1 Public category and product APIs | Provides data | High |
| B1.1 Public category and product APIs | B1.2 Astro catalog and product pages | Requires | High |
| A3.2 Shared products, categories, variants and SKUs | C2.1 Inventory movements and stock handling | Provides stock identity | Critical |
| A3.2 Shared products, categories, variants and SKUs | B3.1 Cart and checkout with pending order creation | Required | Critical |
| A4.1 File upload service | B2.2 Upload and simple mockup preview | Requires | High |
| A4.2 Settings service | A4.4 Notification event definitions | Provides defaults and shared config conventions | High |
| A4.2 Settings service | A5.3 Payment gateway service contract | Provides payment configuration defaults | High |
| A4.2 Settings service | B2.2 Upload and simple mockup preview | Provides upload configuration defaults | High |
| B2.2 Upload and simple mockup preview | B3.1 Cart and checkout with pending order creation | Provides customization upload and preview metadata | High. B2.2.5 now provides the public-safe normalized customization snapshot; B3.1.1 is complete, so B2.2.6 Cart persistence is unblocked, and B2.2.7-B2.2.8 still need order persistence and admin file access before the parent task is complete. |
| A4.2 Settings service | C6.2 Notification implementation | Provides notification configuration defaults | High |
| A4.2 Settings service | C6.3 Google Sheets backup sync | Provides integration configuration defaults | High |
| A4.3 Queue, job and retry foundation | A4.4 Notification event definitions | Provides queue and retry foundation | High |
| A4.3 Queue, job and retry foundation | C6.3 Google Sheets backup sync | Provides retry and deduplication foundation | High |
| A4.3 Queue, job and retry foundation | C6.2 Notification implementation | Requires | High |
| A4.5 Idempotency foundation | B3.1 Cart and checkout with pending order creation | Requires | Critical |
| A4.5 Idempotency foundation | B3.3 Payment webhook handling | Requires | Critical |
| A4.5 Idempotency foundation | C2.1 Inventory movements and stock handling | Requires | High |
| A4.5 Idempotency foundation | A5.3 Payment gateway service contract | Requires | Critical |
| A4.5 Idempotency foundation | C6.2 Notification implementation | Requires | High |
| A4.5 Idempotency foundation | C6.3 Google Sheets backup sync | Requires | High |
| A4.6 Audit event/interface contract | C1/C2/C5 sensitive changes | Provides audit interface | High |
| A4.6 Audit event/interface contract | C6.1 Immutable audit log | Provides event contract | High |
| A5.1 Shared order/payment domain model | B3.1 Cart and checkout with pending order creation | Defines shared rules | Critical |
| A5.1 Shared order/payment domain model | C1.1 Basic admin order and payment view | Defines shared rules | Critical |
| A5.1 Shared order/payment domain model | C5.1 Finance payment and balance views | Defines shared rules | Critical |
| A5.2 Cancellation and refund rules | C1.1 Basic admin order and payment view | Defines cancellation rules | High |
| A5.2 Cancellation and refund rules | C5.2 Refund management | Defines refund rules | High |
| A5.3 Payment gateway service contract | B3.2 Website payment adapter implementation | Requires | Critical |
| B3.1 Cart and checkout with pending order creation | C1.1 Basic admin order and payment view | Creates shared order | Critical |
| B3.2 Website payment adapter implementation | B3.3 Payment webhook handling | Requires | Critical |
| B3.3 Payment webhook handling | C1.1 Basic admin order and payment view | Updates payment state | Critical |
| C1.1 Basic admin order and payment view | B4.1 Customer dashboard | Provides order data | High |
| C1.1 Basic admin order and payment view | C4.1 Simple order processing | Provides order | High |
| C1.2 Sales order creation | C1.3 Quotations and bulk-order conversion | Requires | High |
| C1.2 Sales order creation | C5.1 Finance payment and balance views | Provides receivable data | High |
| C3.1 CRM lead module | C1.3 Quotations and bulk-order conversion | Provides lead/source data | High |
| C2.1 Inventory movements and stock handling | B3.1 Cart and checkout with pending order creation | Provides availability warning | Medium |
| C4.1 Simple order processing | B4.2 Customer tracking page | Provides status data | High |
| C4.2 Shipping details | B4.2 Customer tracking page | Provides tracking information | High |
| C5.1 Finance payment and balance views | C1.1 Basic admin order and payment view | Provides payment summary | High |
| C6.1 Immutable audit log | C1/C2/C5 sensitive changes | Stores immutable audit records | Critical |
| C6.2 Notification implementation | C3.2 Follow-up workflow | Triggers reminders | Medium |
| C6.3 Google Sheets backup sync | Core lead/order/payment saves | May break if blocking | High |

## Correct Checkout To Payment Dependency

Use this flow:

```text
B3.1 Checkout
-> creates shared pending order
-> creates payment attempt
B3.2 Payment adapter
-> starts gateway payment
B3.3 Payment webhook
-> updates payment record
-> recalculates payment state
C1.1 Admin orders
-> displays current order and payment state
```

Cashfree or any future gateway must not be treated as the source that creates the order.

## Impact Check Template

Before changing any task, record:

| Question | Answer |
|---|---|
| Which projects are affected? |  |
| Which features are affected? |  |
| Which APIs are affected? |  |
| Which database tables are affected? |  |
| Which admin screens are affected? |  |
| Which customer screens are affected? |  |
| Which reports are affected? |  |
| Which notifications are affected? |  |
| Which audit records are affected? |  |
| Which idempotency rules are affected? |  |
| What tests must be repeated? |  |
| Is data migration required? |  |
| Is rollback planning required? |  |

## Status And State Impact Rules

Changing order status affects:

- Admin order screens
- Customer dashboard
- Customer tracking page
- Production/shipping screen
- Shipping workflow
- Notifications
- Audit logs
- Reports

Changing payment records affects:

- Payment state calculation
- Admin order payment summary
- Customer payment summary
- Finance reports
- Refund logic
- Outstanding balance
- Audit logs
- Notifications

Changing inventory movements affects:

- SKU stock
- Stock availability warnings
- Product availability
- Production readiness
- Purchase reports
- Low stock warnings
- Audit logs

Changing quotation workflow affects:

- Bulk enquiry flow
- Lead conversion
- Sales order creation
- Advance payment workflow
- Customer communication
- Sales reports

Changing notifications affects:

- Order Created
- Payment Received
- Quotation Sent
- Quotation Approved
- Design Approval Requested
- Production Started
- Shipment Created
- Order Delivered
- Payment Pending
- Follow-up Due
- Low Stock
- Job Failed

## Regression Gates

Before starting a phase:

- Run existing tests.
- Confirm current migrations are healthy.
- Confirm admin login works.
- Confirm checkout smoke path works if already implemented.
- Confirm existing order view works.
- Confirm upload path works if already implemented.

Before deploying a phase:

- Run automated tests.
- Run checkout smoke test.
- Run admin smoke test.
- Run upload smoke test.
- Run payment sandbox or test mode.
- Run permission checks.
- Check logs for errors.
- Confirm rollback path.

After deploying a phase:

- Create a test product.
- Place a test order.
- Confirm pending order creation.
- Confirm payment behavior.
- Confirm admin order visibility.
- Confirm customer order visibility.
- Confirm no private file is public.
- Confirm background jobs are running.

## Task Completion Gate

A task can be marked complete only when:

- Its own tests pass.
- Connected project tests still pass.
- APIs and shared data remain correct.
- Database changes are migrated safely.
- Idempotency behavior is checked where relevant.
- Audit behavior is checked where relevant.
- Notification behavior is checked where relevant.
- Documentation is updated.
- Regression testing is completed.
