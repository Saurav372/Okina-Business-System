# Okina Craft Main System Requirements

## System Purpose

Okina Craft Business System is one connected business platform for customer ecommerce, admin operations, payments, inventory, quotations, CRM, production, shipping, finance, reporting, and automation.

The system must not be treated as three independent applications. The customer website and business admin must share one backend domain, one database direction, and one set of business rules.

## System Hierarchy

```text
Okina Craft Business System
+-- Platform A: Shared Backend and Core Services
|   +-- A1 Architecture and Database
|   +-- A2 Authentication and Permissions
|   +-- A3 Shared Business Data
|   +-- A4 Platform Services
|   +-- A5 Shared Order and Payment Domain
+-- Project B: Customer Ecommerce Website
|   +-- B1 Catalog and Product Discovery
|   +-- B2 Product Customization and Mockup Preview
|   +-- B3 Cart, Checkout and Website Payment
|   +-- B4 Customer Account and Tracking
+-- Project C: Business Operations Admin
    +-- C1 Orders, Sales Orders and Quotations
    +-- C2 Inventory, Vendors and Purchases
    +-- C3 CRM and Staff Workflow
    +-- C4 Production and Shipping
    +-- C5 Finance and Reporting
    +-- C6 Automation, Audit and Hardening
```

## Required Build Order

1. Shared Backend and Core Services
2. Shared order and payment domain
3. Basic admin order and payment management
4. Website catalog and customization
5. Website cart, checkout, and payment
6. Inventory, production, and shipping workflow
7. Customer dashboard and tracking
8. CRM, finance, reports, automation, audit, and hardening

Project B and Project C must be developed in connected phases. Do not finish one as an isolated system before starting the other.

## Core Architecture Rules

- Use one Laravel backend as a modular monolith.
- Use one shared database.
- Use Astro for the customer-facing ecommerce website.
- Use Laravel Filament for the admin/business operations interface.
- Website and admin must use the same customers, products, SKUs, orders, payments, files, inventory, and quotation records.
- External integrations must not block core business actions.
- Database save happens before notifications, Google Sheets sync, courier sync, analytics sync, or other external work.
- Uploaded files must be stored in private storage, not in MySQL.
- Payment gateway logic must use a replaceable gateway service. Checkout must not hardcode Cashfree.

## Product State Rules

Product status and visibility are separate.

Product Status:

- Draft
- Active
- Out of Stock
- Bulk Only
- Discontinued

Visibility:

- Public
- Private

Public pages must show only allowed public products. Private products may still be usable by admin for manual sales orders.

## Order And Payment Rules

Order status is operational. Payment status is financial.

Payment states:

- Unpaid
- Partially Paid
- Paid
- Partially Refunded
- Refunded

Order statuses:

- Pending Payment
- Confirmed
- In Production
- Ready to Ship
- Shipped
- Delivered
- Cancelled
- Refunded

Payment status should be calculated from payment records. A fully paid order must not automatically become operationally Confirmed unless the approved business rule says so.

Design approval is order information, not a separate order status:

- Design Approved: Yes / No
- Approved At
- Approved By
- Design Notes

## Order And Quotation Types

Order types:

- Website Order
- Sales Order

Quotation types:

- Bulk Quotation
- Manual Quotation

Bulk workflow:

```text
Bulk Enquiry
-> Lead
-> Quotation
-> Customer Approval
-> Sales Order
-> Advance Payment
```

Do not create an official order number before quotation approval unless the business explicitly requires it.

## Checkout And Payment Flow

Website checkout must create a pending order before payment is completed.

```text
Checkout creates pending order
-> Payment attempt created
-> Gateway payment initiated
-> Webhook verifies payment
-> Payment record updated
-> Order payment state recalculated
-> Admin displays updated order
```

This prevents order loss when payment fails, the customer abandons checkout, or gateway callbacks are delayed.

## Basic Stock Availability Rules

C2 Inventory, Vendors and Purchases owns inventory rules in V1. Checkout must not be blocked by a complete inventory reservation system.

The inventory module must define:

- Whether the product is currently orderable
- Whether staff can accept an order without stock
- Whether inventory warnings should appear
- When purchased stock is added
- When consumed stock is deducted
- How made-to-order products behave
- How insufficient stock is handled
- Whether negative stock is permitted

Product/SKU data is required for checkout. Inventory availability can provide warnings, but a full reservation workflow is not a critical checkout dependency for the current buy-as-needed workflow.

## Idempotency Rules

Duplicate prevention is required for:

- Checkout submission
- Order creation
- Payment attempts
- Payment webhooks
- Inventory movements
- Notification sending
- Google Sheets sync
- Background job retries

Repeated requests or jobs must not create duplicate orders, duplicate payments, duplicate stock movements, or duplicate customer notifications.

## Notification Domain Rules

Notification events must define recipient, channel, trigger, retry behavior, deduplication rule, and template.

Required notification events include:

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

## Audit Log Rules

Audit logs are immutable business records.

- Staff cannot edit or delete audit records.
- Super Admin can view audit logs but cannot rewrite history.
- Sensitive fields must be masked.
- Passwords, access tokens, private payment credentials, and full card details must never be logged.
- Private file contents must never be stored in audit records.
- Audit records should store request/source information where appropriate.
- Retention duration must be defined before production launch.

Audit logging applies to order status changes, payment changes, refunds, stock movements, product/SKU changes, customer data changes, staff permission changes, and file download/delete actions.

## Change Impact Checklist

Before changing any task, check:

- Which projects are affected?
- Which APIs are affected?
- Which database tables are affected?
- Which screens and reports are affected?
- What tests must be repeated?
- Is data migration required?
- Is idempotency affected?
- Are audit logs affected?
- Are notification triggers affected?
- Is regression testing required?

## Completion Rule

A task is complete only when:

- Its own tests pass.
- Connected projects still work.
- APIs and shared data remain correct.
- Idempotency and duplicate prevention are checked where relevant.
- Audit and notification behavior are checked where relevant.
- Documentation is updated.
- Regression testing is completed.
