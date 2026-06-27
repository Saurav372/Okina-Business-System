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
| A3.2 | Shared products, categories, variants and SKUs | Platform A | A3 | Create shared product catalog data with status/visibility separation and SKU references. | A1.1 | Catalog, cart, inventory | A/B/C | Critical | High | Catalog/SKU tests | Completed |
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
| B2.2 | Upload and simple mockup preview | Project B | B2 | Support design upload, validation, product preview, placement controls, customization metadata, cart/order persistence, and admin file access. | A4.1, B2.1 | Cart/order customization | A/B/C | Medium | High | Upload/preview tests | Completed |
| B3.1 | Cart and checkout with pending order creation | Project B | B3 | Validate cart, recalculate prices, require customer/address, detect bulk quantities, create pending order, create payment attempt, prevent duplicates, and handle failures. | A2.2, A3.1, A3.2, A5.1, B2.2.6 | Payment, admin order display | A/B/C | Critical | Critical | Checkout/idempotency tests | Completed |
| B3.2 | Website payment adapter implementation | Project B | B3 | Use shared payment service and gateway adapter to initiate/verify website payment. | A5.3, B3.1 | Paid order flow | A/B/C | High | Critical | Payment attempt tests | Completed |
| B3.3 | Payment webhook handling | Project B | B3 | Authenticate webhook, parse events, match attempts, create payment records, prevent duplicates, recalculate payment status, handle failures/refunds, and test logging/retries. | A4.5, A5.3, B3.2 | Finance, tracking | A/B/C | High | Critical | Webhook idempotency tests | Completed |

Verification note: completed on 2026-06-20. The webhook flow now authenticates Cashfree callbacks, deduplicates provider event IDs, creates payment and refund records from safe summaries, updates the matched payment attempt, and recalculates payment status from the new payment/refund records. `php artisan test --filter=PaymentWebhookProcessingTest`, `php artisan test`, and `./vendor/bin/pint app/Models/Order.php app/Models/Payment.php app/Models/PaymentAttempt.php app/Models/PaymentWebhookLog.php app/Models/Refund.php app/Services/PaymentWebhookProcessingService.php app/Http/Controllers/Api/PaymentWebhookController.php tests/Feature/PaymentWebhookProcessingTest.php` passed.
Verification note: completed on 2026-06-20. The website payment handoff now uses the shared payment gateway contract, persists an initiated payment attempt with public-safe gateway data, and reuses the same pending order and attempt across duplicate submissions and failed retries. php artisan test --filter=CheckoutPendingOrderTest, php artisan test --filter=PaymentGatewayContractTest, php artisan test, and ./vendor/bin/pint app/Services/CheckoutPendingOrderService.php app/Services/WebsitePaymentInitiationService.php app/Support/Payments/PaymentGatewayRules.php tests/Feature/CheckoutPendingOrderTest.php tests/Feature/PaymentGatewayContractTest.php passed.
| B4.1 | Customer dashboard | Project B | B4 | Show customer profile, addresses, orders, payments, uploaded designs, and support actions. | B3.1, C1.1 | Customer tracking | B/C | Medium | High | Access control tests | Completed |

Verification note: completed on 2026-06-22. The customer dashboard is fully implemented in the Astro frontend utilizing client-side fetch calls (CORS/Credentials enabled) to the backend customer API endpoints. Features include profile display, shipping/billing address CRUD + defaults sync, order/payment history, tracking status timeline, temporary signed preview links for design mockups, and cart reorders. The implementation is protected by session-based authentication and is verified by a comprehensive feature test suite (`CustomerDashboardApiTest.php`).

| B4.2 | Customer tracking page | Project B | B4 | Show customer-friendly order status, payment summary, shipment details, and support actions. | C1.1, C4.1 | Customer support workflow | B/C | Medium | High | Tracking privacy/status tests | Completed |

Verification note: completed on 2026-06-22. The customer tracking page is fully implemented in the Astro frontend utilizing client-side fetch calls to backend API endpoints (dynamic timeline generator). Feature tests in `CustomerTrackingApiTest` cover website and sales order timeline calculations, customer API data exposure, admin status update validations with timestamps, admin shipping detail updates, and role-based permissions protection. All 233 tests passed, and Laravel Pint formatting is applied.

## Project C Tasks

| Task ID | Task Name | Project | Feature | Description | Depends On | Blocks | Affected Projects | Database Impact | API Impact | Testing Required | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| C1.1 | Basic admin order and payment view | Project C | C1 | Provide a read-only, permission-safe Filament view of website orders, payment and refund history, customer/address snapshots, items, and linked uploads. Status changes, manual payments, refunds, shipping, CRM, inventory, and reports remain out of scope. | A2.3, A4.1, A5.1, A5.2, B3.1, B3.3 | Tracking, operations, finance views | A/B/C | Medium | Medium | Admin order, payment, file-access, and authorization tests | Completed |
| C1.2 | Sales order creation | Project C | C1 | Let staff create sales orders with customer selection, products/SKUs, quantities, customization, pricing, discount, advance/final payment structure, creation, editing rules, and confirmation. | C1.1, A5.1 | Split payments, inventory | A/C | High | Medium | Sales order tests | Completed |
| C1.3 | Quotations and bulk-order conversion | Project C | C1 | Support bulk enquiry capture, quotation creation, items/pricing, status, approval, revision, sales-order conversion, and advance-payment recording. | C1.2, C3.1 | Bulk sales workflow | A/B/C | High | Medium | Quotation conversion tests | Completed |
| C2.1 | Inventory movements and stock handling | Project C | C2 | Implement SKU stock balance, stock-in, stock-out, manual adjustment, order stock deduction, cancellation reversal, low-stock warning, movement history, and audit. | A3.2, C1.1 | Checkout warnings, production, purchase | A/B/C | High | Medium | Inventory movement tests | Not Started |
| C2.2 | Vendors and purchases | Project C | C2 | Add vendor management, purchase orders, purchase items, purchase status, receiving, partial receiving, purchase payment tracking, and vendor-order history. | C2.1 | Inventory reports | C | High | Medium | Purchase stock-in tests | Not Started |
| C3.1 | CRM lead module | Project C | C3 | Capture website/manual leads, sources, UTM/referrer/page data, statuses, notes, assignments. | A2.3, A3.1, A4.3 | Quotations, follow-ups | B/C | High | Medium | Lead tests | Not Started |
| C3.2 | Follow-up workflow | Project C | C3 | Add follow-up due dates, reminders, sales dashboard, overdue view, and activity timeline. | C3.1, A4.4 | Notifications | C | Medium | Medium | Follow-up tests | Not Started |
| C4.1 | Simple order processing | Project C | C4 | Allow authorized staff to change the main order status between Confirmed, In Production, Ready to Ship, Shipped, Delivered, and Cancelled. | C1.1 | Tracking | B/C | Medium | Medium | Status workflow tests | Completed |

Verification note: completed on 2026-06-22. Status changes are implemented under admin controller endpoints, validating status rules and timestamps for confirmed, cancelled, ready to ship, shipped, and delivered states. Role-based permissions verify that only authorized staff roles can update status fields. Covered by `CustomerTrackingApiTest`.

| C4.2 | Shipping details | Project C | C4 | Allow staff to save courier name, tracking number, tracking URL, shipping date, and delivery date. | C4.1 | Customer tracking | B/C | Medium | High | Shipping/tracking tests | Completed |

Verification note: completed on 2026-06-22. Shipment details (courier name, tracking number, URL, estimated delivery) are saved via admin endpoints, with customer order detail endpoints returning safe tracking payloads. Role-based permissions protect the endpoints. Covered by `CustomerTrackingApiTest`.
| C5.1 | Finance payment and balance views | Project C | C5 | Show payment records, outstanding balances, split payments, and protected finance views. | A5.1, C1.1 | Refunds, reports | A/C | Medium | Medium | Finance access tests | Completed |
| C5.2 | Refund management | Project C | C5 | Track refund requests, refund approvals, refund records, partial/full refunds, and payment-status recalculation without erasing original payment history. | A5.2, C5.1 | Reports/audit | C | Medium | Medium | Refund tests | Completed |
| C5.3 | Expense management | Project C | C5 | Track approved business expenses separately from refunds, with permissions and reporting categories. | C5.1 | Reports/audit | C | Medium | Low | Expense tests | Completed |
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
| A3.2.7 | Admin management | Completed |
| A3.2.8 | Public API data | Completed |

Verification note: A3.2.7 completed on 2026-06-22. The implementation adds `ProductResource` and `ProductCategoryResource` registration classes, `ProductIndexCatalog`, `ProductDetailCatalog`, `CategoryIndexCatalog`, `CategoryDetailCatalog` support classes, `ProductPolicy` and `ProductCategoryPolicy` (wired via `Gate::policy` in `AppServiceProvider`), and `AdminCatalogManagementTest` covering resource registration, catalog structure, finance-field exclusion, `canAccess`/`canManage` boundaries, and Policy-via-Gate checks for all 6 staff roles. `php artisan test --filter=AdminCatalogManagementTest` (24 tests, 87 assertions) and `php artisan test` (197 tests, 1332 assertions) passed. `./vendor/bin/pint` applied and passed.


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
| B2.2.7 | Order persistence | Completed |
| B2.2.8 | Admin file access | Completed |

Verification note: B2.2.1 completed on 2026-06-19. The implementation adds the design upload API flow, private original file storage, public-safe upload metadata, and feature tests covering allowed uploads, validation rejection, and signed preview URL generation. `php artisan test --filter=DesignUploadFlowTest` passed.

Verification note: B2.2.2 completed on 2026-06-19. The upload flow now rejects invalid file payloads before storage and returns validation errors for unsafe file and customization selection input. `php artisan test --filter=DesignUploadFlowTest` passed.


Verification note: B2.2.3 completed on 2026-06-19. The preview flow now returns a public-safe SVG mockup for uploaded designs, rejects tampering and expired signed preview links, and keeps preview metadata free of raw storage paths. `php artisan test --filter=DesignUploadFlowTest` and `./vendor/bin/pint --test tests\Feature\DesignUploadFlowTest.php` passed.

Verification note: B2.2.4 completed on 2026-06-19. The placement controls UI now refreshes a fresh signed mockup preview for uploaded designs, keeps placement values within safe bounds, and preserves the signed-link safety path. `php artisan test --filter=DesignUploadFlowTest` and `npm run build` passed.

Verification note: B2.2.5 completed on 2026-06-19. The implementation adds a reusable customization snapshot builder, normalizes placement metadata, stores public-safe file and mockup preview references without raw storage paths, and keeps the signed preview-link flow compatible with later cart/order persistence. `php artisan test`, `./vendor/bin/pint --test app\\Http\\Controllers\\Api\\ProductCustomizationController.php app\\Support\\Products\\CustomizationSnapshotBuilder.php app\\Services\\FileUploadService.php app\\Services\\SettingsService.php tests\\Feature\\DesignUploadFlowTest.php tests\\Feature\\SettingsServiceTest.php`, and `npm run build` passed.

Verification note: B2.2.6 completed on 2026-06-19. The real design-upload customization snapshot now persists through `/api/cart/items`, remains attached across cart reloads, preserves public file/mockup references, and excludes private storage paths plus internal cart/product/SKU identifiers from customer payloads. `php artisan test --filter=DesignUploadFlowTest`, `php artisan test --filter=CartStorageTest`, and `./vendor/bin/pint --test tests/Feature/DesignUploadFlowTest.php` passed.

Verification note: B2.2.7 completed on 2026-06-22. Verified that customization snapshot survives order creation and is persisted correctly on order items. Tested via AdminOrderItemFileAccessTest.php. `php artisan test` passed.

Verification note: B2.2.8 completed on 2026-06-22. Authorized staff with `orders.view` and `files.download_private` permissions can access private design files preview/download via public_id order-scoped routes, while unauthorized/unauthenticated users are strictly blocked. Tested via AdminOrderItemFileAccessTest.php. `php artisan test` passed.

### B3.1 Cart and checkout with pending order creation

| Subtask ID | Subtask Name | Status |
|---|---|---|
| B3.1.1 | Cart storage | Completed |
| B3.1.2 | Cart item validation | Completed |
| B3.1.3 | Price recalculation | Project B | B3 | Validate cart, recalculate prices, require customer/address, detect bulk quantities, create pending order, create payment attempt, prevent duplicates, and handle failures. | A2.2, A3.1, A3.2, A5.1, B2.2.6 | Payment, admin order display | A/B/C | Critical | Critical | Checkout/idempotency tests | Completed |
| B3.1.4 | Customer and address validation | Completed |
| B3.1.5 | Bulk quantity detection | Completed |
| B3.1.6 | Pending-order creation | Completed |
| B3.1.7 | Order item/customization storage | Completed |
| B3.1.8 | Payment-attempt creation | Completed |
| B3.1.9 | Duplicate checkout prevention | Completed |
| B3.1.10 | Failed checkout handling | Completed |

Verification note: B3.1.1 completed on 2026-06-19. The database-backed cart layer now stores guest/session carts, attaches optional customer ownership, preserves public-safe customization snapshots, and protects cart ownership through session-scoped tokens. `php artisan test --filter=CartStorageTest` and `./vendor/bin/pint --test app/Support/Products/CustomizationSnapshotBuilder.php app/Services/CartService.php app/Services/CartResponsePresenter.php app/Http/Controllers/Api/CartController.php app/Http/Requests/Cart/StoreCartItemRequest.php app/Http/Requests/Cart/UpdateCartItemRequest.php app/Models/Cart.php app/Models/CartItem.php tests/Feature/CartStorageTest.php` passed.

Verification note: B3.1.2 completed on 2026-06-19. The cart validation endpoint now checks product availability, SKU checkout eligibility, quantity limits, and customization-rule drift while returning a public-safe validation payload. `php artisan test --filter=CartValidationTest`, `php artisan test --filter=CartStorageTest`, `php artisan test --filter=CustomizationOptionApiTest`, and `./vendor/bin/pint --test app/Http/Controllers/Api/CartController.php app/Services/CartValidationService.php routes/api.php tests/Feature/CartValidationTest.php` passed.

Verification note: B3.1.3 completed on 2026-06-19. The backend now recalculates cart line totals and cart summaries from current SKU prices with product base price fallback, returns public-safe pricing fields on cart and validation payloads, and keeps stale browser pricing out of checkout decisions. `php artisan test --filter=CartStorageTest`, `php artisan test --filter=CartValidationTest`, and `./vendor/bin/pint app/Services/CartPricingService.php app/Services/CartResponsePresenter.php app/Models/ProductSku.php tests/Feature/CartStorageTest.php tests/Feature/CartValidationTest.php` passed.

Verification note: B3.1.6 completed on 2026-06-19. The checkout flow now creates a pending website order before payment attempts start, stores customer and address snapshots with public-safe order data, and exposes a checkout handoff payload for the next payment step. `php artisan test --filter=CheckoutPendingOrderTest`, `php artisan test --filter=CheckoutValidationTest`, `php artisan test --filter=CartValidationTest`, `php artisan test --filter=CartStorageTest`, and `./vendor/bin/pint --test app/Http/Controllers/Api/CartController.php app/Models/Order.php app/Services/CheckoutPendingOrderService.php routes/api.php database/factories/OrderFactory.php database/migrations/2026_06_19_000003_create_orders_table.php tests/Feature/CheckoutPendingOrderTest.php` passed.
Verification note: B3.1.10 completed on 2026-06-20. The checkout retry path now reuses the existing pending order and payment attempt, surfaces a public-safe failed-payment response when the attempt is already terminally failed, and keeps duplicate checkout submissions from creating extra records. `php artisan test --filter=CheckoutPendingOrderTest`, `php artisan test`, and `./vendor/bin/pint app/Services/CheckoutPendingOrderService.php tests/Feature/CheckoutPendingOrderTest.php` passed.

### B3.3 Payment webhook handling

| Subtask ID | Subtask Name | Status |
|---|---|---|
| B3.3.1 | Webhook authentication | Completed |
| B3.3.2 | Event parsing | Completed |
| B3.3.3 | Payment-attempt matching | Completed |
| B3.3.4 | Payment record creation | Completed |
| B3.3.5 | Duplicate webhook prevention | Completed |
| B3.3.6 | Payment-status recalculation | Completed |
| B3.3.7 | Failed-payment handling | Completed |
| B3.3.8 | Refund webhook handling | Completed |
| B3.3.9 | Logging and retry tests | Completed |

### B4.1 Customer dashboard

| Subtask ID | Subtask Name | Status |
|---|---|---|
| B4.1.1 | Dashboard UI & Routing | Completed |
| B4.1.2 | API route definitions | Completed |
| B4.1.3 | Address management | Completed |
| B4.1.4 | Order details & tracking | Completed |
| B4.1.5 | Reorder & support actions | Completed |
| B4.1.6 | Test suite, linting & PHPStan | Completed |

### C1.1 Basic admin order and payment view

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C1.1.1 | Admin order resource and authorization boundary | Completed |
| C1.1.2 | Website order index, scopes, and filters | Completed |
| C1.1.3 | Read-only order detail and customer/address snapshots | Completed |
| C1.1.4 | Payment, refund, and payment-attempt history | Completed |
| C1.1.5 | Order item and customization snapshot presentation | Completed |
| B2.2.8 | Authorized admin design-file access bridge | Completed |
| C1.1.6 | Read-only scope guard and regression verification | Completed |

Verification note: C1.1.1 completed on 2026-06-20. The boundary adds a read-only order resource shell, a lightweight admin resource catalog, and tests covering permission-gated access plus the blocked write-action set. `php artisan test --filter=AdminOrderResourceBoundaryTest` and `php artisan test` passed.
Verification note: C1.1.2 completed on 2026-06-20. The implementation adds a conservative website-order index definition, website-order query scope, admin order index catalog, approved status/date/design filters, and feature tests covering website-only queries, scope/filter application, safe summaries, and registration metadata. `php artisan test --filter=AdminOrderIndexTest`, `php artisan test --filter=AdminOrderResourceBoundaryTest`, and `php artisan test` passed.
Verification note: C1.1.3 completed on 2026-06-20. The implementation adds a read-only order detail catalog, snapshot-only customer and address rendering from stored order data, and feature tests covering permission-gated access, read-only registration metadata, and snapshot rendering that stays independent from live customer and address relation labels. `php artisan test --filter=AdminOrderDetailTest`, `php artisan test`, and `./vendor/bin/pint --test app/Filament/Resources/Orders/OrderResource.php app/Support/Admin/OrderDetailCatalog.php tests/Feature/AdminOrderDetailTest.php` passed.
Verification note: C1.1.4 completed on 2026-06-20. The detail surface now includes stored payment, refund, and payment-attempt history from shared order/payment records, keeps finance-sensitive fields and raw payloads out of the presenter, and preserves the read-only order boundary. `php artisan test --filter=AdminOrderDetailTest`, `php artisan test`, and `./vendor/bin/pint --test app/Support/Admin/OrderDetailCatalog.php tests/Feature/AdminOrderDetailTest.php` passed.
Verification note: C1.1.5 completed on 2026-06-22. Order items and customization snapshots are rendered in the order detail catalog, generating signed preview URLs best-effort and excluding raw storage paths. Tested via AdminOrderItemFileAccessTest.php. `php artisan test` passed.
Verification note: C1.1.6 completed on 2026-06-22. Staff with read-only access cannot create, edit, status-update, or delete orders, and the detail view contains no mutation surface. Index catalogs correctly scope to website orders only. Tested via AdminOrderItemFileAccessTest.php. `php artisan test` passed.

### C3.1 CRM lead module

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C3.1.1 | Lead data model and safe migration | Completed |
| C3.1.2 | Manual lead capture | Completed |
| C3.1.3 | Website/bulk lead capture endpoint | Completed |
| C3.1.4 | Source, referrer, page, and UTM attribution | Completed |
| C3.1.5 | Lead lifecycle, ownership, and assignment | Completed |
| C3.1.6 | Notes and activity timeline | Completed |
| C3.1.7 | Lead authorization, list/detail views, and regression tests | Completed |

Verification note: C3.1.1 completed on 2026-06-23. The implementation adds a safe `create_leads_table` migration with all fields and 8 composite indexes from `crm-quotations-schema.md`, a `Lead` Eloquent model with auto-generated `LD-` public_id, default status/priority/country_code, JSON casts, lifecycle datetime casts, helper methods (`isOpen`, `isConverted`, `hasContactRoute`), CRM query scopes, and BelongsTo relations. `LeadFactory` adds states for `websiteBulkEnquiry`, `manual`, `assignedTo`, `qualified`, `won`, `lost`, `spam`, `highPriority`, `urgent`, and `createdBy`. `php artisan migrate --force` and `php artisan test --filter=LeadModelTest` (31 tests, 72 assertions) passed. `php artisan test` (278 tests, 1612 assertions) passed. `./vendor/bin/pint` applied and passed.

Verification note: C3.1.2 completed on 2026-06-23. The manual lead capture endpoint is fully implemented under `POST /admin/leads` and protected by the `['auth', 'dashboard.access']` middleware group. Gated via `LeadPolicy` requiring `leads.manage` permission. Input validation via `StoreLeadRequest` utilizes centralized `Lead::SOURCES`, `Lead::STATUSES`, and `Lead::PRIORITIES` constants. The response is public-safe and contains no internal database IDs. Covered by 6 feature tests in `ManualLeadCaptureTest.php`. All tests passed, and Laravel Pint formatting is applied.

Verification note: C3.1.3 completed on 2026-06-23. The website bulk enquiry endpoint is implemented under `POST /api/catalog/leads`. Spammers and double submissions are prevented via duplicate fingerprint detection (checks matching email/phone and product interests within 5 minutes) returning HTTP 422. Input validation is performed by `StorePublicLeadRequest` with size limits on product interest arrays and URL validation for referrer and landing URLs. Source and status are forced by the server to `website_bulk_enquiry` and `new`. The public JSON response excludes internal numeric IDs and UTM/attribution fields. Covered by 7 feature tests in `WebsiteLeadCaptureTest.php`. All tests passed, and Laravel Pint formatting is applied.

Verification note: C3.1.4 completed on 2026-06-23. The implementation verified the validation and storage of all UTM parameters and page attribution URLs on guest submissions, added validation constraint checks for length and format to WebsiteLeadCaptureTest, added response assertions ensuring internal database IDs do not leak, and verified in LeadModelTest that UTM and attribution fields serialize correctly on the model level. `php artisan test --filter=WebsiteLeadCaptureTest`, `php artisan test --filter=LeadModelTest`, and `./vendor/bin/pint --test` passed.

Verification note: C3.1.5 completed on 2026-06-23. The implementation adds a `create_lead_activities_table` migration with all fields and 3 composite indexes from `crm-quotations-schema.md`, a `LeadActivity` model with fillable fields, type constants, casts, `lead`/`createdBy` relations, and two static factory helpers (`recordStatusChange`, `recordAssignment`). `Lead::activities()` HasMany relation added. `LeadController::update()` now auto-creates `status_change` activity entries (with `from_status`/`to_status` in metadata) and `assignment` activity entries (with `previous_assigned_to_user_id`/`new_assigned_to_user_id` in metadata) after each successful save. Activity logging is skipped entirely on invalid transitions so no partial state is written. `LeadStatusAssignmentTest` extended from 5 to 11 tests. `php artisan migrate --force`, `php artisan test --filter=LeadStatusAssignmentTest` (11 tests, 53 assertions), and `php artisan test` (306 tests, 1812 assertions) passed. `./vendor/bin/pint` applied and passed.

Verification note: C3.1.6 completed on 2026-06-23. The notes and activity timeline is fully implemented. Staff can create a note (and other staff-initiated types: call, email, whatsapp) via `POST /admin/leads/{lead}/activities`, which are validated using `StoreLeadActivityRequest`. The timeline is returned via `GET /admin/leads/{lead}/activities` in ascending chronological order of `occurred_at`, including system status changes and assignments. Both endpoints are gated by `leads.manage` permission via `LeadPolicy::viewActivities` and `LeadPolicy::createActivity`. Responses are public safe and contain no internal database IDs. Customer-facing APIs do not expose activity logs. Covered by 10 feature tests in `LeadActivityTimelineTest.php`. All 316 tests passed and Pint applied.

Verification note: C3.1.7 completed on 2026-06-23. Lead authorization, list, and detail views are fully implemented. Gated via `LeadPolicy::viewAny` and `LeadPolicy::view` requiring either `leads.view` or `leads.manage` permission. The list view returns a paginated structure mapped to safe summary fields (excludes internal IDs, requirements, UTM fields, referrer/landing URLs, and lost reason, but allows `assigned_to_user_id`), ordered newest first (`latest('created_at')`). The detail view returns full CRM data (includes UTM fields, requirements, landing page, etc., but hides internal database IDs `id`, `customer_id`, `created_by_user_id`). Covered by 10 feature tests in `LeadDetailListTest.php`. All 326 tests passed and Pint applied.

### C3.2 Follow-up workflow

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C3.2.1 | Follow-up data model and ownership rules | Not Started |
| C3.2.2 | Create, reschedule, complete, and cancel follow-ups | Not Started |
| C3.2.3 | Due-today and overdue staff views | Not Started |
| C3.2.4 | Reminder event scheduling and notification handoff | Not Started |
| C3.2.5 | Lead activity timeline integration | Not Started |
| C3.2.6 | Follow-up permissions and retry-safe regression tests | Not Started |

### C4.1 Simple order processing

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C4.1.1 | Operational status transition matrix | Completed |
| C4.1.2 | Role and state authorization policies | Completed |
| C4.1.3 | Status-change action and validation | Completed |
| C4.1.4 | Status history and audit-event emission | Completed |
| C4.1.5 | Payment, cancellation, and shipping boundary guards | Completed |
| C4.1.6 | Order-status workflow regression tests | Completed |

### C4.2 Shipping details

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C4.2.1 | Shipment data model and migration | Completed |
| C4.2.2 | Courier and tracking-detail entry | Completed |
| C4.2.3 | Shipping-date and delivery-date validation | Completed |
| C4.2.4 | Shipment event and order-history integration | Completed |
| C4.2.5 | Customer-safe tracking contract | Completed |
| C4.2.6 | Shipping permissions, audit events, and regression tests | Completed |

### C5.1 Finance payment and balance views

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C5.1.1 | Finance access boundary and sensitive-field policy | Completed |
| C5.1.2 | Payment and refund ledger list | Completed |
| C5.1.3 | Order payment detail and balance panel | Completed |
| C5.1.4 | Shared balance calculation presentation | Completed |
| C5.1.5 | Finance filters, totals, and pagination | Completed |
| C5.1.6 | Finance authorization and calculation regression tests | Completed |

Verification note: C5.1.1 completed on 2026-06-25. Enforced finance access boundary and sensitive-field policies. Created PaymentPolicy, RefundPolicy, PaymentResource, and RefundResource. Registered policies in AppServiceProvider. Appended controller routes for listing and displaying payments and refunds. Ensured internal primary database keys and foreign keys are omitted from serialization. Conditionally loaded sensitive gateway fee and net amount fields using $this->when() based on finance.view_cost permissions. Verified via FinanceBoundaryTest.php (5 tests, 64 assertions) and FinanceAccessPolicyTest.php (3 tests, 26 assertions).

Verification note: C5.1.2 completed on 2026-06-25. Implemented paginated ledger lists for all payments and refunds. Exposes JSON payloads through PaymentResource and RefundResource, protecting database keys and conditionally loading sensitive details. Integrated eager loading (`with('order')` and `with(['order', 'payment'])`) on query builders in PaymentController and RefundController to prevent N+1 queries. Verified via FinanceBoundaryTest.php (which covers collection visibility, key omission, and resource mapping).

Verification note: C5.1.3 completed on 2026-06-25. Implemented paid amount, refunded amount, and outstanding balance summary for order details using only succeeded payments and refunds. Outstanding balance is clamped to zero. Fail-loud LogicExceptions are thrown if required top-level or nested relationships are not eager loaded. Eager loading is optimized at the controller level. Model-level boot event validates the domain invariant that succeeded refunds must have a valid recorded payment. Verified via AdminOrderDetailTest.php (8 tests, 81 assertions) and full test suite run (400 tests passed).

Verification note: C5.1.4 completed on 2026-06-25. Refactored balance calculations in OrderDetailCatalog to delegate arithmetic to the shared OrderTotalsCalculator. Used constructor injection with fallback resolution for backward compatibility. Verified via AdminOrderDetailTest.php and all 400 tests passing.

Verification note: C5.1.5 completed on 2026-06-26. Implemented filtering, aggregates, and pagination for finance ledger. Supported filtering payments and refunds by date range, provider, method (payments only), status, and type. Added validation rules via form requests. Implemented page aggregates (totals for amount, fees, net) dynamically calculated from the filtered dataset. Hidden fee/net aggregates for unauthorized staff. Verified via FinanceLedgerFilterTest.php (6 tests, 79 assertions).

Verification note: C5.1.6 completed on 2026-06-26. Verified full regression coverage for finance ledger and balance calculations. Confirmed role-based visibility policies, endpoint protection gates, response structure filtering, and aggregate constraints. Ran and verified all tests passing without regressions.

### C5.4 Financial reports

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C5.4.1 | Report scopes, date ranges, and authorization policy | Not Started |
| C5.4.2 | Payment and outstanding-balance report | Not Started |
| C5.4.3 | Refund report | Not Started |
| C5.4.4 | Expense report | Not Started |
| C5.4.5 | Sales report | Not Started |
| C5.4.6 | Export and aggregate-data safeguards | Not Started |
| C5.4.7 | Report accuracy and permission regression tests | Not Started |

### C6.2 Notification implementation

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C6.2.1 | Notification persistence and migration | Not Started |
| C6.2.2 | Template management and safe variable rendering | Not Started |
| C6.2.3 | Event-to-recipient dispatch rules | Not Started |
| C6.2.4 | Queued channel delivery adapters | Not Started |
| C6.2.5 | Retry, idempotency, and provider-failure handling | Not Started |
| C6.2.6 | Notification log and delivery-attempt operations view | Not Started |
| C6.2.7 | Notification isolation and regression tests | Not Started |

### C6.3 Google Sheets backup sync

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C6.3.1 | Sheets connection configuration and access boundary | Not Started |
| C6.3.2 | Per-record sheet mapping and safe payload contract | Not Started |
| C6.3.3 | Source record projection for approved modules | Not Started |
| C6.3.4 | Post-save sync job enqueueing | Not Started |
| C6.3.5 | Delivery job, upsert, and provider response handling | Not Started |
| C6.3.6 | Retry, idempotency, and sync log operations view | Not Started |
| C6.3.7 | Non-blocking failure, recovery, and security regression tests | Not Started |
### C1.2 Sales order creation

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C1.2.1 | Customer selection | Completed |
| C1.2.2 | Product/SKU selection | Completed |
| C1.2.3 | Quantity and customization | Completed |
| C1.2.4 | Pricing and discount | Completed |
| C1.2.5 | Advance/final payment structure | Completed |
| C1.2.6 | Order creation | Completed |
| C1.2.7 | Order editing rules | Completed |
| C1.2.8 | Order confirmation | Completed |

Verification note: C1.2.7 completed on 2026-06-23. The order editing rules are fully implemented with permission checks (`Gate::authorize`), pessimistic locking (`lockForUpdate`) on both the order and order items inside the transaction, and automatic totals recalculation. An audit event (`orders.order_edited`) is emitted inside `DB::afterCommit` with a sanitized payload tracking header and item-level changes. Covered by 7 feature tests in `SalesOrderEditTest.php` and verified by `AuditEventContractTest.php`. All tests passed.

Verification note: C1.2.8 completed on 2026-06-23. The order confirmation transition is protected using OrderStatus::canTransitionTo() and updateStatus validation rules. Verified via OrderConfirmationTest.php with 7 tests and 24 assertions. All tests passed, and Laravel Pint formatting is applied.


### C1.3 Quotations and bulk-order conversion

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C1.3.1 | Bulk enquiry capture | Completed |
| C1.3.2 | Quotation creation | Completed |
| C1.3.3 | Quotation items and pricing | Completed |
| C1.3.4 | Quotation status | Completed |
| C1.3.5 | Customer approval | Completed |
| C1.3.6 | Quotation revision | Completed |
| C1.3.7 | Sales-order conversion | Completed |
| C1.3.8 | Advance-payment recording | Not Started |

Verification note: C1.3.1 completed on 2026-06-23. The bulk enquiry capture flow connects the website checkout quantity block and CRM Lead capture. Validated that checkout blocks bulk order quantities (item count >= 25) and returns a bulk handoff response advising next_step = bulk_enquiry (handled by CheckoutValidationService). The frontend then submits to the public leads capture API, creating a new lead with source = website_bulk_enquiry (handled by PublicLeadController). Covered by a dedicated feature integration test in `BulkEnquiryCaptureBridgeTest.php`. All 327 tests passed and Pint applied.

Verification note: C1.3.2 completed on 2026-06-23. Staff can create quotations originating from a qualified lead, customer, or manual entry with reachability rules. Validates early that lead or customer public IDs exist, enforces source exclusivity, auto-populates valid_until to 30 days from now, and computes taxes using PHP_ROUND_HALF_UP on a defensive taxable amount base. Returns public-safe payloads hiding internal database IDs. Formatted via Laravel Pint and validated via QuotationCreationTest.php with 16 tests and 92 assertions. All 343 tests passed.

Verification note: C1.3.3 completed on 2026-06-23. Quotation item and pricing logic is fully integrated and tested as part of the core quotation controller and request flow. Item pricing is resolved deterministically (explicit override -> SKU price -> product base price -> 422 validation failure). Subtotal, discounts (capped at subtotal), shipping, tax, and order totals are calculated using OrderTotalsCalculator. Enforces defensive taxable base calculations and PHP_ROUND_HALF_UP rounding rules. Formatted using Laravel Pint and fully tested via QuotationCreationTest.php. All 343 tests passed.

Verification note: C1.3.4 completed on 2026-06-23. Quotation status transitions are implemented with state transition validation, automated timestamp logging, and a dedicated status update endpoint. Validates transitions based on the recommended sales workflow state machine (draft -> sent -> cancelled/approved/rejected/revision_requested/expired -> revised/sent -> cancelled/converted -> terminal). Supports `revised_at` timestamp. Rejects identical state transitions with a 422 validation failure, and blocks modifications on terminal states (converted/cancelled). Returns full updated quotation JSON payloads. Formatted via Laravel Pint and validated via QuotationStatusTest.php with 6 tests and 46 assertions. All 349 tests passed.

Verification note: C1.3.5 completed on 2026-06-23. Customer approval and rejection logic is fully implemented and tested. Added a unique secure `approval_token` column to the `quotations` table and verified using constant-time `hash_equals()` validation on public customer endpoints. Created the `quotation_approval_events` table and model to log a complete history of events (sent, approved, rejected, cancelled, revision_requested, revised, expired, converted). Supports header/body `idempotency_key` checking to avoid duplicate event records. Enforces that only approved quotations can transition to the `converted` state. Formatted via Laravel Pint and validated via QuotationApprovalTest.php with 10 tests and 38 assertions. All 359 tests passed.

Verification note: C1.3.6 completed on 2026-06-23. Quotation revision snapshotting and version archiving is fully implemented. When a quotation's status transitions to revised, a snapshot of the current quotation's commercial terms (totals, line items snapshot, type, expiry date, previous status, customer note, and customer profile details) is persisted in the quotation_revisions table, the approval_token is redacted/excluded from the archive for security, and current_revision_number on the quotation is incremented sequentially. Concurrency is handled using pessimistic row locks (lockForUpdate) inside the transaction block. Formatted via Laravel Pint and validated via QuotationRevisionTest.php with 6 tests and 41 assertions. All 367 tests passed.

Verification note: C1.3.7 completed on 2026-06-23. Approved quotations can be atomically converted to confirmed sales orders via POST /admin/quotations/{id}/convert. Conversion is blocked with 422 if the quotation is not approved, already converted, has no live customer_id, or contains any free-text items (product_sku_id = null) — ensuring order total integrity. All quotation totals are copied verbatim to the order. OrderItems are created with price_source = quotation_conversion. Idempotency is supported via conversion_idempotency_key. The operation uses lockForUpdate inside a DB transaction for TOCTOU safety. A QuotationApprovalEvent with event_type = converted is logged atomically. Formatted via Laravel Pint and validated via QuotationConversionTest.php with 12 tests and 65 assertions. All 379 tests passed.

Verification note: C1.3.8 completed on 2026-06-24. Advance/manual payment recording is fully implemented via POST /admin/orders/{order}/payments. Gated by `payments.record` permission and `OrderPolicy@recordPayment`. Validates that payment amount does not exceed the remaining balance and that the order is not in a terminal state (cancelled/refunded). Generates a unique receipt number starting with `RC-`. Emits `AuditEvent` with type `payments.payment_recorded`. Supports idempotency key verification. Verified via ManualPaymentRecordingTest.php with 8 tests and 31 assertions. All tests passed and Laravel Pint formatted.

### C2.1 Inventory movements and stock handling

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C2.1.1 | SKU stock balance | Completed |
| C2.1.2 | Stock-in | Completed |
| C2.1.3 | Stock-out | Completed |
| C2.1.4 | Manual adjustment | Not Started |
| C2.1.5 | Order stock deduction | Not Started |
| C2.1.6 | Cancellation stock reversal | Not Started |
| C2.1.7 | Low-stock warning | Not Started |
| C2.1.8 | Movement history and audit | Not Started |

Verification note: C2.1.1 completed on 2026-06-27. Created the `inventory_items` table with database check constraints and chunked existing SKU backfill. Created the `InventoryItem` model, `InventoryBalanceService` for atomic transaction synchronization, and `ProductSkuObserver` for SKU auto-initialization. All tests in `InventoryItemTest.php` and the full test suite passed.

Verification note: C2.1.2 completed on 2026-06-27. Implemented the append-only `inventory_movements` database table with snapshots (including `before_available` and `after_available`). Created typo-safe enums (`InventoryMovementType`, `InventoryDirection`, and `InventoryMovementReason`), protected the `InventoryMovement` model's immutability inside booting hooks, and implemented race-safe transaction idempotency checks inside the service. Covered by 8 feature tests in `InventoryStockInTest.php`.

Verification note: C2.1.3 completed on 2026-06-27. Implemented `stockOut` API in `InventoryBalanceService` propagating through the unified `recordMovement` primitive. Introduced a domain-specific `InsufficientStockException` thrown by the service when stock-out violates limits, extended `InventoryMovementReason` with future-proofing reason cases, and asserted exact snapshots, sequential ordering, and negative stock overrides in tests. Covered by 8 feature tests in `InventoryStockOutTest.php`.




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
| C5.2.1 | Refund request | Completed |
| C5.2.2 | Refund approval | Completed |
| C5.2.3 | Partial refund | Completed |
| C5.2.4 | Full refund | Completed |
| C5.2.5 | Refund payment record | Completed |
| C5.2.6 | Payment-status recalculation | Completed |
| C5.2.7 | Refund audit trail | Completed |

Verification note: C5.2.6 completed on 2026-06-26. Verified dynamic recalculations for unpaid, partially paid, paid, partially refunded, and refunded statuses using `PaymentStateRecalculationRules` unit tests and a dedicated integration test suite validating correct presentation and balance summaries across all payment status combinations. Tested via PaymentStatusRecalculationIntegrationTest.php.

Verification note: C5.2.1 completed on 2026-06-26. Implemented refund request creation under `POST /admin/refunds` with a dedicated `refunds.request` permission and model scope validation inside locked transaction. Checked bounds for full and partial refund types, verified that failed/cancelled refunds release reserved balance, and verified audit events. Tested via RefundRequestTest.php.

Verification note: C5.2.2 completed on 2026-06-26. Implemented refund approval workflow under `POST /admin/refunds/{refund}/approve` gated by the `refunds.approve` permission. Extracted state transition predicates/mutators (`canBeApproved()`, `ensureCanBeApproved()`, `approve()`) directly to the model. Handled concurrency locking on the refund record prior to mutation. Emitted `refunds.refund_approved` audit event. Tested via RefundApprovalTest.php.

Verification note: C5.2.3 completed on 2026-06-26. Implemented partial refund transitions, controller endpoints for processing and cancelling, policies, and webhook integrations with row locking and idempotency verification. Dispatched explicit audit events and recalculated order balances correctly. Tested via PartialRefundTest.php.

Verification note: C5.2.4 completed on 2026-06-26. Implemented full refund transitions, controller processing, and webhook integrations under locked transactions. Verified that full refund validation rejects amounts not equal to the remaining refundable balance, and that it correctly reduces net paid amount to 0, dynamically updating payment status to refunded. Tested via PartialRefundTest.php.

Verification note: C5.2.5 completed on 2026-06-26. Implemented explicit self-validating model methods `ensurePaymentIsRefundable()` (asserting `payment_id` presence, resolving payment relation, and checking `succeeded` status) and `ensurePaymentAssociationIsImmutable(?int $newPaymentId)` on the `Refund` model. Added invariants class documentation. Wrote feature tests verifying that parent payment records remain completely unchanged (immutable accounting snapshot comparison) during one or multiple refunds, and verifying that the relationship is append-only and ledger calculations dynamically resolve balance aggregates correctly. Tested via RefundPaymentRecordTest.php.

Verification note: C5.2.7 completed on 2026-06-26. Implemented and verified the complete refund lifecycle audit trail. Added a dedicated feature integration test suite validating that all refund lifecycle transitions (requested, approved, processing, succeeded, failed, cancelled) correctly dispatch explicit audit events with safe payload formats. Eager-loaded relationships to prevent N+1 queries. Passed all test suites and resolved all static analysis check gates.

### C5.3 Expense management

| Subtask ID | Subtask Name | Status |
|---|---|---|
| C5.3.1 | Expense categories | Completed |
| C5.3.2 | Expense entry | Completed |
| C5.3.3 | Expense approval rules | Completed |
| C5.3.4 | Expense permissions | Completed |
| C5.3.5 | Expense reporting data | Completed |

Verification note: C5.3.1 completed on 2026-06-26. Implemented database schema, Eloquent model, seeders, policies, validation requests, and REST endpoints for business expense categories. Added static unique public_id generation, save-time domain code mutation blocking, seeder regressions, and soft-delete route binding 404 behavior. All 13 feature integration tests in ExpenseCategoryTest.php and all 479 global backend tests passed.

Verification note: C5.3.2 completed on 2026-06-26. Implemented database migration for expenses table with restrictOnDelete FKs, Expense model with immutable guards, validation requests excluding soft-deleted categories and restricting future-dated amounts, decimal string resources, and REST endpoints with deterministic sorting and N+1 query eager loading. All 14 feature integration tests in ExpenseTest.php and all 493 global backend tests passed.

Verification note: C5.3.3 completed on 2026-06-26. Implemented state transition rules, approval policies, transaction-locked action endpoints with row-level locks, form request validation including whitespace trimming, first-class approved_at timestamps, and versioned chronological transition history logs in metadata json. All 24 feature tests in ExpenseTest.php and all 503 global backend tests passed. Pint and PHPStan analyses are fully clean.

Verification note: C5.3.4 completed on 2026-06-26. Audited and confirmed all eight expense endpoint policy gates (viewAny, view, create, update, delete, submit, approve, reject). Extended ExpenseTest.php with comprehensive role-by-role permission matrix tests covering Admin, Finance Staff, Sales Staff, Inventory Staff, and Production Staff. Added existence leakage prevention tests verifying that unauthorized users always receive 403 (not 404) on valid public IDs. All 32 ExpenseTest tests and all 511 global backend tests passed. Pint and PHPStan analyses are fully clean.


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







Verification note: B3.1.7 completed on 2026-06-19. The checkout handoff now persists linked order_items rows with SKU, quantity, pricing, and customization snapshots before payment attempts begin, and the checkout feature tests cover item storage, customization persistence, and empty-cart rejection. `php artisan test --filter=CheckoutValidationTest`, `php artisan test --filter=CheckoutPendingOrderTest`, and `./vendor/bin/pint --test app/Models/Order.php app/Models/OrderItem.php app/Services/CheckoutPendingOrderService.php app/Services/CheckoutValidationService.php tests/Feature/CheckoutPendingOrderTest.php tests/Feature/CheckoutValidationTest.php database/migrations/2026_06_19_000004_create_order_items_table.php` passed.
Verification note: B3.1.8 completed on 2026-06-19. The checkout flow now creates and links a traceable payment attempt to the pending order, keeps the response public-safe, and reuses the shared idempotency foundation for the new attempt record. `php artisan test --filter=CheckoutPendingOrderTest`, `php artisan test --filter=CheckoutValidationTest`, and `./vendor/bin/pint --test app/Models/Order.php app/Models/OrderItem.php app/Models/PaymentAttempt.php app/Services/CheckoutPendingOrderService.php tests/Feature/CheckoutPendingOrderTest.php database/migrations/2026_06_19_000005_create_payment_attempts_table.php` passed.

Verification note: B3.1.9 completed on 2026-06-20. Repeated `/api/cart/checkout` submissions now reuse the original pending order, order items, and linked payment attempt through the shared `checkout_submission` idempotency key, while returning the same public-safe handoff identifiers. `php artisan test` (151 tests, 987 assertions), `php artisan test --filter=CheckoutPendingOrderTest`, `php artisan test --filter=CheckoutValidationTest`, `php artisan test --filter=IdempotencyFoundationTest`, and `./vendor/bin/pint --test app/Services/CheckoutPendingOrderService.php tests/Feature/CheckoutPendingOrderTest.php` passed.



