# Okina Craft Subtask Validation

This document validates the subtasks created in `task-list.md`.

Each parent task is complete only when:

- All required subtasks are complete.
- Subtask acceptance criteria pass.
- Parent-level integration tests pass.
- Affected modules still work.
- Documentation and dependency records are updated.
- Regression checks listed in `dependency-impact-register.md` pass where relevant.

Complexity scale:

- Low: narrow documentation, simple model, or isolated UI/API work.
- Medium: touches shared data, multiple screens, or several test paths.
- High: touches money, checkout, order state, permissions, audit, files, or cross-module rules.

## Parent Completion Rules

| Parent Task | Completion Rule |
|---|---|
| A1.1 Final ERD and schema plan | Complete only when all schema groups, IDs, indexes, and migration sequencing are approved together and cross-module relationships are verified. |
| A1.4 Environment and hosting readiness check | Complete only when local tool readiness and target hosting requirements are checked, blockers are documented, and the project has a clear shared cPanel or VPS path before scaffold work starts. |
| A1.5 Repository and app scaffold implementation | Complete only when Laravel and Astro apps exist in the approved locations, environment examples are present, backend boot/test checks pass, frontend build checks pass, and no secrets or dependency folders are committed. |
| A3.2 Shared products, categories, variants and SKUs | Complete only when admin catalog management, public API output, status/visibility rules, and SKU references work together. |
| A4.1 File upload service | Complete only when validation, private storage, previews, signed access, permissions, deletion rules, and upload security tests pass. |
| A5.1 Shared order/payment domain model | Complete only when order/payment rules support website orders, sales orders, quotations, totals, balances, and payment-state calculation without conflicting states. |
| A5.2 Cancellation and refund rules | Complete only when cancellation eligibility/effects, partial/full refund rules, payment-state recalculation, and audit requirements are defined and tested. |
| A5.3 Payment gateway service contract | Complete only when gateway interface, Cashfree, manual payments, verification, webhooks, refunds, and failure handling are tested through the shared payment service. |
| B2.2 Upload and simple mockup preview | Complete only when uploaded design data survives product page, cart, order, and admin review flows. |
| B3.1 Cart and checkout with pending order creation | Complete only when cart validation, price recalculation, bulk detection, pending order creation, payment attempt creation, idempotency, and failure handling pass together. |
| B3.3 Payment webhook handling | Complete only when authenticated webhook events update payment state exactly once and failed/refund events are logged and testable. |
| C1.1 Basic admin order and payment view | Complete only when authorized staff can inspect website orders, payment/refund history, customer and item snapshots, and linked design files without receiving mutation, finance-cost, or private-file access beyond their permissions. |
| C1.2 Sales order creation | Complete only when sales staff can create, price, edit within rules, confirm, structure payments for sales orders, and emit required audit events. Final parent completion also requires C6.1 audit integration tests to pass when audit storage is implemented. |
| C1.3 Quotations and bulk-order conversion | Complete only when bulk enquiries become quotations, approved quotations become sales orders, and advance payments are recorded correctly. |
| C2.1 Inventory movements and stock handling | Complete only when stock balance, stock-in/out, adjustments, order deduction, cancellation reversal, warnings, history, and audit events work together. Final parent completion also requires C6.1 audit integration tests to pass when audit storage is implemented. |
| C2.2 Vendors and purchases | Complete only when vendor purchase orders can be created, received fully/partially, paid/tracked, and linked to stock history. |
| C5.2 Refund management | Complete only when refund records, approvals, partial/full refunds, payment recalculation, and audit trail work without deleting original payment history. |
| C5.3 Expense management | Complete only when expenses are categorized, permission-protected, approved where needed, and available for finance reports. |
| C6.1 Immutable audit log | Complete only when all sensitive change types are captured, masked, permission-protected, and retained according to policy. |
| C3.1 CRM lead module | Complete only when website and manual leads preserve source data, follow the approved ownership/status rules, and are visible only to authorized staff. |
| C3.2 Follow-up workflow | Complete only when follow-ups can be scheduled, completed, surfaced when due/overdue, handed to notifications safely, and recorded in lead activity history. |
| C4.1 Simple order processing | Complete only when permitted operational state transitions are validated, recorded once, and remain separate from payment, refund, and shipment state. |
| C4.2 Shipping details | Complete only when authorized staff can manage validated shipment details, customers receive only safe tracking data, and delivery updates remain traceable. |
| C5.1 Finance payment and balance views | Complete only when protected read-only payment, refund, and balance views use shared financial records and calculations without exposing cost/profit data to unauthorized staff. |
| C5.4 Financial reports | Complete only when each protected report reconciles with its shared source records, respects date/filter scope, and excludes unauthorized sensitive data. |
| C6.2 Notification implementation | Complete only when versioned templates, dispatch rules, delivery logs, retries, and deduplication work without blocking the business action that emitted the event. |
| C6.3 Google Sheets backup sync | Complete only when approved record summaries are queued, deduplicated, retryable, observable, and non-blocking to the source save. |
| C6.4 Backup, security, and regression gates | Complete only when backup, restore, security reviews, deployment checklist, regression checklist, and rollback procedure are documented and tested. |
| B4.1 Customer dashboard | Complete only when Astro frontend dashboard handles session validation, address CRUD/defaults, order history, tracking, signed mockup previews, and reordering, and backend API routes are fully auth-protected and pass all feature tests. |
| B4.2 Customer tracking page | Complete only when customer-friendly order status, payment summary, shipment details, and support actions are displayed on a tracking page via dynamic timelines and shipping cards, auth-protected and fully tested. |

## A1.1 Final ERD And Schema Plan

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A1.1.1 | Users, roles, permissions, and role assignment schema draft | None | Roles support Super Admin, Admin, Sales, Inventory, Finance, Production | Schema review, permission relationship review | Auth, Admin | High |
| A1.1.2 | Customers and addresses schema draft | A1.1.1 optional | Customers support website and sales orders; multiple addresses allowed | Customer/order relationship review | Customers, Checkout, Orders | Medium |
| A1.1.3 | Products, variants, and SKUs schema draft | None | SKU is usable by catalog, cart, orders, inventory | Catalog relationship review | Products, Cart, Inventory | High |
| A1.1.4 | Orders and order items schema draft | A1.1.2, A1.1.3 | Order items reference SKUs and preserve customization snapshot | Order lifecycle review | Orders, Checkout, Admin | High |
| A1.1.5 | Payments and refunds schema draft | A1.1.4 | Multiple payments/refunds per order supported | Payment state calculation review | Payments, Finance | High |
| A1.1.6 | Inventory, vendors, and purchases schema draft | A1.1.3 | Stock movements and purchase receiving are traceable | Stock movement relationship review | Inventory, Vendors | Medium |
| A1.1.7 | CRM and quotations schema draft | A1.1.2, A1.1.4 | Lead to quotation to sales order path is supported | Conversion path review | CRM, Quotations, Orders | Medium |
| A1.1.8 | Files, audit, and notifications schema draft | A1.1.4, A1.1.5 | Private files, immutable audit, notification logs are representable | Security/audit schema review | Files, Audit, Notifications | High |
| A1.1.9 | Public IDs, indexes, migration sequence plan | A1.1.1-A1.1.8 | Public IDs and lookup indexes cover critical paths | Migration order review, index review | Database, All Modules | High |

## A1.4 Environment And Hosting Readiness Check

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A1.4 | Local and hosting readiness report | A1.1, A1.2, A1.3 | Laravel 13/PHP, Composer, MySQL/MariaDB, Node/npm, upload/storage, cron, queues, SSL, webhooks, backup, and rollback readiness are checked or listed as missing information | PHP/version/extension checks, Composer connectivity check, Node/npm checks, MySQL availability check, manual hosting checklist | Deployment, Backend, Frontend | Medium |

## A1.5 Repository And App Scaffold Implementation

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A1.5 | Laravel backend and Astro frontend scaffold | A1.4 | `apps/backend` and `apps/frontend` exist, env examples are present, backend boots, backend tests run, frontend builds, and no secrets or dependency folders are committed | Backend boot/test, frontend build, environment file review, secrets check | Backend, Frontend, Deployment | Medium |

Verification note: completed on 2026-06-17. `php artisan about`, `php artisan test`, and `npm run build` all passed.

## A2.1 Admin Authentication

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A2.1 | Protected admin login, logout, and staff access gate | A1.5 | Unauthorized users are redirected away, valid staff sessions can enter the admin area, and logout ends the session cleanly | Admin login/access tests, unauthorized redirect tests, logout/session tests | Auth, Admin | Medium |

Verification note: completed on 2026-06-17. `php artisan migrate --force` verified the MySQL migration state, `php artisan model:show "App\Models\User"` confirmed dashboard auth fields on the MySQL `users` table, and `php artisan test` passed with 13 tests and 45 assertions.

## A2.3 Role And Permission Model

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A2.3 | App-owned roles, permissions, role assignments, and role-permission grants for staff/admin access | A2.1 | Super Admin, Admin, Sales Staff, Inventory Staff, Finance Staff, and Production Staff are represented; dashboard access requires an assigned dashboard role; permissions are checked through roles; unauthorized staff cannot access protected admin operations; and access control behavior is covered by tests | Role access tests, permission denial tests, authorized access tests | Auth, Admin, Finance, Inventory, CRM, Production | High |

Verification note: completed on 2026-06-18. The implementation uses app-owned `roles`, `permissions`, `role_user`, and `role_permissions` tables; staff dashboard access now depends on assigned roles; and role-based permission checks are available through the `User` model. `php artisan migrate --force`, `php artisan test`, and `./vendor/bin/pint --test` passed.

## A2.2 Customer Authentication

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A2.2 | Customer login, registration, logout, customer guard, and protected account access gate | A1.5 | Guests are redirected to customer login, valid customer credentials create a customer session, authenticated customers can enter the account area, staff sessions do not grant customer access, and logout ends the customer session cleanly | Customer auth tests, unauthorized redirect tests, logout/session tests | Auth, Customer, Website | Medium |

Verification note: completed on 2026-06-18. Customer authentication uses separate `customers` and `customer_accounts` tables, a separate `customer` guard/provider/password broker, normalized unique login emails, and customer-only route protection. `php artisan migrate --force`, `php artisan test`, and `./vendor/bin/pint --test` passed.

## A3.1 Shared Customers And Addresses

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A3.1 | Shared customer profile fields and customer-address relationships for website and admin workflows | A1.1, A2.2 | Customers can exist without addresses, customers can have multiple addresses, customer/address records are usable by both website and admin workflows, and relationship behavior is covered by tests | Customer relationship tests, address relationship tests, shared access tests | Customers, Addresses, Website, Admin | High |

Verification note: completed on 2026-06-18. The implementation adds shared customer profile fields, `customer_addresses`, customer/address factories and models, and relationship tests. `php artisan migrate --force`, `php artisan test`, and `./vendor/bin/pint --test` passed.

## A3.2 Shared Products, Categories, Variants And SKUs

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A3.2.1 | Category model/table/admin fields/API shape | A1.1.3 | Categories have slug, status, SEO fields | Category CRUD/API tests | Products, Website | Medium |
| A3.2.2 | Product model/table/admin fields/API shape | A3.2.1 | Products attach to categories and expose public-safe data | Product CRUD/API tests | Products, Website | High |
| A3.2.3 | Product variant model and relationship rules | A3.2.2 | Variants represent product options without duplicating products | Variant CRUD tests | Products, Cart | Medium |
| A3.2.4 | SKU model and SKU reference rules | A3.2.3 | SKU can be referenced by cart, order item, stock movement | SKU relationship tests | Products, Cart, Orders, Inventory | High |
| A3.2.5 | Product status and visibility rules | A3.2.2, A3.2.4 | Public/private and status rules are enforced | Visibility tests | Products, Website, Admin | High |
| A3.2.6 | Product images and print option structures | A3.2.2 | Images and print options support customization and mockup preview | Image/print option tests | Products, Uploads, Mockup | Medium |
| A3.2.7 | Admin catalog management screens/resources | A3.2.1-A3.2.6, A2.3 | Authorized staff can manage catalog data | Admin permission/CRUD tests | Admin, Products | Medium |
| A3.2.8 | Public catalog API data | A3.2.1-A3.2.6 | API excludes private/draft data and includes required detail fields | Public API contract tests | Website, Products | High |

Verification note: completed on 2026-06-18. The implementation adds `product_categories`, `products`, `product_variants`, and `product_skus` tables, matching model/factory support, catalog visibility helpers, and product relationship tests. `php artisan migrate --force`, `php artisan test`, and `./vendor/bin/pint --test` passed.

## A4.1 File Upload Service

Verification note: completed on 2026-06-18. The implementation adds private file storage, MIME/extension/size validation, image preview generation, signed preview/download routes, file access policy wiring, soft-delete deletion rules, and upload security tests. `php artisan test` and `./vendor/bin/pint --test` passed.

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A4.1.1 | MIME, extension, size, and dangerous-file validation rules | A1.1.8 | Valid files pass; dangerous/oversize files fail | Upload validation tests | Files, Website, Admin | High |
| A4.1.2 | Private storage configuration and file naming rule | A4.1.1 | Original files are not public and names are randomized | Storage visibility tests | Files, Security | High |
| A4.1.3 | Preview generation plan/service | A4.1.2 | Preview failure does not delete original | Preview success/failure tests | Files, Mockup | Medium |
| A4.1.4 | Signed preview/download access | A4.1.2 | URLs expire and require authorization where needed | Signed URL tests | Files, Customer, Admin | High |
| A4.1.5 | File permission policy | A2.3, A4.1.4 | Customers/staff see only allowed files | Permission tests | Files, Auth | High |
| A4.1.6 | File deletion and protection rules | A4.1.2 | Protected files are kept; deleted files retain DB record | Cleanup tests | Files, Jobs | Medium |
| A4.1.7 | Upload security test suite | A4.1.1-A4.1.6 | Security tests cover common unsafe file paths | Upload security regression tests | Files, Security | High |

## A4.4 Notification Event Definitions

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A4.4 | Notification event contract catalog with event keys, recipients, channels, retry, deduplication, and template requirements | A4.2, A4.3 | Approved notification events are defined without adding sending logic | Notification contract tests | Notifications, Jobs, Settings | Medium |

Verification note: completed on 2026-06-18. The implementation adds a shared notification event catalog, a typed notification event definition object, and feature tests covering the approved workflow events, recipient/channel rules, retry behavior, deduplication, and template requirements. `php artisan test` and `./vendor/bin/pint --test` passed.

## A4.5 Idempotency Foundation

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A4.5 | Shared duplicate-prevention keys, retry-safe idempotency conventions, helper rules, and duplicate-handling outcomes for checkout, order creation, payments, inventory movements, notifications, Sheets sync, and retries | A1.1 | Duplicate-prevention rules are consistent, stable, and reusable across the shared backend before checkout/payment/inventory implementations begin | Duplicate request tests, idempotency key generation tests, retry duplicate prevention tests, shared operation duplicate handling tests | Checkout, Orders, Payments, Inventory, Notifications, Google Sheets, Jobs | High |

Verification note: completed on 2026-06-18. The implementation adds a shared idempotency key generator, an operation catalog for checkout/order/payment/inventory/notification/Sheets/job retry duplicate-handling rules, and feature tests covering stable key generation, hash fallback, and catalog outcomes. `php artisan test --filter=IdempotencyFoundationTest` and `./vendor/bin/pint --test app\\Support\\Idempotency tests\\Feature\\IdempotencyFoundationTest.php` passed.

## A4.6 Audit Event/Interface Contract

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A4.6 | Lightweight audit event/interface contract that core modules can emit before permanent audit storage is implemented | A1.1, A4.5 | Core modules can emit audit events with safe payloads, actor/source references, and masking guidance without writing permanent audit storage yet | Audit event contract tests | Orders, Payments, Inventory, Finance, Audit | Medium |

Verification note: completed on 2026-06-18. The implementation adds a shared audit event contract, a safe payload policy, an audit event catalog for order/payment/inventory/finance guidance, and feature tests covering contract shape, module guidance, and recursive redaction of sensitive values. `php artisan test --filter=AuditEventContractTest` and `./vendor/bin/pint --test app\\Contracts\\AuditEventContract.php app\\Support\\Audit tests\\Feature\\AuditEventContractTest.php` passed.

## A5.1 Shared Order/Payment Domain Model

Verification note: A5.1.1 completed on 2026-06-18. The implementation adds a shared order type contract, an `OrderType` enum, an order type catalog with website/admin usage rules, and feature tests covering the two approved order types, labels, contract helpers, and usage guidance. `php artisan test --filter=OrderTypeContractTest` and `./vendor/bin/pint --test app\\Contracts\\OrderTypeContract.php app\\Enums\\OrderType.php app\\Support\\Orders tests\\Feature\\OrderTypeContractTest.php` passed.

Verification note: A5.1.2 completed on 2026-06-18. The implementation adds a shared order status contract, an `OrderStatus` enum, an order status catalog with operational usage rules, and feature tests covering the approved operational statuses, labels, terminal flags, and tracking guidance. `php artisan test --filter=OrderStatusContractTest` and `./vendor/bin/pint --test app\\Contracts\\OrderStatusContract.php app\\Enums\\OrderStatus.php app\\Support\\Orders tests\\Feature\\OrderStatusContractTest.php` passed.

Verification note: A5.1.3 completed on 2026-06-18. The implementation adds a shared payment status contract, a `PaymentStatus` enum, a payment status catalog with calculation rules sourced from payment and refund records, and feature tests covering the approved financial states, labels, state flags, and calculation guidance. `php artisan test --filter=PaymentStatusContractTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentStatusContract.php app\\Enums\\PaymentStatus.php app\\Support\\Payments tests\\Feature\\PaymentStatusContractTest.php` passed.

Verification note: A5.1.4 completed on 2026-06-18. The implementation adds a shared order totals contract, an `OrderTotals` value object, an order totals calculator, and feature tests covering order subtotal/discount/shipping/tax totals, paid and refund-aware balances, and serialization for later service usage. `php artisan test --filter=OrderTotalsContractTest` and `./vendor/bin/pint --test app\\Contracts\\OrderTotalsContract.php app\\Support\\Orders tests\\Feature\\OrderTotalsContractTest.php` passed.

Verification note: A5.1.5 completed on 2026-06-18. The implementation adds a shared website order contract, website order rules, a website order catalog, and feature tests covering pending-order creation before payment, idempotency usage, payment-attempt sequencing, and gateway-independent checkout guidance. `php artisan test --filter=WebsiteOrderRulesTest` and `./vendor/bin/pint --test app\\Contracts\\WebsiteOrderContract.php app\\Support\\Orders tests\\Feature\\WebsiteOrderRulesTest.php` passed.

Verification note: A5.1.6 completed on 2026-06-18. The implementation adds a shared sales order contract, sales order rules, a sales order catalog, and feature tests covering admin/manual creation, approved-quotation conversion compatibility, confirmed initial operational state, advance/final payment support, and gateway independence. `php artisan test --filter=SalesOrderRulesTest` and `./vendor/bin/pint --test app\\Contracts\\SalesOrderContract.php app\\Support\\Orders tests\\Feature\\SalesOrderRulesTest.php` passed.

Verification note: A5.1.7 completed on 2026-06-18. The implementation adds a shared quotation-to-order conversion contract, quotation conversion rules, a quotation conversion catalog, and feature tests covering approved quotation conversion, one-time conversion behavior, conversion idempotency, and sales-order compatibility. `php artisan test --filter=QuotationToOrderConversionRulesTest` and `./vendor/bin/pint --test app\\Contracts\\QuotationToOrderConversionContract.php app\\Support\\Orders tests\\Feature\\QuotationToOrderConversionRulesTest.php` passed.

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A5.1.1 | Order type definitions | A1.1.4 | Website Order and Sales Order are distinct | Domain rule tests | Orders, Checkout, Admin | Medium |
| A5.1.2 | Order status definitions | A5.1.1 | Allowed statuses match approved list only | State transition tests | Orders, Tracking | High |
| A5.1.3 | Payment status definitions | A1.1.5 | Payment status is derived from payment records | Payment calculation tests | Payments, Finance | High |
| A5.1.4 | Order totals and balances rules | A1.1.4, A1.1.5 | Totals, paid amount, balance, refunds calculate correctly | Totals/balance tests | Orders, Payments, Finance | High |
| A5.1.5 | Website-order rules | A5.1.1-A5.1.4 | Checkout creates pending website orders before payment | Checkout domain tests | Website, Orders, Payments | High |
| A5.1.6 | Sales-order rules | A5.1.1-A5.1.4 | Sales orders support advance/final payment structure | Sales order domain tests | Admin, Orders, Finance | High |
| A5.1.7 | Quotation-to-order conversion rules | A1.1.7, A5.1.1 | Approved quotations convert to sales orders only once | Conversion/idempotency tests | CRM, Quotations, Orders | High |

## A5.2 Cancellation And Refund Rules

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A5.2.1 | Cancellation eligibility rules | A5.1.2, A5.1.4 | System defines when each order type/status can be cancelled | Cancellation eligibility tests | Orders, Admin | High |
| A5.2.2 | Cancellation effects | A5.2.1 | Cancellation effects on order status, payment facts, stock actions, and customer visibility are defined | Cancellation effect tests | Orders, Payments, Inventory, Tracking | High |
| A5.2.3 | Partial refund rules | A5.1.3, A5.1.4 | Partial refunds update refund totals and payment state correctly | Partial refund rule tests | Payments, Finance | High |
| A5.2.4 | Full refund rules | A5.1.3, A5.1.4 | Full refunds update refund totals and payment/order financial state correctly | Full refund rule tests | Payments, Finance, Orders | High |
| A5.2.5 | Payment-state recalculation rules | A5.2.3, A5.2.4 | Unpaid, partially paid, paid, partially refunded, and refunded states calculate from records | Payment-state recalculation tests | Payments, Orders, Finance | High |
| A5.2.6 | Audit requirements | A4.6, A5.2.1-A5.2.5 | Cancellation/refund actions must emit audit events with safe payloads | Audit event tests | Orders, Finance, Audit | High |

Verification note: A5.2.1 completed on 2026-06-18. The implementation adds a shared cancellation eligibility contract, cancellation eligibility rules, a cancellation eligibility catalog, and feature tests covering which website orders and sales orders can be cancelled, status-based eligibility, and safety guidance that keeps cancellation separate from refund execution. `php artisan test --filter=CancellationEligibilityRulesTest` and `./vendor/bin/pint --test app\\Contracts\\CancellationEligibilityContract.php app\\Support\\Orders tests\\Feature\\CancellationEligibilityRulesTest.php` passed.

Verification note: A5.2.2 completed on 2026-06-18. The implementation adds a shared cancellation effect contract, cancellation effect rules, a cancellation effect catalog, and feature tests covering that cancellation moves the order to `cancelled` without changing payment facts, triggering refunds, or reversing stock, while keeping customer-safe visibility and leaving later refund/inventory handling to future subtasks. `php artisan test --filter=CancellationEffectRulesTest` and `./vendor/bin/pint --test app\\Contracts\\CancellationEffectContract.php app\\Support\\Orders\\CancellationEffectRules.php app\\Support\\Orders\\CancellationEffectCatalog.php tests\\Feature\\CancellationEffectRulesTest.php` passed.

Verification note: A5.2.3 completed on 2026-06-18. The implementation adds a shared partial refund contract, partial refund rules, a partial refund catalog, and feature tests covering that partial refunds increase refund totals, map to `partially_refunded` or `refunded` based on remaining net paid amount, preserve original payments, and stay separate from cancellation effects. `php artisan test --filter=PartialRefundRulesTest` and `./vendor/bin/pint --test app\\Contracts\\PartialRefundContract.php app\\Support\\Payments\\PartialRefundRules.php app\\Support\\Payments\\PartialRefundCatalog.php tests\\Feature\\PartialRefundRulesTest.php` passed.

Verification note: A5.2.4 completed on 2026-06-18. The implementation adds a shared full refund contract, full refund rules, a full refund catalog, and feature tests covering that full refunds restore the full successful paid amount, mark the order refunded, keep original payments intact, and keep customer-safe visibility while leaving payment-state recalculation and audit storage to later subtasks. `php artisan test --filter=FullRefundRulesTest` and `./vendor/bin/pint --test app\\Contracts\\FullRefundContract.php app\\Support\\Payments\\FullRefundRules.php app\\Support\\Payments\\FullRefundCatalog.php tests\\Feature\\FullRefundRulesTest.php` passed.

Verification note: A5.2.5 completed on 2026-06-18. The implementation adds a shared payment-state recalculation contract, payment-state recalculation rules, a payment-state recalculation catalog, and feature tests covering that unpaid, partially paid, paid, partially refunded, and refunded states are derived from successful payment and refund records with refund states taking priority once refunds exist. `php artisan test --filter=PaymentStateRecalculationRulesTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentStateRecalculationContract.php app\\Support\\Payments\\PaymentStateRecalculationRules.php app\\Support\\Payments\\PaymentStateRecalculationCatalog.php tests\\Feature\\PaymentStateRecalculationRulesTest.php` passed.

Verification note: A5.2.6 completed on 2026-06-18. The implementation extends the shared audit event catalog with cancellation and refund lifecycle events and feature tests covering safe order-cancellation payloads, refund-request payloads, refund-recorded payloads, and recursive redaction of sensitive audit payload values. `php artisan test --filter=AuditEventContractTest` and `./vendor/bin/pint --test app\\Support\\Audit\\AuditEventCatalog.php tests\\Feature\\AuditEventContractTest.php` passed.

## A5.3 Payment Gateway Service Contract

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| A5.3.1 | Gateway interface contract | A5.1 | Checkout/admin use interface, not gateway-specific code | Contract tests | Payments, Checkout | High |
| A5.3.2 | Cashfree adapter contract | A5.3.1 | Cashfree maps to shared request/response shapes | Adapter tests | Payments | High |
| A5.3.3 | Payment attempt rules | A5.3.1, A4.5 | Attempts are traceable and idempotent | Attempt creation tests | Payments, Checkout | High |
| A5.3.4 | Payment verification contract | A5.3.1 | Verification updates payment records safely | Verification tests | Payments, Finance | High |
| A5.3.5 | Webhook contract | A5.3.1, A4.5 | Webhooks can be authenticated and deduplicated | Webhook contract tests | Payments, Security | High |
| A5.3.6 | Manual payment support | A5.1, A5.3.1 | Manual payments update balances through same records | Manual payment tests | Admin, Payments, Finance | Medium |
| A5.3.7 | Refund interface | A5.2, A5.3.1 | Refunds use shared status and record rules | Refund interface tests | Payments, Finance | High |
| A5.3.8 | Gateway failure handling | A5.3.1, A4.3 | Failed gateway calls are logged and recoverable | Failure/retry tests | Payments, Jobs | High |

Verification note: A5.3.1 completed on 2026-06-18. The implementation adds a shared payment gateway contract, gateway-agnostic payment gateway rules, a payment gateway catalog, and feature tests covering that checkout and admin depend on an interface that supports online initiation, webhook verification, refunds, idempotency, and order-first payment flow without hardcoding a specific provider. `php artisan test --filter=PaymentGatewayContractTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentGatewayContract.php app\\Support\\Payments\\PaymentGatewayRules.php app\\Support\\Payments\\PaymentGatewayCatalog.php tests\\Feature\\PaymentGatewayContractTest.php` passed.

Verification note: A5.3.2 completed on 2026-06-18. The implementation adds a shared Cashfree adapter contract, Cashfree adapter rules, a Cashfree adapter catalog, and feature tests covering that Cashfree maps shared request, response, webhook, and refund shapes while keeping provider-specific payloads isolated from shared business logic. `php artisan test --filter=CashfreeAdapterContractTest` and `./vendor/bin/pint --test app\\Contracts\\CashfreeAdapterContract.php app\\Support\\Payments\\CashfreeAdapterRules.php app\\Support\\Payments\\CashfreeAdapterCatalog.php tests\\Feature\\CashfreeAdapterContractTest.php` passed.

Verification note: A5.3.3 completed on 2026-06-18. The implementation adds a shared payment attempt contract, payment attempt rules, a payment attempt catalog, and feature tests covering that website checkout attempts are traceable, idempotent, terminal-state aware, and normalized without exposing secret metadata. `php artisan test --filter=PaymentAttemptRulesTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentAttemptContract.php app\\Support\\Payments\\PaymentAttemptRules.php app\\Support\\Payments\\PaymentAttemptCatalog.php tests\\Feature\\PaymentAttemptRulesTest.php` passed.

Verification note: A5.3.4 completed on 2026-06-18. The implementation adds a shared payment verification contract, payment verification rules, a payment verification catalog, and feature tests covering that verified payment payloads normalize into payment record fields, keep payments separate from refunds, and isolate provider payload data from the shared contract boundary. `php artisan test --filter=PaymentVerificationContractTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentVerificationContract.php app\\Support\\Payments\\PaymentVerificationRules.php app\\Support\\Payments\\PaymentVerificationCatalog.php tests\\Feature\\PaymentVerificationContractTest.php` passed.

Verification note: A5.3.5 completed on 2026-06-18. The implementation adds a shared payment webhook contract, payment webhook rules, a payment webhook catalog, and feature tests covering that webhook events are authenticated, deduplicated, and normalized into safe summary fields without exposing raw payloads or breaking replay-safety. `php artisan test --filter=PaymentWebhookContractTest` and `./vendor/bin/pint --test app\\Contracts\\PaymentWebhookContract.php app\\Support\\Payments\\PaymentWebhookRules.php app\\Support\\Payments\\PaymentWebhookCatalog.php tests\\Feature\\PaymentWebhookContractTest.php` passed.

Verification note: A5.3.6 completed on 2026-06-18. The implementation adds a shared manual payment contract, manual payment rules, a manual payment catalog, and feature tests covering that staff-recorded payments update the same shared payment records without requiring a payment attempt or gateway-specific flow. `php artisan test --filter=ManualPaymentRulesTest` and `./vendor/bin/pint --test app\\Contracts\\ManualPaymentContract.php app\\Support\\Payments\\ManualPaymentRules.php app\\Support\\Payments\\ManualPaymentCatalog.php tests\\Feature\\ManualPaymentRulesTest.php` passed.

Verification note: A5.3.7 completed on 2026-06-18. The implementation adds a shared refund interface contract, refund interface rules, a refund interface catalog, and feature tests covering that refund requests, approvals, processing, and terminal states use the shared refunds table, preserve original payments, and normalize refund payloads without leaking gateway-specific data. `php artisan test --filter=RefundInterfaceContractTest` and `./vendor/bin/pint --test app\\Contracts\\RefundInterfaceContract.php app\\Support\\Payments\\RefundInterfaceRules.php app\\Support\\Payments\\RefundInterfaceCatalog.php tests\\Feature\\RefundInterfaceContractTest.php` passed.

Verification note: A5.3.8 completed on 2026-06-18. The implementation adds a shared gateway failure handling contract, gateway failure handling rules, a gateway failure handling catalog, and feature tests covering safe logging, retryable vs non-retryable failure classes, queue retry windows, and payload normalization without exposing raw gateway data. `php artisan test --filter=GatewayFailureHandlingTest` and `./vendor/bin/pint --test app\\Contracts\\GatewayFailureHandlingContract.php app\\Support\\Payments\\GatewayFailureHandlingRules.php app\\Support\\Payments\\GatewayFailureHandlingCatalog.php tests\\Feature\\GatewayFailureHandlingTest.php` passed.

## B1.1 Public Category And Product APIs

Verification note: completed on 2026-06-18. The implementation adds a public catalog contract, public catalog rules, a public catalog catalog, a public catalog controller, API route registration, public visibility scopes on category/product models, and feature tests covering safe public category/product/SKU responses, detail routes, and Astro guidance. `php artisan test --filter=PublicCatalogApiTest` and `./vendor/bin/pint --test app\\Contracts\\PublicCatalogContract.php app\\Http\\Controllers\\Api\\PublicCatalogController.php app\\Models\\Product.php app\\Models\\ProductCategory.php app\\Providers\\AppServiceProvider.php app\\Support\\Products\\PublicCatalogRules.php app\\Support\\Products\\PublicCatalogCatalog.php tests\\Feature\\PublicCatalogApiTest.php routes\\api.php bootstrap\\app.php` passed.

## B2.2 Upload And Simple Mockup Preview

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| B2.2.1 | Design upload UI/API flow | A4.1, B2.1 | Customer can upload allowed design file | Upload flow tests | Website, Files | Medium |
| B2.2.2 | File validation integration | A4.1.1 | UI/API returns clear validation messages | Validation integration tests | Website, Files | Medium |
| B2.2.3 | Product preview display | A3.2.6, A4.1.3 | Uploaded design appears on selected product mockup | Preview tests | Website, Mockup | Medium |
| B2.2.4 | Placement controls | B2.2.3 | Customer can adjust within allowed print area | UI interaction tests | Website, Mockup | Medium |
| B2.2.5 | Customization metadata structure | B2.1, B2.2.3 | Metadata stores print position, method, file, placement | Metadata tests | Cart, Orders, Files | High |
| B2.2.6 | Cart persistence | B2.2.5, B3.1.1 | Customization remains in cart | Cart persistence tests | Cart, Website | High |
| B2.2.7 | Order persistence | B2.2.6, B3.1.7 | Customization snapshot is saved to order item | Order persistence tests | Orders, Admin | High |
| B2.2.8 | Authorized admin design-file access bridge | A4.1.5, B2.2.7, C1.1.1, C1.1.5 | Authorized staff can view/download order-linked design files through the admin order surface without exposing raw paths or bypassing file policy | Admin file permission tests | Admin, Files, Orders | High |

Verification note: B2.2.1 completed on 2026-06-19. The backend now accepts approved design uploads for public products, stores the original privately, returns public-safe file and preview metadata, and exposes a signed preview URL for the mockup step. `php artisan test --filter=DesignUploadFlowTest` passed.

Verification note: B2.2.2 completed on 2026-06-19. Invalid upload payloads are rejected before storage, and validation errors are returned for unsafe filenames and incompatible customization selections. `php artisan test --filter=DesignUploadFlowTest` passed.


Verification note: B2.2.3 completed on 2026-06-19. The preview flow now returns a public-safe SVG mockup for uploaded designs, rejects tampering and expired signed preview links, and keeps preview metadata free of raw storage paths. `php artisan test --filter=DesignUploadFlowTest` and `./vendor/bin/pint --test tests\Feature\DesignUploadFlowTest.php` passed.

Verification note: B2.2.4 completed on 2026-06-19. Placement adjustments now flow through a signed preview-link endpoint, clamp out-of-range values safely, and update the customer preview iframe through the Astro placement panel. `php artisan test --filter=DesignUploadFlowTest` and `npm run build` passed.

Verification note: B2.2.5 completed on 2026-06-19. Customization metadata now has a versioned public-safe snapshot shape with product/SKU selection, normalized placement, safe uploaded-file references, mockup preview metadata, and tests proving private storage paths are excluded from customer/cart/order-facing payloads. `php artisan test`, `./vendor/bin/pint --test app\\Http\\Controllers\\Api\\ProductCustomizationController.php app\\Support\\Products\\CustomizationSnapshotBuilder.php app\\Services\\FileUploadService.php app\\Services\\SettingsService.php tests\\Feature\\DesignUploadFlowTest.php tests\\Feature\\SettingsServiceTest.php`, and `npm run build` passed.

Verification note: B2.2.6 completed on 2026-06-19. The upload-flow customization snapshot is accepted by cart persistence, retained on cart items and reloads, and exposed through a public-safe cart payload without raw storage paths, preview paths, cart tokens, or internal database IDs. `php artisan test --filter=DesignUploadFlowTest`, `php artisan test --filter=CartStorageTest`, and `./vendor/bin/pint --test tests/Feature/DesignUploadFlowTest.php` passed.

Verification note: B2.2.7 completed on 2026-06-22. Verified that customization snapshot survives order creation and is persisted correctly on order items. Tested via AdminOrderItemFileAccessTest.php. `php artisan test` passed.

Verification note: B2.2.8 completed on 2026-06-22. Authorized staff with `orders.view` and `files.download_private` permissions can access private design files preview/download via public_id order-scoped routes, while unauthorized/unauthenticated users are strictly blocked. Tested via AdminOrderItemFileAccessTest.php. `php artisan test` passed.

## B3.1 Cart And Checkout With Pending Order Creation

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| B3.1.1 | Cart storage | A2.2, A3.2 | Cart persists for guest/session or customer as approved | Cart storage tests | Cart, Customer | Medium |
| B3.1.2 | Cart item validation | A3.2, B2.1 | Invalid SKUs/options cannot proceed | Cart validation tests | Cart, Products | High |
| B3.1.3 | Price recalculation | A3.2, A5.1.4 | Backend recalculates totals, not frontend | Price tests | Cart, Orders, Finance | High |
| B3.1.4 | Customer and address validation | A3.1, A2.2 | Checkout requires valid customer/address | Checkout validation tests | Customers, Checkout | High |
| B3.1.5 | Bulk quantity detection | B3.1.2, B3.1.3 | 25+ returns a bulk handoff response without requiring full CRM/quotation implementation | Bulk flow tests | Checkout, CRM, Quotations | High |
| B3.1.6 | Pending-order creation | A5.1.4-A5.1.5, B3.1.1-B3.1.4 | Pending order created before payment attempt | Pending order tests | Checkout, Orders | High |
| B3.1.7 | Order item/customization storage | B2.2.6, B3.1.6 | Items preserve SKU, price, quantity, customization snapshot | Order item tests | Orders, Products, Files | High |
| B3.1.8 | Payment-attempt creation | A5.3.3, B3.1.6 | Attempt is linked to pending order | Payment attempt tests | Checkout, Payments | High |
| B3.1.9 | Duplicate checkout prevention | A4.5, B3.1.6, B3.1.8 | Repeated checkout does not duplicate orders/attempts | Idempotency tests | Checkout, Orders, Payments | High |
| B3.1.10 | Failed checkout handling | B3.1.6, B3.1.8 | Failed checkout leaves traceable pending order/attempt | Failure path tests | Checkout, Orders, Payments | High |

Verification note: B3.1.1 completed on 2026-06-19. The cart layer now persists database-backed guest and customer carts with public-safe item snapshots, duplicate-item fingerprinting, session-scoped guest ownership, and feature tests covering storage, ownership, snapshot retention, and payload safety. `php artisan test --filter=CartStorageTest` and `./vendor/bin/pint --test app/Support/Products/CustomizationSnapshotBuilder.php app/Services/CartService.php app/Services/CartResponsePresenter.php app/Http/Controllers/Api/CartController.php app/Http/Requests/Cart/StoreCartItemRequest.php app/Http/Requests/Cart/UpdateCartItemRequest.php app/Models/Cart.php app/Models/CartItem.php tests/Feature/CartStorageTest.php` passed.

Verification note: B3.1.2 completed on 2026-06-19. The cart validation endpoint now checks product availability, SKU checkout eligibility, quantity limits, and customization-rule drift while returning a public-safe validation payload. `php artisan test --filter=CartValidationTest`, `php artisan test --filter=CartStorageTest`, `php artisan test --filter=CustomizationOptionApiTest`, and `./vendor/bin/pint --test app/Http/Controllers/Api/CartController.php app/Services/CartValidationService.php routes/api.php tests/Feature/CartValidationTest.php` passed.

## B3.3 Payment Webhook Handling

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| B3.3.1 | Webhook authentication | A5.3.5 | Invalid signatures are rejected | Webhook security tests | Payments, Security | High |
| B3.3.2 | Event parsing | B3.3.1 | Supported events map to internal event types | Event parser tests | Payments | Medium |
| B3.3.3 | Payment-attempt matching | B3.3.2, A5.3.3 | Event matches correct attempt/order | Matching tests | Payments, Orders | High |
| B3.3.4 | Payment record creation | B3.3.3 | Valid paid event creates/updates payment record | Payment record tests | Payments, Finance | High |
| B3.3.5 | Duplicate webhook prevention | A4.5, B3.3.4 | Duplicate event does not duplicate payment | Idempotency tests | Payments | High |
| B3.3.6 | Payment-status recalculation | A5.1.3, B3.3.4 | Order payment status recalculates from records | Payment status tests | Payments, Orders, Finance | High |
| B3.3.7 | Failed-payment handling | B3.3.2, B3.3.3 | Failed event is recorded without corrupting order | Failed payment tests | Payments, Orders | High |
| B3.3.8 | Refund webhook handling | A5.2, A5.3.7 | Refund event updates refund/payment records once | Refund webhook tests | Payments, Finance | High |
| B3.3.9 | Logging and retry tests | A4.3, B3.3.1-B3.3.8 | Failures are logged and retry behavior is safe | Logging/retry tests | Payments, Jobs | High |

## B4.1 Customer Dashboard

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| B4.1.1 | Documentation updates | None | `task-list.md` and `subtask-validation.md` updated with dashboard subtasks and validation rules | Documentation review | Docs | Low |
| B4.1.2 | API route definitions | A2.2 | Routing for session, profile, addresses (CRUD/default), orders, and reordering defined and protected | API route tests | API, Auth | Medium |
| B4.1.3 | Address management | A3.1 | Customers can list, create, update, delete, and toggle default status of shipping/billing addresses safely | CustomerAddress tests | Customers, Addresses | High |
| B4.1.4 | Order details & tracking | A5.1.2 | Order list and detail endpoints show customer-friendly status, payments, and signed design preview URLs | CustomerOrder tests | Orders, Files | High |
| B4.1.5 | Reorder & support actions | B3.1 | Reorder endpoint copies past order items to active database cart; support action details provided | CustomerReorder tests | Orders, Cart | High |
| B4.1.6 | Test suite, linting & PHPStan | B4.1.2-B4.1.5 | Entire test suite passes; Pint formatting and PHPStan static analysis verify code correctness | Feature tests, Pint, PHPStan | All Modules | Medium |

Verification note: completed on 2026-06-22. The customer dashboard is fully implemented in the Astro frontend utilizing client-side fetch calls to backend API endpoints under `web` and `customer.access` middleware groups. Comprehensive feature tests in `CustomerDashboardApiTest.php` cover session verification, profile retrieval, shipping/billing address CRUD + default setting, secure order details, signed preview generation, and reordering. All tests pass, and Laravel Pint formatting is verified.

## B4.2 Customer Tracking Page

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| B4.2.1 | Customer tracking UI & Routing | B4.1.1, C4.1, C4.2 | Astro customer order detail page updated with dynamic tracking timeline, shipment cards, estimated delivery info, and support links | Page validation/smoke tests | Website | Medium |
| B4.2.2 | Timeline calculation logic | A5.1.2, C4.1 | Timeline calculated dynamically (placed, advance/balance payments, design, production, ready to ship, shipped, delivered) | Timeline logic tests | Orders, Tracking | High |
| B4.2.3 | API data exposure | B4.1.2 | Customer order API exposes tracking attributes (courier, tracking code, URLs, dates, design/production/shipping states) and timeline array | Customer API tracking tests | API, Customer | High |
| B4.2.4 | Test suite, linting & PHPStan | B4.2.1-B4.2.3 | Entire test suite passes, Laravel Pint formatting applied | CustomerTrackingApiTest | All Modules | Medium |

Verification note: completed on 2026-06-22. The customer tracking page is fully implemented in the Astro frontend utilizing client-side fetch calls to backend API endpoints (dynamic timeline generator). Feature tests in `CustomerTrackingApiTest` cover website and sales order timeline calculations, customer API data exposure, admin status update validations with timestamps, admin shipping detail updates, and role-based permissions protection. All tests passed, and Pint formatting is applied.

## C4.1 Simple Order Processing

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C4.1.1 | Operational status transitions | A5.1.2 | Admin can update order status (pending_payment, confirmed, in_production, ready_to_ship, shipped, delivered, cancelled, refunded) | Status update tests | Orders | Medium |
| C4.1.2 | Authorization policy checks | A2.3 | Endpoint permission-gated (requires role-based update permission) | Policy authorization tests | Auth, Orders | High |
| C4.1.3 | Automatic timestamp updates | A5.1.2 | Timestamps (confirmed_at, ready_to_ship_at, shipped_at, etc.) auto-update based on status updates | Timestamp update tests | Orders | Medium |
| C4.1.4 | Order-status workflow tests | C4.1.1-C4.1.3 | Feature test suite verifies status flow correctness | CustomerTrackingApiTest | Orders | Medium |

Verification note: completed on 2026-06-22. Status changes are implemented under admin controller endpoints, validating status rules and timestamps for confirmed, cancelled, ready to ship, shipped, and delivered states. Role-based permissions verify that only authorized staff roles can update status fields. Covered by `CustomerTrackingApiTest`.

## C4.2 Shipping Details

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C4.2.1 | Shipment data fields | A1.1.4 | Storing courier_name, tracking_number, tracking_url, estimated_delivery_at | Database storage tests | Orders | Medium |
| C4.2.2 | Tracking detail entry | C4.1 | Admin endpoint allows staff to save shipping/tracking details | Shipping details update tests | Orders | Medium |
| C4.2.3 | Authorization validation | A2.3 | Endpoint permission-gated (requires role-based update permission) | Policy authorization tests | Auth, Orders | High |
| C4.2.4 | Shipping workflow tests | C4.2.1-C4.2.3 | Feature tests cover update, permissions validation | CustomerTrackingApiTest | Orders | Medium |

Verification note: completed on 2026-06-22. Shipment details (courier name, tracking number, URL, estimated delivery) are saved via admin endpoints, with customer order detail endpoints returning safe tracking payloads. Role-based permissions protect the endpoints. Covered by `CustomerTrackingApiTest`.

## C1.2 Sales Order Creation

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C1.2.1 | Customer selection | A3.1, C1.1 | Staff can select/create allowed customer | Admin/customer tests | Admin, Customers | Medium |
| C1.2.2 | Product/SKU selection | A3.2, C1.1 | Staff can select valid product/SKU | Admin product tests | Admin, Products, Orders | Medium |
| C1.2.3 | Quantity and customization | C1.2.2, B2.2.5 | Staff can enter quantity and customization details | Sales item tests | Orders, Files | Medium |
| C1.2.4 | Pricing and discount | A5.1.4, C1.2.3 | Totals reflect approved prices/discounts | Pricing tests | Orders, Finance | High |
| C1.2.5 | Advance/final payment structure | A5.3.3, C1.2.4 | Sales order can track advance and balance | Payment schedule tests | Orders, Payments, Finance | High |
| C1.2.6 | Order creation | C1.2.1-C1.2.5 | Sales order is created with correct status and totals | Sales order creation tests | Orders, Admin | High |
| C1.2.7 | Order editing rules | C1.2.6, A4.6 | Edits are permissioned and emit audit events; permanent storage is verified by C6.1 integration | Edit/audit-event tests | Orders, Audit | High |
| C1.2.8 | Order confirmation | C1.2.6, A5.1.2 | Staff can confirm order according to rules | Confirmation tests | Orders, Production | Medium |

Verification note: C1.2.7 completed on 2026-06-23. The order editing rules are fully implemented with permission checks (`Gate::authorize`), pessimistic locking (`lockForUpdate`) on both the order and order items inside the transaction, and automatic totals recalculation. An audit event (`orders.order_edited`) is emitted inside `DB::afterCommit` with a sanitized payload tracking header and item-level changes. Covered by 7 feature tests in `SalesOrderEditTest.php` and verified by `AuditEventContractTest.php`. All tests passed.

Verification note: C1.2.8 completed on 2026-06-23. The order confirmation transition is protected using OrderStatus::canTransitionTo() and updateStatus validation rules. Verified via OrderConfirmationTest.php with 7 tests and 24 assertions. All tests passed, and Laravel Pint formatting is applied.

## C3.1 CRM Lead Module

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C3.1.1 | `leads` table migration, `Lead` Eloquent model, `LeadFactory`, and unit tests | A2.3, A3.1, A4.3 | Migration runs and rolls back cleanly; `Lead` auto-generates `public_id` starting with `LD-`; all fields and indexes from `crm-quotations-schema.md` are present; status/priority enums are enforced; unit tests pass | `tests/Unit/LeadModelTest.php`, migrate/rollback check | CRM, Database | High |
| C3.1.2 | Manual lead capture endpoint and admin form | A2.3, C3.1.1 | Authorized staff can create a lead with contact, source, priority, and product interest data; unauthenticated/unauthorized requests are rejected; validation errors are returned for missing or invalid fields | Manual lead creation tests | CRM, Admin, Auth | Medium |
| C3.1.3 | Website/bulk lead capture endpoint | C3.1.1, B3.1.5 | Website bulk enquiry submits a lead with raw contact, UTM, referrer, and product interest data; duplicate detection prevents identical submissions within a short window; no customer record is created without qualification | Bulk enquiry endpoint tests | CRM, Website | Medium |
| C3.1.4 | Source, referrer, page, and UTM attribution storage | C3.1.1, C3.1.3 | UTM source/medium/campaign/content/term, referrer URL, and landing page URL are stored on the lead; attribution data is excluded from customer-facing APIs; internal views expose attribution fields | Attribution field tests | CRM, Website | Low |
| C3.1.5 | Lead lifecycle, ownership, and assignment | C3.1.1, A2.3 | Status can progress through `new`, `assigned`, `contacted`, `qualified`, `quoted`, `won`, `lost`, `spam`; invalid status transitions are rejected; assignment to a staff user is recorded and triggers an activity entry; `assigned_to_user_id` updates safely | Lead status/assignment tests | CRM, Admin, Auth | High |
| C3.1.6 | Notes and activity timeline | C3.1.1, C3.1.5 | Staff can add notes to a lead; activity entries are created for status changes, assignments, and notes; activity timeline is returned in chronological order; sensitive content is not exposed in customer-facing APIs | Activity timeline tests | CRM, Admin | Medium |
| C3.1.7 | Lead authorization, list/detail views, and regression tests | A2.3, C3.1.1–C3.1.6 | Only authorized staff roles can list, view, create, assign, and update leads; unauthorized roles receive 403; lead list returns paginated safe summaries; lead detail returns full authorized view; all lead flow regression tests pass | Lead authorization tests, lead list/detail tests | CRM, Admin, Auth | High |

Verification note: C3.1.1 completed on 2026-06-23. The implementation adds a safe `create_leads_table` migration with all fields and 8 composite indexes from `crm-quotations-schema.md`, a `Lead` Eloquent model with auto-generated `LD-` public_id, default status/priority/country_code on creation, JSON casts for `product_interest`, datetime casts for 4 lifecycle timestamp fields, helper methods (`isOpen`, `isConverted`, `hasContactRoute`), CRM query scopes (`open`, `assignedTo`, `byStatus`, `bySource`, `byPriority`), and BelongsTo relations to `customer`, `assignedTo`, and `creator`. `LeadFactory` includes 10 named states. `php artisan migrate --force`, `php artisan test --filter=LeadModelTest` (31 tests, 72 assertions), and `php artisan test` (278 tests, 1612 assertions) passed. `./vendor/bin/pint` applied and passed.

Verification note: C3.1.2 completed on 2026-06-23. The manual lead capture endpoint is fully implemented under `POST /admin/leads` and protected by the `['auth', 'dashboard.access']` middleware group. Gated via `LeadPolicy` requiring `leads.manage` permission. Input validation via `StoreLeadRequest` utilizes centralized `Lead::SOURCES`, `Lead::STATUSES`, and `Lead::PRIORITIES` constants. The response is public-safe and contains no internal database IDs. Covered by 6 feature tests in `ManualLeadCaptureTest.php`. All tests passed, and Laravel Pint formatting is applied.

Verification note: C3.1.3 completed on 2026-06-23. The website bulk enquiry endpoint is implemented under `POST /api/catalog/leads`. Spammers and double submissions are prevented via duplicate fingerprint detection (checks matching email/phone and product interests within 5 minutes) returning HTTP 422. Input validation is performed by `StorePublicLeadRequest` with size limits on product interest arrays and URL validation for referrer and landing URLs. Source and status are forced by the server to `website_bulk_enquiry` and `new`. The public JSON response excludes internal numeric IDs and UTM/attribution fields. Covered by 7 feature tests in `WebsiteLeadCaptureTest.php`. All tests passed, and Laravel Pint formatting is applied.

Verification note: C3.1.4 completed on 2026-06-23. The implementation verified the validation and storage of all UTM parameters and page attribution URLs on guest submissions, added validation constraint checks for length and format to WebsiteLeadCaptureTest, added response assertions ensuring internal database IDs do not leak, and verified in LeadModelTest that UTM and attribution fields serialize correctly on the model level. `php artisan test --filter=WebsiteLeadCaptureTest`, `php artisan test --filter=LeadModelTest`, and `./vendor/bin/pint --test` passed.

Verification note: C3.1.5 completed on 2026-06-23. The implementation adds a `create_lead_activities_table` migration with all fields and 3 composite indexes from `crm-quotations-schema.md`, a `LeadActivity` model with fillable fields, type constants, casts, `lead`/`createdBy` relations, and two static factory helpers (`recordStatusChange`, `recordAssignment`). `Lead::activities()` HasMany relation added. `LeadController::update()` now auto-creates `status_change` activity entries (with `from_status`/`to_status` in metadata) and `assignment` activity entries (with `previous_assigned_to_user_id`/`new_assigned_to_user_id` in metadata) after each successful save. Activity logging is skipped entirely on invalid transitions so no partial state is written. `LeadStatusAssignmentTest` extended from 5 to 11 tests covering: unauthorized access, valid status/assignment update, invalid transition rejection, lost-lead reopening, `lost_reason` validation, public-safe response, status-change activity creation, assignment activity creation, simultaneous two-activity creation, no-change no-activity, metadata safety, and invalid-transition no-activity. `php artisan migrate --force`, `php artisan test --filter=LeadStatusAssignmentTest` (11 tests, 53 assertions), and `php artisan test` (306 tests, 1812 assertions) passed. `./vendor/bin/pint` applied and passed.

Verification note: C3.1.6 completed on 2026-06-23. The notes and activity timeline is fully implemented. Staff can create a note (and other staff-initiated types: call, email, whatsapp) via `POST /admin/leads/{lead}/activities`, which are validated using `StoreLeadActivityRequest`. The timeline is returned via `GET /admin/leads/{lead}/activities` in ascending chronological order of `occurred_at`, including system status changes and assignments. Both endpoints are gated by `leads.manage` permission via `LeadPolicy::viewActivities` and `LeadPolicy::createActivity`. Responses are public safe and contain no internal database IDs. Customer-facing APIs do not expose activity logs. Covered by 10 feature tests in `LeadActivityTimelineTest.php`. All 316 tests passed and Pint applied.

Verification note: C3.1.7 completed on 2026-06-23. Lead authorization, list, and detail views are fully implemented. Gated via `LeadPolicy::viewAny` and `LeadPolicy::view` requiring either `leads.view` or `leads.manage` permission. The list view returns a paginated structure mapped to safe summary fields (excludes internal IDs, requirements, UTM fields, referrer/landing URLs, and lost reason, but allows `assigned_to_user_id`), ordered newest first (`latest('created_at')`). The detail view returns full CRM data (includes UTM fields, requirements, landing page, etc., but hides internal database IDs `id`, `customer_id`, `created_by_user_id`). Covered by 10 feature tests in `LeadDetailListTest.php`. All 326 tests passed and Pint applied.

## C1.3 Quotations And Bulk-Order Conversion

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C1.3.1 | Bulk enquiry capture | C3.1, B3.1.5 | Bulk enquiry creates lead/quote source data | Enquiry tests | CRM, Website | Medium |
| C1.3.2 | Quotation creation | C1.3.1 | Staff can create quotation from lead/manual entry | Quotation CRUD tests | Quotations, Admin | Medium |
| C1.3.3 | Quotation items and pricing | A3.2, A5.1.4, C1.3.2 | Quotation has items, quantities, prices, totals | Quotation pricing tests | Quotations, Products, Finance | High |
| C1.3.4 | Quotation status | C1.3.2 | Statuses support draft/sent/approved/rejected/revised as approved | Status tests | Quotations | Medium |
| C1.3.5 | Customer approval | C1.3.4 | Approval is recorded before order conversion | Approval tests | Quotations, Customers | Medium |
| C1.3.6 | Quotation revision | C1.3.4 | Revision preserves history | Revision/audit tests | Quotations, Audit | Medium |
| C1.3.7 | Sales-order conversion | A5.1.7, C1.2.6, C1.3.5 | Approved quote converts once to sales order | Conversion/idempotency tests | Quotations, Orders | High |
| C1.3.8 | Advance-payment recording | C1.3.7, C5.1 | Advance payment can be recorded against sales order | Advance payment tests | Orders, Payments, Finance | High |

Verification note: C1.3.1 completed on 2026-06-23. The bulk enquiry capture flow connects the website checkout quantity block and CRM Lead capture. Validated that checkout blocks bulk order quantities (item count >= 25) and returns a bulk handoff response advising next_step = bulk_enquiry (handled by CheckoutValidationService). The frontend then submits to the public leads capture API, creating a new lead with source = website_bulk_enquiry (handled by PublicLeadController). Covered by a dedicated feature integration test in `BulkEnquiryCaptureBridgeTest.php`. All 327 tests passed and Pint applied.

Verification note: C1.3.2 completed on 2026-06-23. Staff can create quotations originating from a qualified lead, customer, or manual entry with reachability rules. Validates early that lead or customer public IDs exist, enforces source exclusivity, auto-populates valid_until to 30 days from now, and computes taxes using PHP_ROUND_HALF_UP on a defensive taxable amount base. Returns public-safe payloads hiding internal database IDs. Formatted via Laravel Pint and validated via QuotationCreationTest.php with 16 tests and 92 assertions. All 343 tests passed.

Verification note: C1.3.3 completed on 2026-06-23. Quotation item and pricing logic is fully integrated and tested as part of the core quotation controller and request flow. Item pricing is resolved deterministically (explicit override -> SKU price -> product base price -> 422 validation failure). Subtotal, discounts (capped at subtotal), shipping, tax, and order totals are calculated using OrderTotalsCalculator. Enforces defensive taxable base calculations and PHP_ROUND_HALF_UP rounding rules. Formatted using Laravel Pint and fully tested via QuotationCreationTest.php. All 343 tests passed.

Verification note: C1.3.4 completed on 2026-06-23. Quotation status transitions are implemented with state transition validation, automated timestamp logging, and a dedicated status update endpoint. Validates transitions based on the recommended sales workflow state machine (draft -> sent -> cancelled/approved/rejected/revision_requested/expired -> revised/sent -> cancelled/converted -> terminal). Supports `revised_at` timestamp. Rejects identical state transitions with a 422 validation failure, and blocks modifications on terminal states (converted/cancelled). Returns full updated quotation JSON payloads. Formatted via Laravel Pint and validated via QuotationStatusTest.php with 6 tests and 46 assertions. All 349 tests passed.

Verification note: C1.3.5 completed on 2026-06-23. Customer approval and rejection logic is fully implemented and tested. Added a unique secure `approval_token` column to the `quotations` table and verified using constant-time `hash_equals()` validation on public customer endpoints. Created the `quotation_approval_events` table and model to log a complete history of events (sent, approved, rejected, cancelled, revision_requested, revised, expired, converted). Supports header/body `idempotency_key` checking to avoid duplicate event records. Enforces that only approved quotations can transition to the `converted` state. Formatted via Laravel Pint and validated via QuotationApprovalTest.php with 10 tests and 38 assertions. All 359 tests passed.

Verification note: C1.3.7 completed on 2026-06-23. Approved quotations convert atomically to confirmed sales orders via POST /admin/quotations/{id}/convert. Conversion blocks with 422 if: (1) status is not approved, (2) already converted, (3) no live customer_id, or (4) any quotation item has no product_sku_id — preserving order total integrity. Quotation totals are copied verbatim. OrderItems have price_source = quotation_conversion. Idempotency is protected via conversion_idempotency_key. TOCTOU is guarded by lockForUpdate inside DB::transaction. QuotationApprovalEvent with event_type = converted is logged atomically inside the same transaction. Formatted via Laravel Pint and validated via QuotationConversionTest.php with 12 tests and 65 assertions. All 379 tests passed.

Verification note: C1.3.8 completed on 2026-06-24. Advance/manual payment recording is fully implemented via POST /admin/orders/{order}/payments. Gated by `payments.record` permission and `OrderPolicy@recordPayment`. Validates that payment amount does not exceed the remaining balance and that the order is not in a terminal state (cancelled/refunded). Generates a unique receipt number starting with `RC-`. Emits `AuditEvent` with type `payments.payment_recorded`. Supports idempotency key verification. Verified via ManualPaymentRecordingTest.php with 8 tests and 31 assertions. All tests passed and Laravel Pint formatted.

## C2.1 Inventory Movements And Stock Handling

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C2.1.1 | SKU stock balance | A3.2.4 | Current balance is visible per SKU | Balance tests | Inventory, Products | Medium |
| C2.1.2 | Stock-in | C2.1.1 | Purchased/manual stock-in increases balance | Stock-in tests | Inventory, Purchases | Medium |
| C2.1.3 | Stock-out | C2.1.1 | Manual stock-out decreases balance with reason | Stock-out tests | Inventory | Medium |
| C2.1.4 | Manual adjustment | C2.1.1, A4.6 | Adjustment requires reason and emits audit event; permanent storage is verified by C6.1 integration | Adjustment/audit-event tests | Inventory, Audit | High |
| C2.1.5 | Order stock deduction | C1.1, C2.1.1 | Order deduction reduces stock when staff marks/executes deduction | Deduction tests | Orders, Inventory | High |
| C2.1.6 | Cancellation stock reversal | A5.2, C2.1.5 | Cancelled deducted stock can be reversed once | Reversal/idempotency tests | Orders, Inventory | High |
| C2.1.7 | Low-stock warning | C2.1.1 | Low stock warning appears by configured threshold | Warning tests | Inventory, Notifications | Medium |
| C2.1.8 | Movement history and audit events | C2.1.2-C2.1.7, A4.6 | All movements are traceable and emit audit events; permanent storage is verified by C6.1 integration | History/audit-event tests | Inventory, Audit | High |

Verification note: C2.1.1 completed on 2026-06-27. Created the `inventory_items` table with database check constraints and chunked existing SKU backfill. Created the `InventoryItem` model, `InventoryBalanceService` for atomic transaction synchronization, and `ProductSkuObserver` for SKU auto-initialization. All tests in `InventoryItemTest.php` and the full test suite passed.

Verification note: C2.1.2 completed on 2026-06-27. Implemented the append-only `inventory_movements` database table with snapshots (including `before_available` and `after_available`). Created typo-safe enums (`InventoryMovementType`, `InventoryDirection`, and `InventoryMovementReason`), protected the `InventoryMovement` model's immutability inside booting hooks, and implemented race-safe transaction idempotency checks inside the service. Covered by 8 feature tests in `InventoryStockInTest.php`.

Verification note: C2.1.3 completed on 2026-06-27. Implemented `stockOut` API in `InventoryBalanceService` propagating through the unified `recordMovement` primitive. Introduced a domain-specific `InsufficientStockException` thrown by the service when stock-out violates limits, extended `InventoryMovementReason` with future-proofing reason cases, and asserted exact snapshots, sequential ordering, and negative stock overrides in tests. Covered by 8 feature tests in `InventoryStockOutTest.php`.

Verification note: C2.1.4 completed on 2026-06-27. Implemented the `adjust` API in `InventoryBalanceService` supporting absolute balances manual correction for both on-hand and reserved fields, with pessimistic locking and early idempotency checks inside the transaction. Calculates change quantity as the maximum absolute delta, enforces zero-change validation, and dispatches the `inventory.stock_moved` audit event. Covered by 8 feature tests in `InventoryManualAdjustmentTest.php`.

Verification note: C2.1.5 completed on 2026-06-27. Implemented the `deductOrderStock` API in `InventoryBalanceService` allowing atomic order stock deduction. Features deadlock prevention via eager loading and sorting by inventory item IDs, duplicate SKU lock avoidance, missing inventory checks (`InventoryItemNotFoundException`), transaction deadlock retries, database-level unique key integrity constraint, and duplicate-safe audit dispatch. Covered by 7 feature tests in `InventoryOrderDeductionTest.php`.

Verification note: C2.1.6 completed on 2026-06-27. Implemented the `reverseOrderStock` API in `InventoryBalanceService` allowing atomic cancellation stock reversal. Extends `InventoryMovementReason` with `ORDER_CANCELLATION`, features eager loading, deadlock-preventing sorting, locked inventory item caching, and checks duplicate reversals using `order_id` and `order_item_id` inside the lock. Covered by 8 feature tests in `InventoryCancellationReversalTest.php` including complete lifecycle tests (Deduct -> Deduct -> Reverse -> Reverse).

Verification note: C2.1.7 completed on 2026-06-27. Implemented low-stock warning detection inside `recordMovement` in `InventoryBalanceService`. Features dynamic threshold resolution encapsulated in `resolvedLowStockThreshold()` helper on `InventoryItem`, threshold crossing verification, after-commit dispatch of the structured `LowStockDetected` event (carrying ProductSku, available quantity, threshold, causing InventoryMovement), and structured context logging. Covered by 6 feature tests in `InventoryLowStockWarningTest.php` including sequences, override resolution, and multiple movement types.

Verification note: C2.1.8 completed on 2026-06-29. Exposed query interface `getMovementHistory` and builder resolver `movementHistoryQuery` on `InventoryBalanceService` supporting whitelisted sorting, inclusive date-only range boundaries, and default eager loading. Verified that all 5 movement types dispatch the expected `AuditEvent` payload, and that idempotency locks prevent duplicate event dispatches. Tested via `InventoryMovementHistoryTest.php` with all tests passing, Pint formatting checked, and PHPStan analysis passing with zero errors.

## C2.2 Vendors And Purchases

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C2.2.1 | Vendor management | A2.3 | Authorized staff can create/edit vendors | Vendor CRUD tests | Vendors, Admin | Medium |
| C2.2.2 | Purchase order creation | C2.2.1 | Staff can create purchase order | Purchase CRUD tests | Purchases, Vendors | Medium |
| C2.2.3 | Purchase order items | A3.2.4, C2.2.2 | Purchase items reference SKUs | Purchase item tests | Purchases, Products | Medium |
| C2.2.4 | Purchase status | C2.2.2 | Purchase status tracks order/received/cancelled as approved | Status tests | Purchases | Medium |
| C2.2.5 | Stock receiving | C2.1.2, C2.2.3 | Received stock increases SKU balance | Receiving tests | Purchases, Inventory | High |
| C2.2.6 | Partial stock receiving | C2.2.5 | Partial receiving updates received/pending quantities | Partial receiving tests | Purchases, Inventory | High |
| C2.2.7 | Purchase payment tracking | C5.1, C2.2.2 | Vendor payment status is trackable | Purchase payment tests | Purchases, Finance | Medium |
| C2.2.8 | Vendor-order history | C2.2.1-C2.2.7 | Vendor page shows related purchase history | History tests | Vendors, Purchases | Low |

Verification note: C2.2.1 completed on 2026-06-29. Implemented the `vendors` database migration with soft deletes, unique `vendor_code` constraint, and contact/tax fields. Added `Vendor` Eloquent model with `VendorStatus` backed enum casting, uppercase/trim mutators for `gstin` and `country_code`, and `scopeActive()`. Built `VendorController` supporting search whitelisting, creation with `VendorCodeGenerator` helper, and user tracking, all gated by `VendorPolicy` and `vendors.manage`/`vendors.view` permissions. Dispatched structured `AuditEvent` dispatches on create, update, and delete actions. Fully covered by 5 feature tests in `VendorManagementTest.php` with all tests passing, Pint checks passing, and PHPStan analysis returning zero errors.

Verification note: C2.2.2 completed on 2026-06-29. Implemented database migration for `vendor_orders` table with restricted delete constraints on vendor relationship. Built `VendorOrder` model using enums, custom domain exceptions, pure total calculation method, explicit `changeExpectedAt` date chronology check, and custom status/payment transition methods that mutate state in memory. Implemented `VendorOrderController` with search support, eager loaded relationship optimization, active vendor checks, custom exception throwing, and DB transactions emitting audit events inside `DB::afterCommit()`. Fully covered by 10 comprehensive feature tests in `PurchaseOrderCreationTest.php` with all tests passing, Pint checks passing, and PHPStan analysis returning zero errors.

Verification note: C2.2.3 completed on 2026-06-29. Implemented database migration for `vendor_order_items` table with composite unique constraint on `(vendor_order_id, product_sku_id)`, restrict-on-delete FK to `product_skus`, and snapshot columns (`sku_code_snapshot`, `product_name_snapshot`). Built `VendorOrderItem` model with Fillable attribute, casts, `calculateLineTotal()` pure method, explicit `changeExpectedAt()` date chronology check, and BelongsTo relations. Built `VendorOrderItemController` with authorization via `VendorOrderItemPolicy`, `PurchaseOrderImmutableException` guard, concurrent QueryException translation to 422, DB transaction wrapping with `DB::afterCommit` `AuditEvent` dispatches, and parent PO total recalculation on every write. Request validations in `StoreVendorOrderItemRequest` (unique SKU per PO) and `UpdateVendorOrderItemRequest`. Covered by 7 feature tests in `PurchaseOrderItemTest.php` including CRUD with total propagation, duplicate SKU rejection, concurrency race condition handling, immutability guard, snapshot preservation, mass-assignment protection, and RESTRICT delete constraint. All tests passing and Pint checks passing.

Verification note: C2.2.4 completed on 2026-06-29. Implemented `POST /admin/purchase-orders/{purchase_order}/status` endpoint. Created `UpdateVendorOrderStatusRequest` with Rule::enum constraints. Modified `VendorOrder` model to validate same-status transitions as invalid (throws exception) to keep transitions atomic. Controller wraps execution inside `DB::transaction`, runs status transition first, resolves Auth::user early, refreshes model state to synchronize Carbon datetimes, and triggers `AuditEvent` dispatch inside `DB::afterCommit`. Maps exceptions to status/payment_status keys on 422 responses. Fully covered by 6 feature tests in `PurchaseOrderStatusTest.php` including gated access, valid transitions (draft -> ordered -> partially_received -> received -> closed, draft -> cancelled), same-status and invalid transitions rejection with validation payloads, database idempotency on failure, transaction rollback, timestamp immutability (ordered_at, received_at, cancelled_at), and payment status transitions. All tests and Pint styling checks pass.

Verification note: C2.2.5 completed on 2026-06-29. Implemented `POST /admin/purchase-orders/{purchase_order}/items/{item}/receive` stock receiving endpoint. Created `ReceiveVendorOrderItemStockRequest` with integer validation, and `PurchaseOrderNotReceivableException` exception. The controller receive logic runs within `DB::transaction()`, locks parent `VendorOrder` and all child `VendorOrderItem` rows, validates route-model relationship, asserts status eligibility, performs authoritative transaction-level quantity validation, increases inventory balances via `InventoryBalanceService` (reason: `PURCHASE_RECEIPT`), updates model states, and triggers `AuditEvent` dispatch inside `DB::afterCommit`. Fully covered by 7 feature tests in `PurchaseOrderStockReceivingTest.php` including gated access, route safety, valid receiving (partial vs full), multi-receive accumulation, multi-item order progression, invalid status immutability, concurrent racing checks, and exact audit payload checks. All tests and Pint checks pass.

Verification note: C2.2.6 completed on 2026-06-29. Partial stock receiving is inherently supported and handled dynamically within C2.2.5's implementation. Multiple partial receiving increments are tracked and verified. Status transitions to `partially_received` immediately upon first receive, remaining in that state until all order lines are fully received, at which point the status shifts to `received`. Tested and verified via the `test_partial_receiving_twice_and_zero_remaining_regression` and `test_multi_item_po_status_advancement` tests.

Verification note: C2.2.7 completed on 2026-06-29. Implemented database migration for `vendor_payments` table, `VendorPayment` model, backed enums `VendorPaymentStatus` and `VendorPaymentMethod`. Configured relations and `recalculatePaymentStatus()` in `VendorOrder`. Created validation rules in `StoreVendorPaymentRequest` using `Rule::enum`. Added `VendorPaymentController` wrapping execution inside `DB::transaction()`, acquiring `lockForUpdate` on parent PO and child payments collection, throwing custom domain exceptions, and triggering `AuditEvent` dispatches inside `DB::afterCommit` after non-optional model refreshes. Fully covered by 8 feature tests in `PurchaseOrderPaymentTest.php` including authorization, zero payments initial state, single/multiple partial payments, duplicate references, overpayment, zero remaining balance attempts, and database transaction rollbacks. All tests and Pint checks pass.

Verification note: C2.2.8 completed on 2026-06-29. Added `purchaseOrders()` relationship on the `Vendor` model. Created `scopeFilter()` local scope on the `VendorOrder` model to reuse status, payment status, and search filters. Configured the nested route `GET /admin/vendors/{vendor}/purchase-orders` in `web.php` and implemented the paginated actions in `VendorController` and `VendorOrderController`, ensuring both endpoints maintain strict response parity (loading creator/vendor relations) and cap page limits between 1 and 100. Fully covered by 5 feature tests in `VendorOrderHistoryTest.php` including authorization gates, vendor isolation, query parameters filtering, bounded pagination, and deterministic descending order assertions. All tests and Pint checks pass.





## C5.1 Finance Payment And Balance Views

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C5.1.1 | Finance access boundary and sensitive-field policy | A2.3 | Gated access to payment/refund endpoints based on roles/permissions; sensitive columns (gateway fees, net amount, etc.) are hidden from unauthorized users | Permission and access restriction tests | Auth, Finance, Payments | High |
| C5.1.2 | Payment and refund ledger list | C5.1.1, C1.1 | Paginated ledger lists all payments and refunds; respects user visibility permissions | Ledger listing/access tests | Finance, Payments | Medium |
| C5.1.3 | Order payment detail and balance panel | C5.1.1, C1.1 | Detail page for order shows payment list, refund list, and outstanding balance summary | Order payment detail tests | Finance, Payments, Orders | Medium |
| C5.1.4 | Shared balance calculation presentation | A5.1.4 | Computes outstanding balance correctly (total - paid + refunded) using the shared calculator and displays it | Balance presentation tests | Finance, Payments, Orders | Medium |
| C5.1.5 | Finance filters, totals, and pagination | C5.1.2 | Ledger supports filtering by date range, provider, method, status, and type with correct totals | Filter and aggregate tests | Finance, Payments | Medium |
| C5.1.6 | Finance authorization and calculation regression tests | C5.1.1-C5.1.5 | Complete regression test suite verifying all access rules and calculations | Finance regression tests | Finance, Payments, Auth | High |

Verification note: C5.1.1 completed on 2026-06-25. Enforced finance access boundary and sensitive-field policies. Created PaymentPolicy, RefundPolicy, PaymentResource, and RefundResource. Registered policies in AppServiceProvider. Appended controller routes for listing and displaying payments and refunds. Ensured internal primary database keys and foreign keys are omitted from serialization. Conditionally loaded sensitive gateway fee and net amount fields using $this->when() based on finance.view_cost permissions. Verified via FinanceBoundaryTest.php (5 tests, 64 assertions) and FinanceAccessPolicyTest.php (3 tests, 26 assertions).

Verification note: C5.1.2 completed on 2026-06-25. Implemented paginated ledger lists for all payments and refunds. Exposes JSON payloads through PaymentResource and RefundResource, protecting database keys and conditionally loading sensitive details. Integrated eager loading (`with('order')` and `with(['order', 'payment'])`) on query builders in PaymentController and RefundController to prevent N+1 queries. Verified via FinanceBoundaryTest.php (which covers collection visibility, key omission, and resource mapping).

Verification note: C5.1.3 completed on 2026-06-25. Implemented paid amount, refunded amount, and outstanding balance summary for order details using only succeeded payments and refunds. Outstanding balance is clamped to zero. Fail-loud LogicExceptions are thrown if required top-level or nested relationships are not eager loaded. Eager loading is optimized at the controller level. Model-level boot event validates the domain invariant that succeeded refunds must have a valid recorded payment. Verified via AdminOrderDetailTest.php (8 tests, 81 assertions) and full test suite run (400 tests passed).

Verification note: C5.1.4 completed on 2026-06-25. Refactored balance calculations in OrderDetailCatalog to delegate arithmetic to the shared OrderTotalsCalculator. Used constructor injection with fallback resolution for backward compatibility. Verified via AdminOrderDetailTest.php and all 400 tests passing.

Verification note: C5.1.5 completed on 2026-06-26. Implemented filtering, aggregates, and pagination for finance ledger. Supported filtering payments and refunds by date range, provider, method (payments only), status, and type. Added validation rules via form requests. Implemented page aggregates (totals for amount, fees, net) dynamically calculated from the filtered dataset. Hidden fee/net aggregates for unauthorized staff. Verified via FinanceLedgerFilterTest.php (6 tests, 79 assertions).

Verification note: C5.1.6 completed on 2026-06-26. Verified full regression coverage for finance ledger and balance calculations. Confirmed role-based visibility policies, endpoint protection gates, response structure filtering, and aggregate constraints. Ran and verified all tests passing without regressions.

## C5.2 Refund Management

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C5.2.1 | Refund request | A5.2, C5.1 | Refund request can be created against valid payment/order | Refund request tests | Finance, Orders | Medium |
| C5.2.2 | Refund approval | C5.2.1, A2.3 | Only authorized roles approve refunds | Permission tests | Finance, Auth | High |
| C5.2.3 | Partial refund | C5.2.2, A5.2.3 | Partial refund updates refund totals correctly | Partial refund tests | Payments, Finance | High |
| C5.2.4 | Full refund | C5.2.2, A5.2.4 | Full refund updates payment/order financial state correctly | Full refund tests | Payments, Finance | High |
| C5.2.5 | Refund payment record | C5.2.3/C5.2.4 | Refund is recorded without deleting original payment | Record integrity tests | Payments, Finance | High |
| C5.2.6 | Payment-status recalculation | C5.2.5 | Refunded/partially refunded states calculate correctly | Status calculation tests | Payments, Orders | High |
| C5.2.7 | Refund audit events | A4.6, C5.2.1-C5.2.6 | Refund lifecycle emits audit events; permanent storage is verified by C6.1 integration | Audit-event tests | Finance, Audit | High |

Verification note: C5.2.1 completed on 2026-06-26. Implemented refund request creation under `POST /admin/refunds` with a dedicated `refunds.request` permission and model scope validation inside locked transaction. Checked bounds for full and partial refund types, verified that failed/cancelled refunds release reserved balance, and verified audit events. Tested via RefundRequestTest.php.

Verification note: C5.2.2 completed on 2026-06-26. Implemented refund approval workflow under `POST /admin/refunds/{refund}/approve` gated by the `refunds.approve` permission. Extracted state transition predicates/mutators (`canBeApproved()`, `ensureCanBeApproved()`, `approve()`) directly to the model. Handled concurrency locking on the refund record prior to mutation. Emitted `refunds.refund_approved` audit event. Tested via RefundApprovalTest.php.

Verification note: C5.2.3 completed on 2026-06-26. Implemented partial refund transitions, controller endpoints for processing and cancelling, policies, and webhook integrations with row locking and idempotency verification. Dispatched explicit audit events and recalculated order balances correctly. Tested via PartialRefundTest.php.

Verification note: C5.2.4 completed on 2026-06-26. Implemented full refund transitions, controller processing, and webhook integrations under locked transactions. Verified that full refund validation rejects amounts not equal to the remaining refundable balance, and that it correctly reduces net paid amount to 0, dynamically updating payment status to refunded. Tested via PartialRefundTest.php.

Verification note: C5.2.5 completed on 2026-06-26. Implemented explicit self-validating model methods `ensurePaymentIsRefundable()` (asserting `payment_id` presence, resolving payment relation, and checking `succeeded` status) and `ensurePaymentAssociationIsImmutable(?int $newPaymentId)` on the `Refund` model. Added invariants class documentation. Wrote feature tests verifying that parent payment records remain completely unchanged (immutable accounting snapshot comparison) during one or multiple refunds, and verifying that the relationship is append-only and ledger calculations dynamically resolve balance aggregates correctly. Tested via RefundPaymentRecordTest.php.

Verification note: C5.2.6 completed on 2026-06-26. Verified dynamic recalculations for unpaid, partially paid, paid, partially refunded, and refunded statuses using `PaymentStateRecalculationRules` unit tests and a dedicated integration test suite validating correct presentation and balance summaries across all payment status combinations. Tested via PaymentStatusRecalculationIntegrationTest.php.

Verification note: C5.2.7 completed on 2026-06-26. Implemented and verified the complete refund lifecycle audit trail. Added a dedicated feature integration test suite validating that all refund lifecycle transitions (requested, approved, processing, succeeded, failed, cancelled) correctly dispatch explicit audit events with safe payload formats. Eager-loaded relationships to prevent N+1 queries. Passed all test suites and resolved all static analysis check gates.

## C5.3 Expense Management

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C5.3.1 | Expense categories | C5.1 | Expense categories exist and can be managed by authorized users | Category tests | Finance | Low |
| C5.3.2 | Expense entry | C5.3.1 | Staff can enter expenses with amount/date/category | Expense CRUD tests | Finance | Medium |
| C5.3.3 | Expense approval rules | C5.3.2, A2.3 | Approval follows role permissions | Approval/permission tests | Finance, Auth | Medium |
| C5.3.4 | Expense permissions | A2.3, C5.3.2 | Restricted users cannot see protected expense data | Permission tests | Finance, Auth | High |
| C5.3.5 | Expense reporting data | C5.3.1-C5.3.4 | Expenses are available for finance reports | Report data tests | Finance, Reports | Medium |

Verification note: C5.3.1 completed on 2026-06-26. Implemented database schema, Eloquent model, seeders, policies, validation requests, and REST endpoints for business expense categories. Added static unique public_id generation, save-time domain code mutation blocking, seeder regressions, and soft-delete route binding 404 behavior. All 13 feature integration tests in ExpenseCategoryTest.php and all 479 global backend tests passed.

Verification note: C5.3.2 completed on 2026-06-26. Implemented database migration for expenses table with restrictOnDelete FKs, Expense model with immutable guards, validation requests excluding soft-deleted categories and restricting future-dated amounts, decimal string resources, and REST endpoints with deterministic sorting and N+1 query eager loading. All 14 feature integration tests in ExpenseTest.php and all 493 global backend tests passed.

Verification note: C5.3.3 completed on 2026-06-26. Implemented state transition rules, approval policies, transaction-locked action endpoints with row-level locks, form request validation including whitespace trimming, first-class approved_at timestamps, and versioned chronological transition history logs in metadata json. All 24 feature tests in ExpenseTest.php and all 503 global backend tests passed. Pint and PHPStan analyses are fully clean.

Verification note: C5.3.4 completed on 2026-06-26. Audited and confirmed all eight expense endpoint policy gates (viewAny, view, create, update, delete, submit, approve, reject). Extended ExpenseTest.php with comprehensive role-by-role permission matrix tests covering Admin, Finance Staff, Sales Staff, Inventory Staff, and Production Staff. Added existence leakage prevention tests verifying that unauthorized users always receive 403 (not 404) on valid public IDs. All 32 ExpenseTest tests and all 511 global backend tests passed. Pint and PHPStan analyses are fully clean.

Verification note: C5.3.5 completed on 2026-06-27. Implemented `ExpenseReportingService` supporting category and chronological monthly grouping (SQLite/MySQL query compatible). Validated filters (dates, status, categories) using a dedicated `ExpenseReportRequest` form request. Policy checks enforce role permissions via `viewExpenseReports`. Covered by 6 feature tests in `ExpenseReportingTest.php`. All tests passed, Pint formatted, and PHPStan clean.


## C6.1 Immutable Audit Log

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C6.1.1 | Audit table design | A1.1.8, A4.6 | Audit stores actor, action, old/new values, source, related records | Schema tests/review | Audit, Database | High |
| C6.1.2 | Order-change auditing | C6.1.1, A4.6, C1.1 | Order audit events create immutable audit records | Order audit tests | Orders, Audit | High |
| C6.1.3 | Payment/refund auditing | C6.1.1, A4.6, C5.2 | Payment/refund audit events create immutable audit records | Payment audit tests | Payments, Finance, Audit | High |
| C6.1.4 | Inventory auditing | C6.1.1, A4.6, C2.1 | Stock audit events create immutable audit records | Inventory audit tests | Inventory, Audit | High |
| C6.1.5 | Customer/product auditing | C6.1.1, A4.6, A3 | Customer/product audit events create immutable audit records | Data audit tests | Customers, Products, Audit | Medium |
| C6.1.6 | Permission-change auditing | C6.1.1, A4.6, A2.3 | Role/permission audit events create immutable audit records | Permission audit tests | Auth, Audit | High |
| C6.1.7 | Sensitive-data masking | C6.1.1 | Secrets, passwords, tokens, private contents are never logged | Masking/security tests | Audit, Security | High |
| C6.1.8 | Audit viewing permissions | C6.1.1, A2.3 | Only authorized roles can view audit logs | Permission tests | Audit, Auth | High |
| C6.1.9 | Retention rules | C6.1.1 | Retention policy is defined and enforceable | Retention tests/review | Audit, Jobs | Medium |

## C6.4 Backup, Security And Regression Gates

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C6.4.1 | Database backup | A1.1 | Backup routine and storage destination documented/tested | Backup test | Database, Deployment | High |
| C6.4.2 | Private-file backup | A4.1 | Private uploads are included in backup plan | File backup test | Files, Deployment | High |
| C6.4.3 | Restore procedure | C6.4.1, C6.4.2 | Restore steps are documented and rehearsed | Restore rehearsal | Database, Files, Deployment | High |
| C6.4.4 | Permission review | A2.3 | Role access matrix is reviewed before deploy | Permission regression tests | Auth, Admin | High |
| C6.4.5 | Upload security review | A4.1 | Upload risks are reviewed and tests pass | Upload security tests | Files, Security | High |
| C6.4.6 | Payment security review | A5.3, B3.3 | Webhook/payment secrets and verification are reviewed | Payment security tests | Payments, Security | High |
| C6.4.7 | API security review | A2, B APIs | Auth, rate limits, CORS, and data leaks are reviewed | API security tests | API, Security | High |
| C6.4.8 | Deployment checklist | A1.2 | Deployment steps, env vars, queues, scheduler are documented | Deployment dry-run | Deployment, All Modules | Medium |
| C6.4.9 | Regression test checklist | All parent tasks | Critical user/admin/payment/file paths are listed | Regression checklist run | All Modules | High |
| C6.4.10 | Rollback procedure | C6.4.8 | Rollback steps are documented and safe for migrations/files | Rollback review/test | Deployment, Database | High |



