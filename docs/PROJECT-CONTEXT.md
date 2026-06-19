# Okina Craft Project Context

Use this file as the regular context file for development sessions. It contains only stable project rules.

For task-specific instructions, use `docs/CURRENT-TASK.md`.

## AI Implementation Permission Gate

Default AI mode is inspection and review only.

Before any code-producing or build-producing work, the AI must confirm the active scope from `docs/CURRENT-TASK.md`, report blockers, and wait for explicit user approval to implement/build/fix.

Do not implement, scaffold, install dependencies, run migrations, or change application code when the user asks only to check, compare, review, verify, plan, or inspect.

Use the step prompts in `docs/prompt-steps/` for each workflow stage. Use `docs/prompt-steps/03-implement-current-subtask.md` only after the user explicitly approves implementation for the current unblocked subtask.

## Git Restore Point Rule

After every approved change batch, create a Git restore point before moving to the next task.

Required flow:

1. Run `git status --short` before edits and after edits.
2. Stage only files changed for the approved task. Do not stage unrelated user work.
3. Commit the task changes with a short message that includes the task ID when available.
4. If the repository has no baseline commit or most files are still untracked, stop and ask for approval before creating the initial baseline commit.
5. If commits are not possible, report exactly why and list the changed files so the restore-point gap is visible.

Do not use destructive Git commands such as reset, checkout, clean, or force-push unless explicitly requested.
## System Architecture

Okina Craft Business System is one connected platform, not separate disconnected apps.

Architecture:

- One Laravel backend as a modular monolith.
- One shared database.
- Astro frontend for the customer website.
- Laravel Filament admin for business operations.
- Website and admin must use the same products, SKUs, customers, orders, payments, files, quotations, inventory, and audit rules.
- External integrations must not block core business actions.

## Technology Stack

- Frontend: Astro + Tailwind CSS
- Backend: Laravel 13 + Filament
- Database: MySQL
- Payment: Cashfree first, through a replaceable gateway interface
- Hosting phase 1: shared cPanel if compatible
- Future hosting: VPS when needed for queues, webhooks, uploads, reporting, and scale

## Project Areas

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

## Current Build Phase

Current phase:

Foundation implementation is underway across the Laravel backend and Astro frontend.

Use `docs/CURRENT-TASK.md` as the source of truth for the active subtask, blockers, and allowed implementation scope. Do not start unrelated application implementation outside that active scope.

## Core Business Rules

- Products must support categories, variants, and SKUs.
- Every purchasable product combination must have a SKU.
- Product status and visibility are separate.
- Uploaded files must be stored privately, not in MySQL.
- Website checkout creates a pending order before payment starts.
- Payment records are separate from orders.
- Payment status is calculated from payment records.
- Cashfree must not be hardcoded into checkout logic.
- Quotations are not official orders until approved.
- Bulk flow: Bulk Enquiry -> Lead -> Quotation -> Customer Approval -> Sales Order -> Advance Payment.
- Inventory does not block checkout in V1; it provides availability warnings and admin stock handling.
- Staff-facing production/shipping workflow is simple in V1.
- Google Sheets is backup/reporting only, not the source of truth.

## Product State Rules

Product Status:

- Draft
- Active
- Out of Stock
- Bulk Only
- Discontinued

Visibility:

- Public
- Private

## Order And Payment Rules

Order statuses:

- Pending Payment
- Confirmed
- In Production
- Ready to Ship
- Shipped
- Delivered
- Cancelled
- Refunded

Payment states:

- Unpaid
- Partially Paid
- Paid
- Partially Refunded
- Refunded

Order status is operational. Payment status is financial.

Design approval is order information, not an order status:

- Design Approved: Yes / No
- Approved At
- Approved By
- Design Notes

## Roles And Permissions

Roles:

- Super Admin
- Admin
- Sales Staff
- Inventory Staff
- Finance Staff
- Production Staff

Rules:

- Staff permissions must be role-based.
- Finance and cost/profit data must be restricted.
- Staff cannot delete core records unless explicitly allowed.
- Customer accounts cannot access another customer's orders or files.
- Sensitive changes must emit audit events through the audit interface.

## Security And Reliability Rules

- Validate all user input.
- Validate file uploads by MIME type and extension.
- Use private storage for uploaded originals.
- Use signed URLs for file previews/downloads where needed.
- Never store passwords, tokens, payment credentials, full card details, or private file contents in logs or audit records.
- Checkout submission, order creation, payment webhooks, inventory movements, notifications, Google Sheets sync, and background jobs must be idempotent where relevant.
- External integrations should run through queues/jobs where possible.
- Failed integrations must be logged and retryable where safe.
- Audit logs are immutable once implemented.

## Code Quality And Efficiency Rules

- Follow established Laravel, Filament, Astro, and Tailwind conventions.
- Correctness and security take priority over micro-optimization.
- Avoid N+1 database queries.
- Eager load relationships when the access pattern requires them.
- Paginate, cursor, or chunk potentially large datasets.
- Do not load unnecessary records or columns.
- Avoid repeated database queries inside loops.
- Use database constraints and indexes for important integrity and lookup rules.
- Use transactions when multiple related database writes must succeed or fail together.
- Keep slow external integrations outside core database transactions.
- Use queues for slow or failure-prone external operations where appropriate.
- Avoid unnecessary service classes, repositories, design patterns, and abstractions.
- Do not add caching without a demonstrated need and a clear invalidation rule.
- Keep controllers and UI resources focused; place reusable business rules in appropriate domain or action classes.
- All code-producing tasks should run relevant tests.
- Laravel code should run Pint and static analysis when those tools are configured.
- Passing tests does not by itself prove performance, maintainability, or security.
- Do not describe code as optimized without measurable evidence.

## Reference Documents

Use these detailed documents only when needed:

- `docs/main-system-requirements.md`
- `docs/project-specifications.md`
- `docs/feature-list.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`

Read detailed documents when:

- A dependency is unclear.
- A change affects another module.
- A parent task is being completed.
- Regression testing is required.
- A business rule needs verification.
- The dependency impact of a change is uncertain.

For most coding sessions, use only:

1. `docs/PROJECT-CONTEXT.md`
2. `docs/CURRENT-TASK.md`
3. Existing code files related to the current task

Prompt templates and usage sequence are in:

- `docs/AI-PROMPT-SEQUENCE.md`

## Development Prompt Workflow

Use `docs/AI-PROMPT-SEQUENCE.md` for task inspection, implementation, quality review, regression review, and documentation updates.

Do not combine inspection and implementation for shared, sensitive, or high-complexity tasks.
