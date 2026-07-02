# Project Overview

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** `Build docs/main-system-requirements.md`, `Build docs/feature-list.md`, `Build docs/PROJECT-CONTEXT.md`

---

## What Is Okina Business System?

Okina Business System is a fully integrated business platform for a custom-apparel and print-on-demand company. It combines a customer-facing ecommerce website with a complete back-office administration system — sharing one Laravel backend, one database, and one set of business rules.

It is **not** three separate applications. The customer website, the admin panel, and all integrations are one connected system.

---

## Business Problems Solved

| Problem | How the System Solves It |
|---|---|
| Manual order tracking in spreadsheets | Centralised order management with status tracking, payment records, and audit trail |
| No structured quotation process for bulk orders | CRM lead → quotation → customer approval → sales order workflow |
| Payment reconciliation done manually | Automated payment webhook processing with idempotent reconciliation |
| Inventory managed separately from orders | Inventory movements linked to orders, purchase orders, and stock adjustments |
| Customer designs emailed back and forth | Secure private file upload with admin design approval workflow |
| Finance data visible to all staff | Role-based access control with protected cost/profit visibility |
| No backup or audit history | Immutable audit logs and automated database/file backups |

---

## System Areas

```
Okina Business System
├── Platform A: Shared Backend and Core Services
│   ├── A1  Architecture and Database
│   ├── A2  Authentication and Permissions
│   ├── A3  Shared Business Data (Products, Customers)
│   ├── A4  Platform Services (Files, Queue, Settings, Notifications)
│   └── A5  Shared Order and Payment Domain
│
├── Project B: Customer Ecommerce Website
│   ├── B1  Catalog and Product Discovery
│   ├── B2  Product Customization and Mockup Preview
│   ├── B3  Cart, Checkout, and Website Payment
│   └── B4  Customer Account and Order Tracking
│
└── Project C: Business Operations Admin
    ├── C1  Orders, Sales Orders, and Quotations
    ├── C2  Inventory, Vendors, and Purchases
    ├── C3  CRM and Staff Workflow
    ├── C4  Production and Shipping
    ├── C5  Finance and Reporting
    └── C6  Automation, Audit, and Hardening
```

---

## Feature Summary

### Platform A — Shared Core
- Modular Laravel monolith with a single shared MySQL database
- Role-based authentication for staff (6 roles) and separate customer authentication
- Shared product catalog: categories, products, variants, SKUs
- Shared customer records and addresses
- Private file storage with signed access URLs
- Configurable business settings
- Queue-based job processing with retry and deduplication
- Immutable audit event contract shared across all modules

### Project B — Customer Website (Astro)
- Public product listing and detail pages with SEO support
- Product customization: size-wise quantity, print method, design file upload, mockup preview
- Cart supporting mixed customized items
- Checkout with backend price recalculation and quantity-based bulk detection (25+ → quotation flow)
- Cashfree payment gateway integration (behind a replaceable interface)
- Customer account, order history, and shipment tracking

### Project C — Admin Operations (Laravel)
- Full order management (website orders + manual sales orders)
- Quotation creation, revision, and customer approval workflow
- Bulk enquiry → lead → quotation → sales order pipeline
- CRM: lead capture, assignment, follow-up, and conversion
- Inventory: stock balances, movements, purchase orders, vendor management
- Finance: payment ledger, refund management, expense tracking, financial reports
- Notification system: templates, queued delivery, retry, deduplication
- Google Sheets backup sync (mirror only — not source of truth)
- Immutable audit log with sensitive-data masking and retention policy
- Backup and restore (`system:backup`, `system:restore`)
- Security review, deployment checklist, and regression gate

---

## V1 Scope Boundaries

The following are intentionally deferred from V1:

- Canva-style design editor (basic upload + preview only)
- Full inventory reservation blocking checkout (availability warnings only)
- External courier tracking API integration (manual entry only)
- Multi-currency support (INR only)
- Public API for third-party integrations

---

## Known Limitations

- Google Sheets sync uses a basic append/update model — it is a reporting mirror, not a two-way sync.
- Notification delivery depends on configured external adapters (email, SMS, WhatsApp); adapter credentials must be set in `.env`.
- Queue-based jobs require a persistent queue driver (database or Redis) in production — the `sync` driver processes jobs inline and is suitable for development only.

---

## Future Improvements

- Canva-style customization editor
- Automated courier tracking integration
- Advanced inventory reservation at checkout
- Analytics and sales dashboard
- Mobile-optimised admin interface
