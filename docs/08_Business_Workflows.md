# Business Workflows

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** `apps/backend/app/Services/*`, `apps/backend/app/Http/Controllers/*`

---

## 1. Business Lifecycle Overview

The high-level journey from first customer contact to completed order:

```mermaid
flowchart TD
    L["Lead Captured\n(Website enquiry or manual entry)"]
    Q["Quotation Created\n(by Sales Staff)"]
    A["Customer Approval\n(via approval link)"]
    SO["Sales Order Created"]
    AP["Advance Payment Recorded"]
    P["In Production"]
    D["Dispatched\n(Courier details entered)"]
    C["Completed\n(Delivered)"]

    L --> Q --> A --> SO --> AP --> P --> D --> C
```

For website orders (retail customers), the flow is shorter:

```mermaid
flowchart LR
    Cart --> Checkout --> Order["Pending Order"] --> Payment --> Confirmed --> Production --> Shipped --> Delivered
```

---

## 2. Website Checkout Flow

```mermaid
sequenceDiagram
    participant CU as Customer
    participant FE as Astro Frontend
    participant BE as Laravel Backend
    participant CF as Cashfree
    participant QW as Queue Worker

    CU->>FE: Fills cart, clicks Checkout
    FE->>BE: POST /api/cart/checkout
    BE->>BE: Validate cart items, quantities, price recalculation
    BE->>BE: Check quantity < 25 (else → bulk enquiry response)
    BE->>BE: Create pending Order (status: pending_payment)
    BE->>BE: Create PaymentAttempt
    BE->>CF: Initiate payment session
    CF-->>BE: gateway_order_id, payment_session_id
    BE-->>FE: 200 OK (order_ref, payment_session_id)
    FE->>CF: Customer completes payment on Cashfree
    CF->>BE: POST /api/webhooks/payments/cashfree
    BE->>BE: Verify HMAC signature
    BE->>BE: Idempotency check (prevent duplicate processing)
    BE->>BE: Create Payment record, update order payment_status
    BE->>QW: dispatch(InventoryDeductionJob) [afterCommit]
    BE->>QW: dispatch(SendNotificationJob) [afterCommit]
    BE->>QW: dispatch(SyncRecordToGoogleSheetsJob) [afterCommit]
    QW->>BE: Deduct inventory stock
    QW->>BE: Send notification (email/SMS/WhatsApp)
    QW->>BE: Sync order to Google Sheets
    BE->>BE: Write AuditLog (payment.recorded)
```

---

## 3. Payment Webhook Reconciliation

```mermaid
sequenceDiagram
    participant CF as Cashfree
    participant BE as Laravel Backend
    participant DB as Database

    CF->>BE: POST /api/webhooks/payments/cashfree
    BE->>BE: Verify X-Webhook-Signature (HMAC)
    alt Invalid signature
        BE-->>CF: 401 Unauthorized
    end
    BE->>DB: Look up PaymentAttempt by gateway_order_id
    BE->>DB: Check idempotency key (webhook_event_id)
    alt Already processed
        BE-->>CF: 200 OK (idempotent)
    end
    BE->>DB: BEGIN TRANSACTION
    BE->>DB: Create Payment record (amount, gateway_reference, status)
    BE->>DB: Recalculate order payment_status
    BE->>DB: Log PaymentWebhookLog
    BE->>DB: COMMIT
    BE->>BE: dispatch(AuditEvent) [afterCommit]
    BE->>BE: dispatch(SendNotificationJob) [afterCommit]
    BE-->>CF: 200 OK
```

---

## 4. Refund Workflow

```mermaid
sequenceDiagram
    participant FS as Finance Staff
    participant BE as Laravel Backend
    participant CF as Cashfree
    participant QW as Queue Worker

    FS->>BE: POST /admin/refunds (refund request)
    BE->>BE: Validate payment is refundable (succeeded status)
    BE->>BE: Validate amount does not exceed refundable balance
    BE->>BE: Create Refund record (status: requested)
    BE->>BE: dispatch(AuditEvent: refunds.refund_requested) [afterCommit]

    FS->>BE: POST /admin/refunds/{id}/approve
    BE->>BE: ensureCanBeApproved()
    BE->>BE: Update Refund status → approved
    BE->>BE: dispatch(AuditEvent: refunds.refund_approved) [afterCommit]

    BE->>CF: Initiate refund via gateway
    CF-->>BE: Refund reference
    BE->>BE: Update Refund status → succeeded
    BE->>BE: Recalculate Payment payment_status
    BE->>BE: Recalculate Order payment_status
    BE->>QW: dispatch(SendNotificationJob) [afterCommit]
    BE->>BE: dispatch(AuditEvent: refunds.refund_succeeded) [afterCommit]
```

---

## 5. Bulk Enquiry → Sales Order

```mermaid
sequenceDiagram
    participant CU as Customer
    participant FE as Astro Frontend
    participant SS as Sales Staff
    participant BE as Laravel Backend

    CU->>FE: Submits bulk enquiry (25+ items)
    FE->>BE: POST /api/cart/checkout (detected as bulk)
    BE-->>FE: 200 (bulk handoff response, no order created)
    FE->>CU: "Our team will contact you"

    SS->>BE: POST /admin/leads (create lead)
    SS->>BE: POST /admin/quotations (create quotation for lead)
    SS->>BE: POST /admin/quotations/{id}/send (email approval link)
    CU->>BE: GET /admin/quotations/{token}/approve (customer approves)
    BE->>BE: Update Quotation status → approved
    BE->>BE: Create Sales Order from Quotation
    SS->>BE: POST /admin/orders/{order}/payments (record advance payment)
    BE->>BE: Create Payment record
    BE->>BE: dispatch(AuditEvent) [afterCommit]
```

---

## 6. Stock-In from Purchase Order

```mermaid
sequenceDiagram
    participant IS as Inventory Staff
    participant BE as Laravel Backend
    participant DB as Database
    participant QW as Queue Worker

    IS->>BE: POST /admin/purchase-orders (create PO with vendor)
    IS->>BE: POST /admin/purchase-orders/{po}/items (add items with expected qty)
    IS->>BE: POST /admin/purchase-orders/{po}/status (mark as ordered)

    Note over IS,BE: Stock arrives from vendor

    IS->>BE: POST /admin/purchase-orders/{po}/items/{item}/receive
    BE->>DB: BEGIN TRANSACTION (lockForUpdate on PO and items)
    BE->>DB: Validate receivable quantity
    BE->>DB: Call InventoryBalanceService.stockIn()
    DB->>DB: Create InventoryMovement (reason: PURCHASE_RECEIPT)
    DB->>DB: Update InventoryItem balances
    DB->>DB: Update VendorOrderItem received_qty
    DB->>DB: Update VendorOrder status (partially_received / received)
    BE->>DB: COMMIT
    BE->>QW: dispatch(AuditEvent: inventory.stock_moved) [afterCommit]
    QW->>BE: Check low-stock threshold
    alt Stock below threshold
        QW->>QW: dispatch(LowStockDetected event)
    end
```

---

## 7. Notification Dispatch

```mermaid
sequenceDiagram
    participant BE as Backend (Core Save)
    participant DB as Database
    participant QW as Queue Worker
    participant NA as Notification Adapter
    participant NL as NotificationLog

    BE->>DB: Core domain write (order, payment, etc.)
    BE->>DB: DB::afterCommit() → dispatch(SendNotificationJob)
    QW->>DB: Look up NotificationTemplate by key + channel
    QW->>QW: NotificationRenderer.render(template, payload)
    QW->>DB: Update NotificationLog status → queued
    QW->>NA: Deliver via channel adapter (email/SMS/WhatsApp)
    alt Success
        QW->>DB: Update NotificationLog status → sent
        QW->>DB: Create NotificationDeliveryAttempt (success)
    else Failure (transient)
        QW->>DB: Update NotificationLog status → failed
        QW->>DB: Create NotificationDeliveryAttempt (failed)
        QW->>QW: Job retries with back-off
    end
```
