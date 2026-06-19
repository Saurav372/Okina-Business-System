# Okina Craft Task List

## Task Fields

Each implementation task must include:

- Task ID
- Task Name
- Project
- Feature
- Description
- Depends On
- Blocks
- Affected Projects
- Database Impact
- API Impact
- Testing Required
- Status

Subtasks use the parent task ID plus a sequence number. Example: `A1.1.1`.

Detailed validation for each subtask is maintained in `docs/subtask-validation.md`.

Parent completion rule:

Each parent task is complete only when all required subtasks are complete, subtask acceptance criteria pass, parent-level integration tests pass, affected modules still work, and the related documentation/dependency records are updated.

## Platform A Tasks

| Task ID | Task Name | Project | Feature | Description | Depends On | Blocks | Affected Projects | Database Impact | API Impact | Testing Required | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| A1.1 | Final ERD and schema plan | Platform A | A1 | Define tables, relationships, public IDs, indexes, and phase-safe migration plan. | None | All implementation | A/B/C | Critical | Medium | Schema review | Completed |
| A1.2 | Repository and app scaffold plan | Platform A | A1 | Define repo shape, Laravel backend location, Astro frontend location, docs/deployment structure. | A1.1 | App scaffolding | A/B/C | Low | Low | Build/boot checklist | Completed |
| A1.3 | Modular backend structure | Platform A | A1 | Define Laravel module layout for CRM, Customers, Products, Orders, Payments, Inventory, Vendors, Finance, Shipping, Files, Notifications, Tracking, Settings. | A1.2 | Backend modules | A/C | Low | Medium | Module loading tests | Completed |
| A1.4 | Environment and hosting readiness check | Platform A | A1 | Confirm local and target hosting readiness for Laravel 13, Composer, MySQL, cron, queues, private storage, upload limits, SSL, webhooks, backups, and rollback before scaffold work. | A1.2, A1.3 | A1.5, A2.1 | A/B/C | Low | Low | Environment readiness checks | Completed |
| A1.5 | Repository and app scaffold implementation | Platform A | A1 | Create the real Laravel backend app, Astro frontend app, environment examples, local boot commands, and baseline build/test checks. | A1.4 | All implementation | A/B/C | Low | Medium | Backend boot/test and frontend build | Completed |
| A2.1 | Admin authentication | Platform A | A2 | Add protected admin login and base staff access. | A1.5 | Admin features | A/C | Medium | Medium | Login/access tests | Completed |
| A2.2 | Customer authentication | Platform A | A2 | Add customer login/register/session rules for website and account area. | A1.5 | Checkout/account | A/B | Medium | High | Customer auth tests | Completed |
| A2.3 | Role and permission model | Platform A | A2 | Define Super Admin, Admin, Sales, Inventory, Finance, Production roles and policies. | A2.1 | Admin workflows | A/C | High | Medium | Permission tests | Completed |
| A3.1 | Shared customers and addresses | Platform A | A3 | Create customer and address records usable by website and admin. | A1.1, A2.2 | Checkout, sales orders | A/B/C | High | High | Customer/address tests | Completed |
| A3.2 | Shared products, categories, variants and SKUs | Platform A | A3 | Create shared product catalog data with status/visibility separation and SKU references. | A1.1 | Catalog, cart, inventory | A/B/C | Critical | High | Catalog/SKU tests | In Progress |
| A4.1 | File upload service | Platform A | A4 | Store originals privately, validate MIME/type/size, create previews where possible, signed access, permissions, deletion rules, and upload security tests. | A1.1 | Customization, orders | A/B/C | High | High | Upload/security tests | Completed |
| A4.2 | Settings service | Platform A | A4 | Add business, payment, notification, SEO, upload, and integration settings foundation. | A1.1 | Payments, notifications | A/B/C | Medium | Medium | Settings tests | Completed |
| A4.3 | Queue, job and retry foundation | Platform A | A4 | Define queue behavior, retry safety, failed-job logs, and job deduplication. | A1.2 | Notifications, Sheets sync | A/C | Medium | Medium | Job retry tests | Completed |
| A4.4 | Notification event definitions | Platform A | A4 | Define event, recipient, channel, trigger, retry, deduplication, and template requirements. | A4.2, A4.3 | C6 notifications | A/B/C | Medium | Medium | Notification contract tests | Completed |

Verification note: completed on 2026-06-18. The implementation adds a shared notification event catalog, a typed notification event definition object, and feature tests covering the approved workflow events, recipient/channel rules, retry behavior, deduplication, and template requirements. `php artisan test` and `./vendor/bin/pint --test` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared idempotency key generator, an operation catalog for checkout/order/payment/inventory/notification/Sheets/job retry duplicate-handling rules, and feature tests covering stable key generation, hash fallback, and catalog outcomes. `php artisan test --filter=IdempotencyFoundationTest` and `./vendor/bin/pint --test app\\Support\\Idempotency tests\\Feature\\IdempotencyFoundationTest.php` passed.

| A4.5 | Idempotency foundation | Platform A | A4 | Define duplicate prevention keys for checkout, order creation, payments, stock movements, notifications, Sheets sync, and retries. | A1.1 | B3, C2, C6 | A/B/C | High | High | Duplicate request tests | Completed |

Verification note: completed on 2026-06-18. The implementation adds a shared audit event contract, a safe payload policy, an audit event catalog for order/payment/inventory/finance guidance, and feature tests covering contract shape, module guidance, and recursive redaction of sensitive values. `php artisan test --filter=AuditEventContractTest` and `./vendor/bin/pint --test app\\Contracts\\AuditEventContract.php app\\Support\\Audit tests\\Feature\\AuditEventContractTest.php` passed.

| A4.6 | Audit event/interface contract | Platform A | A4 | Define a lightweight audit event/interface that core modules can call before permanent audit storage is implemented. | A1.1, A4.5 | Order, payment, inventory, finance audit integration | A/C | Medium | Medium | Audit event contract tests | Completed |

| A5.1 | Shared order/payment domain model | Platform A | A5 | Define order types, order statuses, payment statuses, totals/balances, website-order rules, sales-order rules, and quotation conversion. | A1.1, A3.1, A3.2 | Checkout, admin orders, finance | A/B/C | Critical | Critical | Domain state tests | Completed |
| A5.2 | Cancellation and refund rules | Platform A | A5 | Define how orders are cancelled, how refunds are recorded, how refund status is calculated, and how cancellation/refund actions affect order and payment records. | A5.1 | Admin orders, finance | A/B/C | High | High | Cancellation/refund tests | Completed |
| A5.3 | Payment gateway service contract | Platform A | A5 | Define gateway interface, Cashfree adapter, payment attempts, verification, webhook contract, manual payments, refunds, and gateway failure handling. | A5.1, A4.5 | Website payment, finance | A/B/C | Critical | Critical | Payment contract tests | Completed |

## Project B Tasks

| Task ID | Task Name | Project | Feature | Description | Depends On | Blocks | Affected Projects | Database Impact | API Impact | Testing Required | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| B1.1 | Public category and product APIs | Project B | B1 | Expose public-safe category/product/SKU data for Astro. | A3.2 | Product pages | A/B | Low | High | API visibility tests | Completed |
| B1.2 | Astro catalog and product pages | Project B | B1 | Build product listing/detail/category pages using backend APIs. | B1.1 | Customization, checkout | B | Low | Medium | Page smoke tests | Completed |

Verification note: completed on 2026-06-18. The implementation adds a public catalog contract, public catalog rules, a public catalog catalog, a public catalog controller, API route registration, public visibility scopes on category/product models, and feature tests covering safe public category/product/SKU responses, detail routes, and Astro guidance. `php artisan test --filter=PublicCatalogApiTest` and `./vendor/bin/pint --test app\\Contracts\\PublicCatalogContract.php app\\Http\\Controllers\\Api\\PublicCatalogController.php app\\Models\\Product.php app\\Models\\ProductCategory.php app\\Providers\\AppServiceProvider.php app\\Support\\Products\\PublicCatalogRules.php app\\Support\\Products\\PublicCatalogCatalog.php tests\\Feature\\PublicCatalogApiTest.php routes\\api.php bootstrap\\app.php` passed.

| B2.1 | Customization option APIs | Project B | B2 | Expose size, print position, print method, SKU, and validation rules. | A3.2, A5.1 | Product customization UI | A/B/C | Medium | High | Validation tests | Completed |
| B2.2 | Upload and simple mockup preview | Project B | B2 | Support design upload, validation, product preview, placement controls, customization metadata, cart/order persistence, and admin file access. | A4.1, B2.1 | Cart/order customization | A/B/C | Medium | High | Upload/preview tests | In Progress |
| B3.1 | Cart and checkout with pending order creation | Project B | B3 | Validate cart, recalculate prices, require customer/address, detect bulk quantities, create pending order, create payment attempt, prevent duplicates, and handle failures. | A2.2, A3.1, A3.2, A5.1, B2.2.6 | Payment, admin order display | A/B/C | Critical | Critical | Checkout/idempotency tests | In Progress |
| B3.2 | Website payment adapter implementation | Project B | B3 | Use shared payment service and gateway adapter to initiate/verify website payment. | A5.3, B3.1 | Paid order flow | A/B/C | High | Critical | Payment attempt tests | Not Started |
| B3.3 | Payment webhook handling | Project B | B3 | Authenticate webhook, parse events, match attempts, create payment records, prevent duplicates, recalculate payment status, handle failures/refunds, and test logging/retries. | A4.5, A5.3, B3.2 | Finance, tracking | A/B/C | High | Critical | Webhook idempotency tests | Not Started |
| B4.1 | Customer dashboard | Project B | B4 | Show customer profile, addresses, orders, payments, uploaded designs, and support actions. | B3.1, C1.1 | Customer tracking | B/C | Medium | High | Access control tests | Not Started |
| B4.2 | Customer tracking page | Project B | B4 | Show customer-friendly order status, payment summary, shipment details, and support actions. | C1.1, C4.1 | Customer support workflow | B/C | Medium | High | Tracking privacy/status tests | Not Started |

## Project C Tasks

| Task ID | Task Name | Project | Feature | Description | Depends On | Blocks | Affected Projects | Database Impact | API Impact | Testing Required | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| C1.1 | Basic admin order and payment view | Project C | C1 | Display website pending/paid orders, payment records, status, customer, items, and uploads. | A2.3, A5.1, B3.1 | Tracking, operations | A/B/C | Medium | Medium | Admin order tests | Not Started |
| C1.2 | Sales order creation | Project C | C1 | Let staff create sales orders with customer selection, products/SKUs, quantities, customization, pricing, discount, advance/final payment structure, creation, editing rules, and confirmation. | C1.1, A5.1 | Split payments, inventory | A/C | High | Medium | Sales order tests | Not Started |
| C1.3 | Quotations and bulk-order conversion | Project C | C1 | Support bulk enquiry capture, quotation creation, items/pricing, status, approval, revision, sales-order conversion, and advance-payment recording. | C1.2, C3.1 | Bulk sales workflow | A/B/C | High | Medium | Quotation conversion tests | Not Started |
| C2.1 | Inventory movements and stock handling | Project C | C2 | Implement SKU stock balance, stock-in, stock-out, manual adjustment, order stock deduction, cancellation reversal, low-stock warning, movement history, and audit. | A3.2, C1.1 | Checkout warnings, production, purchase | A/B/C | High | Medium | Inventory movement tests | Not Started |
| C2.2 | Vendors and purchases | Project C | C2 | Add vendor management, purchase orders, purchase items, purchase status, receiving, partial receiving, purchase payment tracking, and vendor-order history. | C2.1 | Inventory reports | C | High | Medium | Purchase stock-in tests | Not Started |
| C3.1 | CRM lead module | Project C | C3 | Capture website/manual leads, sources, UTM/referrer/page data, statuses, notes, assignments. | A2.3, A3.1, A4.3 | Quotations, follow-ups | B/C | High | Medium | Lead tests | Not Started |
| C3.2 | Follow-up workflow | Project C | C3 | Add follow-up due dates, reminders, sales dashboard, overdue view, and activity timeline. | C3.1, A4.4 | Notifications | C | Medium | Medium | Follow-up tests | Not Started |
| C4.1 | Simple order processing | Project C | C4 | Allow authorized staff to change the main order status between Confirmed, In Production, Ready to Ship, Shipped, Delivered, and Cancelled. | C1.1 | Tracking | B/C | Medium | Medium | Status workflow tests | Not Started |
| C4.2 | Shipping details | Project C | C4 | Allow staff to save courier name, tracking number, tracking URL, shipping date, and delivery date. | C4.1 | Customer tracking | B/C | Medium | High | Shipping/tracking tests | Not Started |
| C5.1 | Finance payment and balance views | Project C | C5 | Show payment records, outstanding balances, split payments, and protected finance views. | A5.1, C1.1 | Refunds, reports | A/C | Medium | Medium | Finance access tests | Not Started |
| C5.2 | Refund management | Project C | C5 | Track refund requests, refund approvals, refund records, partial/full refunds, and payment-status recalculation without erasing original payment history. | A5.2, C5.1 | Reports/audit | C | Medium | Medium | Refund tests | Not Started |
| C5.3 | Expense management | Project C | C5 | Track approved business expenses separately from refunds, with permissions and reporting categories. | C5.1 | Reports/audit | C | Medium | Low | Expense tests | Not Started |
| C5.4 | Financial reports | Project C | C5 | Create payment, balance, refund, expense, sales, and protected finance reports. | C5.1, C5.2, C5.3 | Hardening | C | Medium | Low | Report accuracy tests | Not Started |
| C6.1 | Immutable audit log | Project C | C6 | Implement audit table design, order-change auditing, payment/refund auditing, inventory auditing, customer/product auditing, permission-change auditing, sensitive-data masking, viewing permissions, and retention rules. | A4.6, A5.1, C1.1 | Finance/inventory hardening | A/C | High | Medium | Audit immutability tests | Not Started |
| C6.2 | Notification implementation | Project C | C6 | Implement notification templates, channels, logs, retries, and deduplication. | A4.4, A4.5 | Automation | A/B/C | Medium | Medium | Notification tests | Not Started |
| C6.3 | Google Sheets backup sync | Project C | C6 | Queue deduplicated sync jobs for leads, orders, payments, inventory, customers, follow-ups, vendors. | A4.3, A4.5, C6.2 | Backup/reporting | A/C | Medium | Medium | Sheets failure/retry tests | Not Started |
| C6.4 | Backup, security, and regression gates | Project C | C6 | Define database backup, private-file backup, restore procedure, permission review, upload/payment/API security reviews, deployment checklist, regression checklist, and rollback procedure. | C6.1, C6.2, C6.3 | Production readiness | A/B/C | Medium | Medium | Full regression tests | Not Started |

## Required Subtask Breakdowns

### A1.1 Final ERD and schema plan

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A1.1.1 | Core users/roles schema | Completed |
| A1.1.2 | Customers/addresses schema | Completed |
| A1.1.3 | Products/variants/SKUs schema | Completed |
| A1.1.4 | Orders/order items schema | Completed |
| A1.1.5 | Payments/refunds schema | Completed |
| A1.1.6 | Inventory/vendors/purchases schema | Completed |
| A1.1.7 | CRM/quotations schema | Completed |
| A1.1.8 | Files/audit/notifications schema | Completed |
| A1.1.9 | Indexes, IDs, migrations | Completed |

### A3.2 Shared products, categories, variants and SKUs

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A3.2.1 | Categories | Completed |
| A3.2.2 | Products | Completed |
| A3.2.3 | Product variants | Completed |
| A3.2.4 | SKUs | Completed |
| A3.2.5 | Product status and visibility | Completed |
| A3.2.6 | Product images and print options | Completed |
| A3.2.7 | Admin management | Not Started |
| A3.2.8 | Public API data | Completed |

### A4.1 File upload service

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A4.1.1 | Validation | Completed |
| A4.1.2 | Private storage | Completed |
| A4.1.3 | Preview generation | Completed |
| A4.1.4 | Signed access | Completed |
| A4.1.5 | File permissions | Completed |
| A4.1.6 | Deletion rules | Completed |
| A4.1.7 | Upload security tests | Completed |

### A4.2 Settings service

Verification note: completed on 2026-06-18. The implementation adds a `settings` table, a `Setting` model, a `SettingsService` registry with grouped access conventions, seeded defaults for business, payment, notification, SEO, upload, and integration settings, and feature tests covering persistence, grouping, defaults, and retrieval. `php artisan migrate --force`, `php artisan test`, and `./vendor/bin/pint --test` passed.

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A4.2.1 | Settings storage foundation | Completed |
| A4.2.2 | Settings access conventions | Completed |
| A4.2.3 | Category or grouping rules | Completed |
| A4.2.4 | Defaults by category | Completed |
| A4.2.5 | Settings tests | Completed |

### A4.3 Queue, job and retry foundation

Verification note: completed on 2026-06-18. The implementation adds shared queue defaults, a reusable queued operation base class, a cache-backed deduplication helper, and safe failed-job logging. `php artisan test` and `./vendor/bin/pint --test` passed.

### A5.1 Shared order/payment domain model

Verification note: completed on 2026-06-18. The implementation adds a shared order type contract, an `OrderType` enum, an order type catalog with website/admin usage rules, and feature tests covering the two approved order types, labels, contract helpers, and usage guidance. `php artisan test --filter=OrderTypeContractTest` and `./vendor/bin/pint --test app\\Contracts\\OrderTypeContract.php app\\Enums\\OrderType.php app\\Support\\Orders tests\\Feature\\OrderTypeContractTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared order status contract, an `OrderStatus` enum, an order status catalog with operational usage rules, and feature tests covering the approved operational statuses, labels, terminal flags, and tracking guidance. `php artisan test --filter=OrderStatusContractTest` and `./vendor/bin/pint --test app\\Contracts\\OrderStatusContract.php app\\Enums\\OrderStatus.php app\\Support\\Orders tests\\Feature\\OrderStatusContractTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared payment status contract, a `PaymentStatus` enum, a payment status catalog with calculation rules sourced from payment and refund records, and feature tests covering the approved financial states, labels, state flags, and calculation guidance. `php artisan test --filter=PaymentStatusContractTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentStatusContract.php app\\Enums\\PaymentStatus.php app\\Support\\Payments tests\\Feature\\PaymentStatusContractTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared order totals contract, an `OrderTotals` value object, an order totals calculator, and feature tests covering order subtotal/discount/shipping/tax totals, paid and refund-aware balances, and serialization for later service usage. `php artisan test --filter=OrderTotalsContractTest` and `./vendor/bin/pint --test app\\Contracts\\OrderTotalsContract.php app\\Support\\Orders tests\\Feature\\OrderTotalsContractTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared website order contract, website order rules, a website order catalog, and feature tests covering pending-order creation before payment, idempotency usage, payment-attempt sequencing, and gateway-independent checkout guidance. `php artisan test --filter=WebsiteOrderRulesTest` and `./vendor/bin/pint --test app\\Contracts\\WebsiteOrderContract.php app\\Support\\Orders tests\\Feature\\WebsiteOrderRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared sales order contract, sales order rules, a sales order catalog, and feature tests covering admin/manual creation, approved-quotation conversion compatibility, confirmed initial operational state, advance/final payment support, and gateway independence. `php artisan test --filter=SalesOrderRulesTest` and `./vendor/bin/pint --test app\\Contracts\\SalesOrderContract.php app\\Support\\Orders tests\\Feature\\SalesOrderRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared quotation-to-order conversion contract, quotation conversion rules, a quotation conversion catalog, and feature tests covering approved quotation conversion, one-time conversion behavior, conversion idempotency, and sales-order compatibility. `php artisan test --filter=QuotationToOrderConversionRulesTest` and `./vendor/bin/pint --test app\\Contracts\\QuotationToOrderConversionContract.php app\\Support\\Orders tests\\Feature\\QuotationToOrderConversionRulesTest.php` passed.

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A5.1.1 | Order types | Completed |
| A5.1.2 | Order statuses | Completed |
| A5.1.3 | Payment statuses | Completed |
| A5.1.4 | Order totals and balances | Completed |
| A5.1.5 | Website-order rules | Completed |
| A5.1.6 | Sales-order rules | Completed |
| A5.1.7 | Quotation-to-order conversion | Completed |

### A5.2 Cancellation and refund rules

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A5.2.1 | Cancellation eligibility | Completed |
| A5.2.2 | Cancellation effects | Completed |
| A5.2.3 | Partial refund rules | Completed |
| A5.2.4 | Full refund rules | Completed |
| A5.2.5 | Payment-state recalculation | Completed |
| A5.2.6 | Audit requirements | Completed |

Verification note: completed on 2026-06-18. The implementation adds a shared cancellation eligibility contract, cancellation eligibility rules, a cancellation eligibility catalog, and feature tests covering which website orders and sales orders can be cancelled, status-based eligibility, and safety guidance that keeps cancellation separate from refund execution. `php artisan test --filter=CancellationEligibilityRulesTest` and `./vendor/bin/pint --test app\\Contracts\\CancellationEligibilityContract.php app\\Support\\Orders tests\\Feature\\CancellationEligibilityRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared cancellation effect contract, cancellation effect rules, a cancellation effect catalog, and feature tests covering that cancellation sets the order to `cancelled` without changing payment facts, triggering refunds, or reversing stock, while keeping customer-visible cancellation safe. `php artisan test --filter=CancellationEffectRulesTest` and `./vendor/bin/pint --test app\\Contracts\\CancellationEffectContract.php app\\Support\\Orders\\CancellationEffectRules.php app\\Support\\Orders\\CancellationEffectCatalog.php tests\\Feature\\CancellationEffectRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared partial refund contract, partial refund rules, a partial refund catalog, and feature tests covering that partial refunds increase refund totals, map to `partially_refunded` or `refunded` based on remaining net paid amount, preserve original payments, and stay separate from cancellation effects. `php artisan test --filter=PartialRefundRulesTest` and `./vendor/bin/pint --test app\\Contracts\\PartialRefundContract.php app\\Support\\Payments\\PartialRefundRules.php app\\Support\\Payments\\PartialRefundCatalog.php tests\\Feature\\PartialRefundRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared full refund contract, full refund rules, a full refund catalog, and feature tests covering that full refunds restore the full successful paid amount, mark the order refunded, keep original payments intact, and keep customer-safe visibility while leaving payment-state recalculation and audit storage to later subtasks. `php artisan test --filter=FullRefundRulesTest` and `./vendor/bin/pint --test app\\Contracts\\FullRefundContract.php app\\Support\\Payments\\FullRefundRules.php app\\Support\\Payments\\FullRefundCatalog.php tests\\Feature\\FullRefundRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation adds a shared payment-state recalculation contract, payment-state recalculation rules, a payment-state recalculation catalog, and feature tests covering that unpaid, partially paid, paid, partially refunded, and refunded states are derived from successful payment and refund records with refund states taking priority once refunds exist. `php artisan test --filter=PaymentStateRecalculationRulesTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentStateRecalculationContract.php app\\Support\\Payments\\PaymentStateRecalculationRules.php app\\Support\\Payments\\PaymentStateRecalculationCatalog.php tests\\Feature\\PaymentStateRecalculationRulesTest.php` passed.

Verification note: completed on 2026-06-18. The implementation extends the shared audit event catalog with cancellation and refund lifecycle events and feature tests covering safe order-cancellation payloads, refund-request payloads, refund-recorded payloads, and recursive redaction of sensitive audit payload values. `php artisan test --filter=AuditEventContractTest` and `./vendor/bin/pint --test app\\Support\\Audit\\AuditEventCatalog.php tests\\Feature\\AuditEventContractTest.php` passed.

### A5.3 Payment gateway service contract

| Subtask ID | Subtask Name | Status |
|---|---|---|
| A5.3.1 | Gateway interface | Completed |
| A5.3.2 | Cashfree adapter | Completed |
| A5.3.3 | Payment attempts | Completed |
| A5.3.4 | Payment verification | Completed |
| A5.3.5 | Webhook contract | Completed |
| A5.3.6 | Manual payment support | Completed |
| A5.3.7 | Refund interface | Completed |
| A5.3.8 | Gateway failure handling | Completed |

Verification note: A5.3.1 completed on 2026-06-18. The implementation adds a shared payment gateway contract, gateway-agnostic payment gateway rules, a payment gateway catalog, and feature tests covering that checkout and admin depend on an interface that supports online initiation, webhook verification, refunds, idempotency, and order-first payment flow without hardcoding a specific provider. `php artisan test --filter=PaymentGatewayContractTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentGatewayContract.php app\\Support\\Payments\\PaymentGatewayRules.php app\\Support\\Payments\\PaymentGatewayCatalog.php tests\\Feature\\PaymentGatewayContractTest.php` passed.

### B2.2 Upload and simple mockup preview

| Subtask ID | Subtask Name | Status |
|---|---|---|
| B2.2.1 | Design upload | Completed |
| B2.2.2 | File validation | Completed |
| B2.2.3 | Product preview | Completed |
| B2.2.4 | Placement controls | Completed |
| B2.2.5 | Customization metadata | Completed |
| B2.2.6 | Cart persistence | Completed |
| B2.2.7 | Order persistence | Not Started |
| B2.2.8 | Admin file access | Not Started |

Verification note: B2.2.1 completed on 2026-06-19. The implementation adds the design upload API flow, private original file storage, public-safe upload metadata, and feature tests covering allowed uploads, validation rejection, and signed preview URL generation. `php artisan test --filter=DesignUploadFlowTest` passed.

Verification note: B2.2.2 completed on 2026-06-19. The upload flow now rejects invalid file payloads before storage and returns validation errors for unsafe file and customization selection input. `php artisan test --filter=DesignUploadFlowTest` passed.


Verification note: B2.2.3 completed on 2026-06-19. The preview flow now returns a public-safe SVG mockup for uploaded designs, rejects tampering and expired signed preview links, and keeps preview metadata free of raw storage paths. `php artisan test --filter=DesignUploadFlowTest` and `./vendor/bin/pint --test tests\Feature\DesignUploadFlowTest.php` passed.

Verification note: B2.2.4 completed on 2026-06-19. The placement controls UI now refreshes a fresh signed mockup preview for uploaded designs, keeps placement values within safe bounds, and preserves the signed-link safety path. `php artisan test --filter=DesignUploadFlowTest` and `npm run build` passed.

Verification note: B2.2.5 completed on 2026-06-19. The implementation adds a reusable customization snapshot builder, normalizes placement metadata, stores public-safe file and mockup preview references without raw storage paths, and keeps the signed preview-link flow compatible with later cart/order persistence. `php artisan test`, `./vendor/bin/pint --test app\\Http\\Controllers\\Api\\ProductCustomizationController.php app\\Support\\Products\\CustomizationSnapshotBuilder.php app\\Services\\FileUploadService.php app\\Services\\SettingsService.php tests\\Feature\\DesignUploadFlowTest.php tests\\Feature\\SettingsServiceTest.php`, and `npm run build` passed.

Verification note: B2.2.6 completed on 2026-06-19. The real design-upload customization snapshot now persists through `/api/cart/items`, remains attached across cart reloads, preserves public file/mockup references, and excludes private storage paths plus internal cart/product/SKU identifiers from customer payloads. `php artisan test --filter=DesignUploadFlowTest`, `php artisan test --filter=CartStorageTest`, and `./vendor/bin/pint --test tests/Feature/DesignUploadFlowTest.php` passed.

Planning note: B2.2.7 and B2.2.8 are deferred bridge subtasks. They must not block B3.1 checkout work because B3.1 only needs customization metadata through cart persistence, which is complete in B2.2.6. Use `docs/project-b-c-build-runway.md` before moving to the next Project B/C task.

### B3.1 Cart and checkout with pending order creation

| Subtask ID | Subtask Name | Status |
|---|---|---|
| B3.1.1 | Cart storage | Completed |
| B3.1.2 | Cart item validation | Completed |
| B3.1.3 | Price recalculation | Project B | B3 | Validate cart, recalculate prices, require customer/address, detect bulk quantities, create pending order, create payment attempt, prevent duplicates, and handle failures. | A2.2, A3.1, A3.2, A5.1, B2.2.6 | Payment, admin order display | A/B/C | Critical | Critical | Checkout/idempotency tests | Completed |
| B3.1.4 | Customer and address validation | Completed |
| B3.1.5 | Bulk quantity detection | Completed |
| B3.1.6 | Pending-order creation | Not Started |
| B3.1.7 | Order item/customization storage | Not Started |
| B3.1.8 | Payment-attempt creation | Not Started |
| B3.1.9 | Duplicate checkout prevention | Not Started |
| B3.1.10 | Failed checkout handling | Not Started |

Verification note: B3.1.1 completed on 2026-06-19. The database-backed cart layer now stores guest/session carts, attaches optional customer ownership, preserves public-safe customization snapshots, and protects cart ownership through session-scoped tokens. `php artisan test --filter=CartStorageTest` and `./vendor/bin/pint --test app/Support/Products/CustomizationSnapshotBuilder.php app/Services/CartService.php app/Services/CartResponsePresenter.php app/Http/Controllers/Api/CartController.php app/Http/Requests/Cart/StoreCartItemRequest.php app/Http/Requests/Cart/UpdateCartItemRequest.php app/Models/Cart.php app/Models/CartItem.php tests/Feature/CartStorageTest.php` passed.

Verification note: B3.1.2 completed on 2026-06-19. The cart validation endpoint now checks product availability, SKU checkout eligibility, quantity limits, and customization-rule drift while returning a public-safe validation payload. `php artisan test --filter=CartValidationTest`, `php artisan test --filter=CartStorageTest`, `php artisan test --filter=CustomizationOptionApiTest`, and `./vendor/bin/pint --test app/Http/Controllers/Api/CartController.php app/Services/CartValidationService.php routes/api.php tests/Feature/CartValidationTest.php` passed.

Verification note: B3.1.3 completed on 2026-06-19. The backend now recalculates cart line totals and cart summaries from current SKU prices with product base price fallback, returns public-safe pricing fields on cart and validation payloads, and keeps stale browser pricing out of checkout decisions. `php artisan test --filter=CartStorageTest`, `php artisan test --filter=CartValidationTest`, and `./vendor/bin/pint app/Services/CartPricingService.php app/Services/CartResponsePresenter.php app/Models/ProductSku.php tests/Feature/CartStorageTest.php tests/Feature/CartValidationTest.php` passed.

### B3.3 Payment webhook handling

| Subtask ID | Subtask Name | Status |
|---|---|---|
| B3.3.1 | Webhook authentication | Not Started |
| B3.3.2 | Event parsing | Not Started |
| B3.3.3 | Payment-attempt matching | Not Started |
| B3.3.4 | Payment record creation | Not Started |
| B3.3.5 | Duplicate webhook prevention | Not Started |
| B3.3.6 | Payment-status recalculation | Not Started |
| B3.3.7 | Failed-payment handling | Not Started |
| B3.3.8 | Refund webhook handling | Not Started |
| B3.3.9 | Logging and retry tests | Not Started |

### C1.2 Sales order creation

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C1.2.1 | Customer selection | Not Started |
| C1.2.2 | Product/SKU selection | Not Started |
| C1.2.3 | Quantity and customization | Not Started |
| C1.2.4 | Pricing and discount | Not Started |
| C1.2.5 | Advance/final payment structure | Not Started |
| C1.2.6 | Order creation | Not Started |
| C1.2.7 | Order editing rules | Not Started |
| C1.2.8 | Order confirmation | Not Started |

### C1.3 Quotations and bulk-order conversion

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C1.3.1 | Bulk enquiry capture | Not Started |
| C1.3.2 | Quotation creation | Not Started |
| C1.3.3 | Quotation items and pricing | Not Started |
| C1.3.4 | Quotation status | Not Started |
| C1.3.5 | Customer approval | Not Started |
| C1.3.6 | Quotation revision | Not Started |
| C1.3.7 | Sales-order conversion | Not Started |
| C1.3.8 | Advance-payment recording | Not Started |

### C2.1 Inventory movements and stock handling

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C2.1.1 | SKU stock balance | Not Started |
| C2.1.2 | Stock-in | Not Started |
| C2.1.3 | Stock-out | Not Started |
| C2.1.4 | Manual adjustment | Not Started |
| C2.1.5 | Order stock deduction | Not Started |
| C2.1.6 | Cancellation stock reversal | Not Started |
| C2.1.7 | Low-stock warning | Not Started |
| C2.1.8 | Movement history and audit | Not Started |

### C2.2 Vendors and purchases

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C2.2.1 | Vendor management | Not Started |
| C2.2.2 | Purchase order creation | Not Started |
| C2.2.3 | Purchase order items | Not Started |
| C2.2.4 | Purchase status | Not Started |
| C2.2.5 | Stock receiving | Not Started |
| C2.2.6 | Partial stock receiving | Not Started |
| C2.2.7 | Purchase payment tracking | Not Started |
| C2.2.8 | Vendor-order history | Not Started |

### C5.2 Refund management

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C5.2.1 | Refund request | Not Started |
| C5.2.2 | Refund approval | Not Started |
| C5.2.3 | Partial refund | Not Started |
| C5.2.4 | Full refund | Not Started |
| C5.2.5 | Refund payment record | Not Started |
| C5.2.6 | Payment-status recalculation | Not Started |
| C5.2.7 | Refund audit trail | Not Started |

### C5.3 Expense management

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C5.3.1 | Expense categories | Not Started |
| C5.3.2 | Expense entry | Not Started |
| C5.3.3 | Expense approval rules | Not Started |
| C5.3.4 | Expense permissions | Not Started |
| C5.3.5 | Expense reporting data | Not Started |

### C6.1 Immutable audit log

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C6.1.1 | Audit table design | Not Started |
| C6.1.2 | Order-change auditing | Not Started |
| C6.1.3 | Payment/refund auditing | Not Started |
| C6.1.4 | Inventory auditing | Not Started |
| C6.1.5 | Customer/product auditing | Not Started |
| C6.1.6 | Permission-change auditing | Not Started |
| C6.1.7 | Sensitive-data masking | Not Started |
| C6.1.8 | Audit viewing permissions | Not Started |
| C6.1.9 | Retention rules | Not Started |

### C6.4 Backup, security, and regression gates

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C6.4.1 | Database backup | Not Started |
| C6.4.2 | Private-file backup | Not Started |
| C6.4.3 | Restore procedure | Not Started |
| C6.4.4 | Permission review | Not Started |
| C6.4.5 | Upload security review | Not Started |
| C6.4.6 | Payment security review | Not Started |
| C6.4.7 | API security review | Not Started |
| C6.4.8 | Deployment checklist | Not Started |
| C6.4.9 | Regression test checklist | Not Started |
| C6.4.10 | Rollback procedure | Not Started |


Verification note: completed on 2026-06-19. The implementation adds a shared Astro catalog fetch helper, a catalog layout, a root product listing page, a category listing page, a category detail page, and a product detail page wired to the backend public catalog API. 
pm run build passed after starting the local backend API on port 8000.



Verification note: completed on 2026-06-19. The implementation adds a customization option contract, a customization option catalog, a customization rules service, a public customization-options API route, and feature tests covering public-safe option groups, print option defaults, SKU matching, and invalid customization rejection. php artisan test --filter=CustomizationOptionApiTest passed.






