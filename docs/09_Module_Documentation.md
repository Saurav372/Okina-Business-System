# Module Documentation

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** `apps/backend/app/Models/*`, `apps/backend/app/Services/*`, `apps/backend/app/Policies/*`, `apps/backend/app/Jobs/*`, `apps/backend/app/Events/*`

---

## Module Overview

The backend is organized as a modular monolith. Each module owns a clearly defined set of domain objects.

| Module | Core Responsibility |
|---|---|
| [Auth / Users](#auth--users) | Staff authentication, roles, permissions |
| [Customers](#customers) | Customer records, accounts, addresses |
| [Products](#products) | Catalog, categories, variants, SKUs |
| [Cart](#cart) | Cart state, items, checkout validation |
| [Orders](#orders) | Order lifecycle, order items, status history |
| [Payments](#payments) | Payments, refunds, webhook reconciliation |
| [CRM](#crm) | Leads, activities, follow-ups, quotations |
| [Inventory](#inventory) | Stock balances, movements, low-stock detection |
| [Vendors](#vendors) | Vendor profiles, purchase orders, receiving |
| [Finance](#finance) | Expense tracking, financial reports |
| [Files](#files) | Private file storage and access |
| [Notifications](#notifications) | Templates, delivery, retry, deduplication |
| [Google Sheets](#google-sheets) | Backup mirror sync |
| [Audit](#audit) | Immutable audit log |
| [Settings](#settings) | Business configuration |

---

## Auth / Users

**Purpose:** Manage staff accounts, roles, and permission assignments for the admin panel.

**Models:** `User`, `Role`, `Permission`

**Services:** (built into Laravel RBAC pattern — no dedicated service class)

**Policies:** Enforced via middleware and `can()` checks in controllers

**Events:** `users.role_assigned`, `users.permission_updated`

**Jobs:** None

**Notifications:** None

**Public APIs:** `POST /admin/login`, `POST /admin/logout`

**Dependencies:**
- None (this module is a foundational dependency for all other modules)

---

## Customers

**Purpose:** Shared customer records used by website checkout, admin, CRM, and quotations.

**Models:** `Customer`, `CustomerAccount`, `CustomerAddress`

**Services:** (direct model operations; no dedicated service class)

**Policies:** `CustomerPolicy` — controls who can view or manage customer records

**Events:** `customers.customer_updated` (via `CustomerObserver`)

**Jobs:** None

**Notifications:** Order notifications are dispatched with customer data

**Public APIs:** `/api/auth/*`, `/api/customer/*`

**Dependencies:**
- Auth / Users — customer-facing routes use a separate customer guard

---

## Products

**Purpose:** The single source of truth for all product catalog data used by the website and admin.

**Models:** `Product`, `ProductCategory`, `ProductVariant`, `ProductSku`

**Services:** (catalog queries in controllers; customization via `CheckoutValidationService`)

**Policies:** `ProductPolicy`, `ProductCategoryPolicy`

**Events:** `products.product_updated`, `products.sku_updated` (via `ProductObserver`, `ProductSkuObserver`)

**Jobs:** `SyncRecordToGoogleSheetsJob` (on product/SKU save)

**Notifications:** None

**Public APIs:** `/api/catalog/products`, `/api/catalog/categories`, `/api/catalog/customization-options`

**Dependencies:**
- Inventory — `ProductSku` has a one-to-one with `InventoryItem`
- Orders — `OrderItem` snapshots from `ProductSku`

---

## Cart

**Purpose:** Session-based cart state and checkout staging for the customer website.

**Models:** `Cart`, `CartItem`

**Services:** `CartService`, `CartValidationService`, `CartPricingService`, `CartResponsePresenter`

**Policies:** Cart is customer-scoped (no explicit policy — controller enforces customer guard)

**Events:** None

**Jobs:** None

**Notifications:** None

**Public APIs:** `/api/cart/*`, `/api/cart/checkout`

**Dependencies:**
- Products — cart items reference `ProductSku`
- Customers — cart belongs to authenticated customer
- Orders — checkout creates an `Order`
- Payments — checkout creates a `PaymentAttempt`

---

## Orders

**Purpose:** The operational centre of the system — manages order lifecycle, status, shipping details, and design approval.

**Models:** `Order`, `OrderItem`

**Services:** `SalesOrderService`, `OrderTimelineService`

**Policies:** `OrderPolicy`

**Events:** `orders.order_created`, `orders.status_changed`, `orders.field_mutated`

**Jobs:** None (status transitions are synchronous; side-effects are queued by payment/notification modules)

**Notifications:** Order Created, Status Changed (dispatched via NotificationDispatcher)

**Public APIs:** `/admin/orders/*`, `/api/customer/orders/*`

**Dependencies:**
- Customers — orders belong to a customer
- Products — order items snapshot from `ProductSku`
- Payments — payment state is calculated from `Payment` records

---

## Payments

**Purpose:** All financial transaction records. Processes incoming webhooks, records payments, manages refunds, and recalculates payment state.

**Models:** `Payment`, `PaymentAttempt`, `PaymentWebhookLog`, `Refund`

**Services:** `PaymentWebhookProcessingService`, `WebsitePaymentInitiationService`

**Policies:** `PaymentPolicy`, `RefundPolicy`

**Events:** `payments.payment_recorded`, `refunds.refund_requested`, `refunds.refund_approved`, `refunds.refund_succeeded`

**Jobs:** None (webhook processing is synchronous; side-effects queued via afterCommit)

**Notifications:** Payment Received, Refund Processed

**Public APIs:** `/admin/payments/*`, `/admin/refunds/*`, `/api/webhooks/payments/cashfree`

**Dependencies:**
- Orders — payments are associated with an order
- Audit — all payment events emit audit events

---

## CRM

**Purpose:** Lead capture, staff assignment, follow-up management, quotation creation, and conversion to sales orders.

**Models:** `Lead`, `LeadActivity`, `LeadFollowUp`, `Quotation`, `QuotationItem`, `QuotationRevision`, `QuotationApprovalEvent`

**Services:** (inline controller logic; no separate service class)

**Policies:** `LeadPolicy`, `QuotationPolicy`

**Events:** None (CRM actions emit audit events directly)

**Jobs:** `SyncRecordToGoogleSheetsJob`

**Notifications:** Quotation Sent, Quotation Approved

**Public APIs:** `/admin/leads/*`, `/admin/quotations/*`, `/admin/quotations/{token}/approve` (public link)

**Dependencies:**
- Customers — leads convert to customer records
- Orders — approved quotations create sales orders

---

## Inventory

**Purpose:** Track SKU stock availability, record all stock movements, detect low-stock conditions, and enforce movement idempotency.

**Models:** `InventoryItem`, `InventoryMovement`

**Services:** `InventoryBalanceService`

**Policies:** (inventory endpoints gated by permission middleware, no separate policy class)

**Events:** `inventory.stock_moved`, `LowStockDetected`

**Jobs:** `SyncRecordToGoogleSheetsJob`

**Notifications:** Low Stock Warning

**Public APIs:** `/admin/inventory/*`

**Dependencies:**
- Products — `InventoryItem` is linked one-to-one to `ProductSku`
- Orders — `deductOrderStock()` is called after checkout payment confirmation
- Vendors — `stockIn()` is called when purchase order items are received

---

## Vendors

**Purpose:** Manage vendor profiles, purchase orders, stock receiving, and vendor payment tracking.

**Models:** `Vendor`, `VendorOrder`, `VendorOrderItem`, `VendorPayment`

**Services:** (inline controller logic)

**Policies:** `VendorPolicy`, `VendorOrderPolicy`, `VendorOrderItemPolicy`

**Events:** Audit events emitted on create/update/receive

**Jobs:** None

**Notifications:** None

**Public APIs:** `/admin/vendors/*`, `/admin/purchase-orders/*`

**Dependencies:**
- Inventory — receiving stock calls `InventoryBalanceService.stockIn()`
- Audit — all vendor/purchase changes emit audit events

---

## Finance

**Purpose:** Expense tracking with categories and approval workflow. Financial report generation aggregating payments, refunds, expenses, and outstanding balances.

**Models:** `Expense`, `ExpenseCategory`

**Services:** `ExpenseReportingService`, `FinanceReportService`

**Policies:** `ExpensePolicy`, `ExpenseCategoryPolicy`

**Events:** Audit events on expense state changes

**Jobs:** None

**Notifications:** None

**Public APIs:** `/admin/expenses/*`, `/admin/finance/report`

**Dependencies:**
- Payments — finance reports aggregate payment and refund data
- Orders — outstanding balance calculation uses active order amounts
- Audit — expense approval actions emit audit events

---

## Files

**Purpose:** Secure private file storage for uploaded customer design files and internal documents.

**Models:** `StoredFile`

**Services:** `FileUploadService`

**Policies:** `StoredFilePolicy`

**Events:** None

**Jobs:** None

**Notifications:** None

**Public APIs:** (signed URL generation in admin and customer controllers)

**Dependencies:**
- Orders — design files are associated with order customization
- Customers — uploaded files belong to a customer context

---

## Notifications

**Purpose:** Template-based notification delivery across email, SMS, WhatsApp, and in-app database channels. Deduplication, retry, and delivery logging.

**Models:** `NotificationTemplate`, `NotificationLog`, `NotificationDeliveryAttempt`

**Services:** `NotificationEventCatalog`, `QueueDispatchDeduplicator`, `QueueFailureLogger`

**Policies:** `NotificationLogPolicy`

**Events:** Listens to domain events to trigger dispatch

**Jobs:** `SendNotificationJob`

**Notifications:** (this module is the notification system itself)

**Public APIs:** `/admin/notification-logs/*`

**Dependencies:**
- All modules — notifications are triggered by events from every business domain
- Queue — `SendNotificationJob` must run in a persistent queue worker

---

## Google Sheets

**Purpose:** Backup mirror of key business records (orders, payments, inventory, customers, leads, vendors). One-way sync only — Sheets is never the source of truth.

**Models:** `GoogleSheetsSyncLog`

**Services:** `GoogleSheetsPayloadMapper`

**Policies:** `GoogleSheetsSyncLogPolicy`

**Events:** Observed via `GoogleSheetsSyncObserver` (fires on model saved events)

**Jobs:** `SyncRecordToGoogleSheetsJob`

**Notifications:** None

**Public APIs:** `/admin/google-sheets/test-connection`

**Dependencies:**
- All entity modules — observer fires on save of 7 entity types
- Queue — sync jobs must run in a persistent queue worker
- Settings — Google Sheets credentials and enabled flag are read from settings/config

---

## Audit

**Purpose:** Immutable, append-only record of all sensitive business changes. Masking of sensitive fields. Retention policy enforcement.

**Models:** `AuditLog`, `AuditLogRelatedRecord`

**Services:** `AuditPayloadPolicy` (masking), `AuditEventCatalog`

**Policies:** `AuditLogPolicy`

**Events:** Listens to `AuditEvent` — a shared contract emitted by all business modules

**Jobs:** None (written synchronously by `AuditEventListener` inside the web request)

**Notifications:** None

**Public APIs:** `/admin/audit-logs/*`

**Dependencies:**
- All modules — every business domain emits `AuditEvent`

---

## Settings

**Purpose:** Runtime business configuration. Payment gateway credentials, notification settings, upload limits, SEO values, and Google Sheets credentials.

**Models:** `Setting`

**Services:** `SettingsService`

**Policies:** (gated by `settings.manage` permission)

**Events:** None

**Jobs:** None

**Notifications:** None

**Public APIs:** `/admin/settings`

**Dependencies:**
- Notifications — notification adapters read provider credentials from settings
- Google Sheets — sheets credentials and enabled flag read from settings/config
- Files — upload size limits configurable via settings
