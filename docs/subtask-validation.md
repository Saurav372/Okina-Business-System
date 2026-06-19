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
| C1.2 Sales order creation | Complete only when sales staff can create, price, edit within rules, confirm, structure payments for sales orders, and emit required audit events. Final parent completion also requires C6.1 audit integration tests to pass when audit storage is implemented. |
| C1.3 Quotations and bulk-order conversion | Complete only when bulk enquiries become quotations, approved quotations become sales orders, and advance payments are recorded correctly. |
| C2.1 Inventory movements and stock handling | Complete only when stock balance, stock-in/out, adjustments, order deduction, cancellation reversal, warnings, history, and audit events work together. Final parent completion also requires C6.1 audit integration tests to pass when audit storage is implemented. |
| C2.2 Vendors and purchases | Complete only when vendor purchase orders can be created, received fully/partially, paid/tracked, and linked to stock history. |
| C5.2 Refund management | Complete only when refund records, approvals, partial/full refunds, payment recalculation, and audit trail work without deleting original payment history. |
| C5.3 Expense management | Complete only when expenses are categorized, permission-protected, approved where needed, and available for finance reports. |
| C6.1 Immutable audit log | Complete only when all sensitive change types are captured, masked, permission-protected, and retained according to policy. |
| C6.4 Backup, security, and regression gates | Complete only when backup, restore, security reviews, deployment checklist, regression checklist, and rollback procedure are documented and tested. |

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
| B2.2.8 | Admin file access | A4.1.5, B2.2.7 | Authorized admin can view/download related files | Admin permission tests | Admin, Files | High |

Verification note: B2.2.1 completed on 2026-06-19. The backend now accepts approved design uploads for public products, stores the original privately, returns public-safe file and preview metadata, and exposes a signed preview URL for the mockup step. `php artisan test --filter=DesignUploadFlowTest` passed.

Verification note: B2.2.2 completed on 2026-06-19. Invalid upload payloads are rejected before storage, and validation errors are returned for unsafe filenames and incompatible customization selections. `php artisan test --filter=DesignUploadFlowTest` passed.


Verification note: B2.2.3 completed on 2026-06-19. The preview flow now returns a public-safe SVG mockup for uploaded designs, rejects tampering and expired signed preview links, and keeps preview metadata free of raw storage paths. `php artisan test --filter=DesignUploadFlowTest` and `./vendor/bin/pint --test tests\Feature\DesignUploadFlowTest.php` passed.

Verification note: B2.2.4 completed on 2026-06-19. Placement adjustments now flow through a signed preview-link endpoint, clamp out-of-range values safely, and update the customer preview iframe through the Astro placement panel. `php artisan test --filter=DesignUploadFlowTest` and `npm run build` passed.

Verification note: B2.2.5 completed on 2026-06-19. Customization metadata now has a versioned public-safe snapshot shape with product/SKU selection, normalized placement, safe uploaded-file references, mockup preview metadata, and tests proving private storage paths are excluded from customer/cart/order-facing payloads. `php artisan test`, `./vendor/bin/pint --test app\\Http\\Controllers\\Api\\ProductCustomizationController.php app\\Support\\Products\\CustomizationSnapshotBuilder.php app\\Services\\FileUploadService.php app\\Services\\SettingsService.php tests\\Feature\\DesignUploadFlowTest.php tests\\Feature\\SettingsServiceTest.php`, and `npm run build` passed.

Verification note: B2.2.6 completed on 2026-06-19. The upload-flow customization snapshot is accepted by cart persistence, retained on cart items and reloads, and exposed through a public-safe cart payload without raw storage paths, preview paths, cart tokens, or internal database IDs. `php artisan test --filter=DesignUploadFlowTest`, `php artisan test --filter=CartStorageTest`, and `./vendor/bin/pint --test tests/Feature/DesignUploadFlowTest.php` passed.

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

## C5.3 Expense Management

| Subtask ID | Exact output/deliverable | Dependencies | Acceptance criteria | Tests required | Affected modules | Complexity |
|---|---|---|---|---|---|---|
| C5.3.1 | Expense categories | C5.1 | Expense categories exist and can be managed by authorized users | Category tests | Finance | Low |
| C5.3.2 | Expense entry | C5.3.1 | Staff can enter expenses with amount/date/category | Expense CRUD tests | Finance | Medium |
| C5.3.3 | Expense approval rules | C5.3.2, A2.3 | Approval follows role permissions | Approval/permission tests | Finance, Auth | Medium |
| C5.3.4 | Expense permissions | A2.3, C5.3.2 | Restricted users cannot see protected expense data | Permission tests | Finance, Auth | High |
| C5.3.5 | Expense reporting data | C5.3.1-C5.3.4 | Expenses are available for finance reports | Report data tests | Finance, Reports | Medium |

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



