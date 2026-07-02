# Architecture Decisions

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** This document (ADRs are historical records — they describe why, not what)

---

## What Is an ADR?

An Architecture Decision Record (ADR) captures a significant technical decision: the context that made it necessary, the decision itself, and the consequences — both positive and the trade-offs accepted.

ADRs are **historical records**. Once accepted, they are not rewritten. If a decision is reversed, a new ADR is added.

---

## ADR-01 — Astro Instead of React/Next.js for the Customer Frontend

**Status:** Accepted

**Context:**
The customer website is primarily a content and product catalog site with a relatively simple checkout flow. Heavy JavaScript framework overhead was not justified. SEO performance and page load speed are business requirements.

**Decision:**
Use Astro as the customer frontend framework. Astro produces static or server-rendered HTML with minimal JavaScript shipped to the browser. Interactive islands (cart, checkout forms) use targeted client-side components only where needed.

**Consequences:**
- Excellent Core Web Vitals and SEO out of the box.
- Frontend developers need to understand Astro's island architecture.
- Dynamic interactions require explicit `.client` directives — less implicit than React.
- The admin panel uses Laravel's server-rendered approach, so no React-heavy UI framework is needed anywhere.

---

## ADR-02 — Laravel Monolith Instead of Node.js or Microservices

**Status:** Accepted

**Context:**
The system needed to handle orders, payments, inventory, CRM, finance, audit, and notifications. A microservices architecture would require inter-service communication, distributed transactions, and separate deployment pipelines — all unnecessary complexity for a single-business application at this scale.

**Decision:**
Use a single Laravel application (modular monolith) as the backend. Modules are organized by domain (`Orders`, `Payments`, `Inventory`, etc.) but share one process, one database connection pool, and one deployment unit.

**Consequences:**
- Simple local development and deployment.
- Database transactions span module boundaries naturally.
- Module boundaries are enforced by convention, not by process isolation.
- Scaling options are vertical first; horizontal scaling would require stateless session storage and shared file storage.

---

## ADR-03 — Integer Minor-Unit Money Storage

**Status:** Accepted

**Context:**
Decimal floating-point arithmetic is unsafe for currency calculations. Storing `150.50` as a `DECIMAL` column avoids floats but requires all arithmetic to remain in the database layer or use `bcmath`. A simpler and more portable approach is needed.

**Decision:**
Store all monetary amounts as integers in **minor units** (paise for INR). `₹150.00` is stored as `15000`. Formatting to a decimal string for display (`"150.00"`) is performed by the presentation layer (API Resource or controller).

**Consequences:**
- Integer arithmetic is exact — no floating-point rounding errors.
- All comparisons and aggregations in SQL are safe.
- The presentation layer must always convert before displaying amounts to users.
- A consistent naming convention is enforced: columns end with `_minor` (e.g. `amount_minor`, `fee_minor`).

---

## ADR-04 — Queue-Based Processing Instead of Synchronous External Calls

**Status:** Accepted

**Context:**
Several business actions trigger external side-effects: sending notifications (email/SMS/WhatsApp), syncing records to Google Sheets, processing payment webhook retries, and creating backup snapshots. If these calls were made synchronously inside the web request, a slow or failed external service would delay or roll back the core database transaction.

**Decision:**
All external integrations are dispatched as queued jobs **after the database transaction commits** (`DB::afterCommit()`). The core save always completes first. The job worker handles the external call independently, with retry on failure and logging of outcomes.

**Consequences:**
- Core business writes are never blocked or rolled back by external service failures.
- Notification delivery, Sheets sync, and backup operations are resilient to transient failures.
- Developers must be aware that side-effects are eventually consistent — a notification may be sent seconds after the triggering event.
- A running queue worker is **required** in production. The `sync` driver processes jobs inline and is only suitable for development.
- Failed jobs must be monitored (see [Maintenance Guide](./13_Maintenance_Guide.md)).

---

## ADR-05 — Google Sheets as Backup Mirror, Not Source of Truth

**Status:** Accepted

**Context:**
The business wanted to use Google Sheets for financial reporting and as a familiar interface for reviewing data. Two options were considered: bidirectional sync (Sheets as a data entry point) or unidirectional mirror (Sheets as a read-only reporting view).

**Decision:**
Google Sheets is a **read-only backup mirror**. Records are pushed from the database to Sheets — never pulled from Sheets into the database. The database is always the source of truth.

**Consequences:**
- Eliminates conflict resolution complexity.
- Sheets data may lag behind the database by the time required for the sync job to process.
- Business users cannot use Sheets to edit operational data — all changes must go through the admin panel.
- Sync failures are logged and retryable without affecting core operations.

---

## ADR-06 — Cashfree Behind a Replaceable Gateway Interface

**Status:** Accepted

**Context:**
Payment gateway requirements can change (pricing, reliability, regulatory compliance). Hardcoding Cashfree into checkout logic would make switching gateways expensive.

**Decision:**
Checkout logic never calls Cashfree directly. A `WebsitePaymentInitiationService` abstracts gateway-specific details. The webhook handler uses a `PaymentWebhookProcessingService` that validates signatures and updates payment state without knowing which gateway sent the webhook.

**Consequences:**
- Switching to a different gateway requires updating the service and webhook handler — checkout logic is unchanged.
- More abstraction to understand for new developers.
- The current integration is Cashfree only; the interface is designed to accommodate a replacement.

---

## ADR-07 — Modular Monolith Folder Structure

**Status:** Accepted

**Context:**
A flat `app/` directory with no domain organisation becomes difficult to navigate as the codebase grows. True microservices were rejected (see ADR-02). A middle path was needed.

**Decision:**
The backend uses a modular monolith structure. Business logic is organised by domain inside `app/` (using conventional Laravel directories). Domain boundaries are enforced by naming conventions and code review — not by process or package isolation.

**Consequences:**
- Domain ownership is clear from the folder structure.
- No framework magic or autoloader complexity.
- Cross-module access is unrestricted by the framework — discipline is required to respect boundaries.

---

## ADR-08 — Immutable Append-Only Audit Log

**Status:** Accepted

**Context:**
Business and compliance requirements demand a tamper-evident record of sensitive changes (order mutations, payments, refunds, role changes, inventory adjustments). A mutable log table provides no guarantee of integrity.

**Decision:**
Audit logs are written once and never updated or deleted. The `AuditLog` Eloquent model boots immutability checks that throw `LogicException` on any attempt to `save()`, `update()`, or `delete()` an existing record. Retention pruning deletes records only via the database query builder, bypassing the model observer — this is intentional and documented.

**Consequences:**
- Audit records can be trusted as complete historical records.
- Audit tables grow indefinitely without a pruning strategy — `audit:prune` handles configurable retention.
- Sensitive fields (passwords, tokens, card numbers) are masked at the listener layer before the record is written.
