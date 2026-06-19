# Repository and App Scaffold Plan

Task: A1.2 Repository and app scaffold plan

Status: Planning draft

## Scope

This document defines the recommended repository layout, Laravel backend location, Astro frontend location, documentation/deployment folders, environment handling, local development entrypoints, and future build/boot checklist.

It does not scaffold Laravel, scaffold Astro, install dependencies, configure Filament, create CI, create Docker files, create `.env` secrets, or verify hosting capabilities.

## Recommended Repository Shape

Use one Git repository unless deployment constraints force two repositories later.

```text
okina-craft/
  apps/
    backend/
    frontend/
  docs/
  deployment/
  scripts/
  README.md
```

Purpose:

- `apps/backend/`: Laravel 13 backend, Filament admin, shared API, database migrations, queue/jobs, private file handling, and integrations.
- `apps/frontend/`: Astro + Tailwind public ecommerce website.
- `docs/`: planning, architecture, schema, API contract, deployment notes, and task tracking.
- `deployment/`: cPanel/VPS deployment guides, environment checklists, cron notes, rollback notes, and future server config examples.
- `scripts/`: optional local helper scripts after scaffolding, such as boot checks or deploy preparation. Keep empty or absent until useful.

Avoid creating separate disconnected backends for website, CRM, inventory, or finance. They are modules inside the one Laravel backend.

## Backend Location

Laravel should be scaffolded in:

```text
apps/backend/
```

Backend responsibilities:

- Shared MySQL database.
- Filament admin.
- Product, SKU, customer, order, payment, inventory, vendor, CRM, quotation, file, audit, notification, settings, and reporting modules.
- Public/customer APIs consumed by Astro.
- Checkout validation and order creation.
- Payment gateway interface and Cashfree adapter.
- Private file storage and signed access.
- Queue jobs, scheduler commands, retries, and external integration safety.

Initial backend structure after later scaffold work:

```text
apps/backend/
  app/
    Modules/
    Filament/
    Jobs/
    Services/
    Policies/
  database/
    migrations/
    seeders/
  routes/
    api.php
    web.php
  storage/
  tests/
  .env.example
```

`app/Modules/` is defined in detail by A1.3. Do not overbuild module folders during A1.2.

## Frontend Location

Astro should be scaffolded in:

```text
apps/frontend/
```

Frontend responsibilities:

- Public website pages.
- Product/category rendering from Laravel API data.
- Customization and upload UI.
- Cart and checkout UI.
- Customer account and tracking pages.
- Bulk enquiry UI.
- SEO pages, landing pages, insights/articles, and static marketing pages.
- Analytics event dispatch.

Initial frontend structure after later scaffold work:

```text
apps/frontend/
  src/
    pages/
    components/
    lib/
    styles/
  public/
  .env.example
```

Astro must not duplicate backend business rules. Price, stock/orderability, checkout, payment, customer, and file access rules come from Laravel.

## Documentation and Deployment Structure

Keep existing planning files in `docs/`.

Recommended future docs:

```text
docs/
  PROJECT-CONTEXT.md
  CURRENT-TASK.md
  task-list.md
  subtask-validation.md
  dependency-impact-register.md
  api-contract.md
  environment.md
  deployment-checklist.md
```

Recommended deployment folder:

```text
deployment/
  cpanel/
    frontend-static-deploy.md
    backend-laravel-deploy.md
    cron-and-queues.md
  vps/
    backend-vps-plan.md
    frontend-cdn-plan.md
  rollback.md
```

Do not store secrets in docs or deployment files.

## Environment Files

Backend:

- `apps/backend/.env.example`
- Should document database, app URL, admin URL, frontend URL, queue driver, mail settings, Cashfree placeholders, file disk, and signed URL settings.
- Real `.env` stays untracked.

Frontend:

- `apps/frontend/.env.example`
- Should document public API base URL and public analytics placeholders.
- Only expose variables safe for the browser.

Shared environment notes:

- Use `docs/environment.md` later for setup explanation.
- Keep actual credentials out of Git.
- Use separate values for local, staging, and production.

## Local Development Entry Points

Recommended default ports:

| App | Directory | Command | URL |
|---|---|---|---|
| Laravel backend | `apps/backend` | `php artisan serve --host=127.0.0.1 --port=8000` | `http://127.0.0.1:8000` |
| Astro frontend | `apps/frontend` | `npm run dev -- --host 127.0.0.1 --port 4321` | `http://127.0.0.1:4321` |

Recommended future helper commands:

```text
composer install
php artisan migrate
php artisan test
npm install
npm run dev
npm run build
```

Do not run these during this planning task. They are for the future scaffold implementation.

## Build and Boot Checklist

When A1.2 is implemented as real scaffolding later, the exit gate should be:

- Repository has `apps/backend`, `apps/frontend`, `docs`, and `deployment`.
- Laravel app exists in `apps/backend`.
- Astro app exists in `apps/frontend`.
- Backend `.env.example` exists and documents required placeholders.
- Frontend `.env.example` exists and documents public-safe variables.
- Backend boots locally.
- Frontend boots locally.
- Backend test command runs.
- Frontend build command runs.
- No secrets are committed.
- Generated dependency folders such as `vendor/` and `node_modules/` are ignored.
- Private upload storage is not under a public web root.

## Quality Commands for Future Scaffold Work

Backend checks after Laravel scaffold:

```text
php artisan test
./vendor/bin/pint --test
```

Add PHPStan only after it is installed/configured:

```text
./vendor/bin/phpstan analyse
```

Frontend checks after Astro scaffold:

```text
npm run build
```

Add lint/typecheck commands only after the frontend toolchain defines them.

## Hosting and Deployment Notes

Phase 1 hosting target:

- `okinacraft.com`: Astro static frontend.
- `admin.okinacraft.com`: Laravel + Filament backend/admin/API.
- MySQL via cPanel if compatible.

Before real scaffolding or deployment, confirm:

- PHP 8.3+ for Laravel 13.
- Composer availability.
- Required PHP extensions.
- MySQL/MariaDB version.
- Cron support for Laravel scheduler.
- Queue worker feasibility.
- File storage permissions and storage limit.
- Webhook accessibility for payment callbacks.
- Ability to keep uploaded originals outside the public web root.

If shared cPanel cannot support Laravel 13, queue jobs, uploads, or webhooks reliably, use VPS for backend from the beginning or consciously approve a different Laravel version. Do not split the domain model into separate apps to work around hosting.

Future VPS path:

- Astro may remain static/CDN-hosted.
- Laravel backend/admin/API can move to VPS.
- Database, queues, scheduler, uploads, backups, and webhooks can become more reliable on VPS.

## Review Checklist

### Repository Layout Review

- One Git repository is recommended.
- Frontend and backend are separated inside `apps/`.
- Docs and deployment files have clear homes.
- The structure does not create disconnected systems.

Result: Pass.

### Backend Location Review

- Laravel backend location is `apps/backend`.
- Backend remains the shared modular monolith.
- Filament admin and APIs live in the same Laravel app.

Result: Pass.

### Frontend Location Review

- Astro frontend location is `apps/frontend`.
- Frontend owns presentation and public UX.
- Backend remains the source of business truth.

Result: Pass.

### Deployment Structure Review

- Deployment notes belong in `deployment/`.
- Shared hosting and VPS paths are both supported.
- Hosting capabilities are not assumed without verification.

Result: Pass.

### Build and Boot Checklist Review

- Future scaffold exit checks are listed.
- Backend boot/test and frontend boot/build are covered.
- Secrets and dependency folders are excluded from planned tracked files.

Result: Pass.

## Open Decisions for Future Tasks

- Whether shared cPanel can run Laravel 13 with PHP 8.3+, Composer, required extensions, cron, queues, private storage, and webhooks.
- Whether the real scaffold should use plain Laravel folders or a Laravel module package.
- Whether local development uses simple native commands only or later adds Docker.
- Final production domains for API if separate from `admin.okinacraft.com`.
- Final CI provider and checks.
