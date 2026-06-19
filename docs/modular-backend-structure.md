# Modular Backend Structure Plan

Task: A1.3 Modular backend structure

Status: Planning draft

## Scope

This document defines the Laravel modular monolith backend structure for the Okina Craft Business System.

It does not scaffold Laravel, install packages, create module files, create APIs, create migrations, or configure Filament. It only defines the structure that later implementation should follow.

## Design Goals

- Keep one Laravel application and one shared database.
- Organize business logic by domain, not by delivery channel.
- Keep module ownership clear so code does not drift into a single giant app folder.
- Share only genuinely cross-cutting logic.
- Keep Filament, API controllers, jobs, policies, listeners, requests, and tests aligned with the owning module.
- Support later A1.5, A2, A3, A4, A5, and C1-C6 implementation without forcing a second backend.
- Preserve support for queues, private storage, signed access, idempotency, audit, notifications, and reporting.

## Recommended Backend Tree

Use the `apps/backend` location from the scaffold plan, then organize the Laravel app as a modular monolith:

```text
apps/backend/
  app/
    Modules/
      CRM/
      Customers/
      Products/
      Cart/
      Orders/
      Payments/
      Inventory/
      Vendors/
      Finance/
      Shipping/
      Files/
      Notifications/
      GoogleSheets/
      Tracking/
      Settings/
    Support/
    Filament/
  bootstrap/
  config/
  database/
  public/
  resources/
  routes/
  storage/
  tests/
```

This keeps the backend as one Laravel app while giving each business area a clear home.

## Module Responsibilities

| Module | Owns | Notes |
|---|---|---|
| `CRM` | Leads, activities, follow-ups, quotations, revisions, conversion orchestration | Own the lead-to-quotation pipeline and staff follow-up workflow. |
| `Customers` | Customer records, addresses, customer snapshots, duplicate-detection helpers | Shared by website, admin, CRM, quotations, and orders. |
| `Products` | Categories, products, variants, SKUs, public catalog truth, product availability rules | Own product/SKU truth for both website and admin. |
| `Cart` | Cart state, cart items, cart validation, checkout staging | Keep cart behavior separate from order creation. |
| `Orders` | Order headers, order items, status history, order snapshots, conversion target from quotations | Shared order truth for website and sales orders. |
| `Payments` | Payment attempts, payments, refunds, webhook logs, payment-state calculation support | Own payment truth and gateway-facing records. |
| `Inventory` | Stock balances, movements, reservations, low-stock alerts, stock lookup helpers | Must support traceable stock history and idempotent adjustments. |
| `Vendors` | Vendor profiles, purchase orders, purchase items, receiving, vendor payment tracking | Keep procurement and supplier history together. |
| `Finance` | Protected cost/profit views, balances, expense tracking, finance reports | Keep cost/profit visibility permissioned. |
| `Shipping` | Courier details, shipment records, tracking numbers, delivery dates, status timeline | Keep shipping out of order core status logic. |
| `Files` | File metadata, private storage references, file links, signed access grants | Store metadata only; actual bytes stay private. |
| `Notifications` | Templates, logs, delivery attempts, retries, dedupe keys | Must not block core business writes. |
| `GoogleSheets` | Backup sync jobs and Google Sheets integration records | Backup/reporting only, not source of truth. |
| `Tracking` | Customer-safe tracking timelines and public tracking helpers | Build customer-facing tracking views from backend truth. |
| `Settings` | Business settings, payment settings, notification settings, upload settings, SEO settings | Centralized configuration for shared runtime behavior. |

Authentication and permissions are shared platform concerns. Their exact package or folder placement can be finalized in A2, but the module structure here should remain compatible with that later work.

## Module Folder Pattern

Each module should own its code in a consistent shape:

```text
app/Modules/<Module>/
  Actions/
  Data/
  Events/
  Filament/
    Resources/
    Pages/
    Widgets/
  Http/
    Controllers/
    Requests/
  Jobs/
  Listeners/
  Models/
  Policies/
  Providers/
  Services/
  Tests/
  Database/
    Migrations/
    Seeders/
```

Use only the folders each module actually needs. Do not create empty folders just to mirror the pattern.

### What Goes Where

- `Models/`: Eloquent models and relationships owned by the module.
- `Actions/`: One-off domain actions and transactional workflows.
- `Services/`: Reusable module business logic that is still module-owned.
- `Policies/`: Authorization rules for that module’s records.
- `Jobs/`: Queueable work, retries, and external integrations.
- `Listeners/`: Event consumers that react to module or cross-module events.
- `Http/Controllers/`: API and web controllers for the module.
- `Http/Requests/`: Validation objects for module endpoints/forms.
- `Filament/Resources/`: Module-owned admin resources, pages, and widgets.
- `Database/Migrations/`: Module-owned schema changes, if migrations are split by module.
- `Database/Seeders/`: Module seeders only when they are truly module-specific.
- `Tests/`: Module-specific unit and feature tests.

## Shared Support Layer

Keep cross-cutting technical helpers in a small shared layer instead of repeating them in every module.

Suggested shared support folders:

```text
app/Support/
  Actions/
  Audit/
  Contracts/
  Data/
  Enums/
  Exceptions/
  Files/
  Http/
  Idempotency/
  Jobs/
  Money/
  Notifications/
  Responses/
  Traits/
  Validation/
  ValueObjects/
```

### Shared Support Responsibilities

| Support Area | Purpose | Should Not Own |
|---|---|---|
| `Responses` | Standard API response and error shape | Domain rules |
| `Validation` | Shared validation helpers and error normalization | Business validation logic that belongs to a module |
| `Idempotency` | Duplicate-prevention keys and replay helpers | Module-specific state machines |
| `Money` | Amount/currency helpers and value objects | Pricing policy decisions |
| `Audit` | Audit event shaping and masking helpers | Permanent audit storage |
| `Files` | Signed access helpers, file policy helpers, safe metadata helpers | Actual file bytes |
| `Notifications` | Event-to-notification dispatch helpers | Template content ownership |
| `Jobs` | Retry helpers and queue conventions | Individual job business logic |
| `Http` | Shared request/response middleware helpers | Module controllers |
| `Contracts` | Shared interfaces used by several modules | Concrete module logic |

Keep shared support small. If code clearly belongs to one module, keep it in that module.

## Filament Placement Strategy

Filament is an admin UI concern, but the ownership still belongs to the business module.

Recommended pattern:

```text
app/Modules/<Module>/Filament/Resources/
app/Modules/<Module>/Filament/Pages/
app/Modules/<Module>/Filament/Widgets/
```

Use the root `app/Filament/` directory only for truly global admin pages or dashboards that do not belong to one module.

## Route Loading Strategy

Keep top-level Laravel route files as entry points, then let each module load its own routes.

Suggested pattern:

```text
routes/
  api.php
  web.php
  admin.php
app/Modules/<Module>/Routes/
  api.php
  web.php
  admin.php
```

Module service providers can register their own route files, policies, and bindings.

## Module Boundary Rules

- One module owns one domain.
- Cross-module reads are allowed through services, query objects, or read models when needed.
- Cross-module writes should be intentional and transactional.
- Do not duplicate customer, product, order, payment, file, or notification rules in multiple modules.
- Cart should validate against Products, Customers, and shared order/payment rules, but it should not become a second order module.
- Payments should not own order lifecycle truth.
- Inventory should not own payment or quotation truth.
- Vendors should not own stock balances.
- Notifications should not own source business data.
- GoogleSheets should only mirror records after the core save succeeds.

## Cross-Module Service Placement

| Shared Concern | Preferred Home | Why |
|---|---|---|
| Public IDs | `app/Support/Idempotency` or a dedicated support service area | Shared generator/formatting logic should not live in one business module. |
| Money and amounts | `app/Support/Money` | Currency/amount helpers are used by many modules. |
| API response shape | `app/Support/Responses` | Keeps API output consistent. |
| Validation normalization | `app/Support/Validation` | Avoids duplicated error formatting. |
| Audit event shaping | `app/Support/Audit` | Shared masking and event-envelope logic. |
| Private file access | `app/Support/Files` | Used by Files, Orders, CRM, and customer upload flows. |
| Notification dispatch helpers | `app/Support/Notifications` | Shared dispatch and dedupe conventions. |
| Queue/retry helpers | `app/Support/Jobs` | Shared job policy without centralizing domain rules. |
| Settings resolution | `app/Modules/Settings` with a small `Support` facade if needed | Settings are a real business domain, not just infrastructure. |

## Module Sequencing Notes

This structure matches the approved build order:

1. Shared backend foundation and scaffold.
2. Shared catalog, customers, and order/payment core.
3. Inventory, vendors, CRM, and quotations.
4. Files, notifications, audit, and hardening.

The folder structure should make it easy to add modules without reworking the whole backend.

## Notes For Future Work

- A1.4 should verify environment and hosting readiness before the real scaffold begins.
- A1.5 can create the real Laravel/Astro scaffold without changing the module map.
- A4 can add platform services such as file upload and settings without changing the module map.
- A2 can finalize the authentication and permissions home without rewriting business modules.
- A3 can add model/service/action code inside `Customers`, `Products`, and related modules.
- A4 can add queue, audit, notification, and file infrastructure inside shared support and the owning modules.
- A5 can keep order and payment rules inside `Orders`, `Payments`, `Cart`, and `Finance`.
- C1-C6 can layer admin workflows, reports, and automation onto the same backend structure.

## Review Checklist

### Module Boundary Review

- Each core business area has a clear module.
- Cart, Orders, Payments, Inventory, Vendors, CRM, Finance, Shipping, Files, Notifications, Tracking, and Settings are all represented.
- Shared support is small and technical, not a second business layer.

Result: Pass.

### Shared Service Placement Review

- Shared helpers live in `app/Support`.
- Module-specific logic stays inside the owning module.
- Cross-cutting concerns do not overwhelm the domain folders.

Result: Pass.

### Filament Placement Review

- Module-owned Filament resources live under the module.
- Global admin UI can remain at root only when truly shared.
- Admin UI remains aligned with domain ownership.

Result: Pass.

### Queue and Job Placement Review

- Jobs live with the module that owns the work.
- Retry and background processing remain compatible with shared support conventions.
- External integrations stay behind jobs where possible.

Result: Pass.

### Policy, Request, and Test Placement Review

- Policies stay close to the owning module.
- Requests stay with the owning module’s controllers.
- Tests are organized by module so domain behavior is easier to find.

Result: Pass.

## Open Decisions for Future Tasks

- Whether module migrations should live under each module or remain in a central `database/migrations` tree with module naming conventions.
- Whether `app/Filament` should hold only global dashboards or also a few shared admin resources.
- Whether `app/Support` should be split further into smaller technical subtrees after implementation begins.
- Whether authentication/permission internals should eventually get their own `Identity` module or remain in shared platform support.
