# Okina Business System

A fully integrated business platform for custom-apparel and print-on-demand operations.

**One Laravel application · One shared database · One public origin**

→ **[Full Documentation](./docs/00_INDEX.md)**

---

## Quick Start

### Application (Laravel + Blade)

```powershell
cd apps/backend
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm ci
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

### Running Tests

```powershell
cd apps/backend
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```

---

## Local URLs

| App | URL |
|---|---|
| Customer storefront | `http://127.0.0.1:8000` |
| Admin panel | `http://127.0.0.1:8000/admin` |
| JSON API | `http://127.0.0.1:8000/api` |

---

## Documentation

The full technical and system documentation is in [`docs/`](./docs/00_INDEX.md):

| Section | Documents |
|---|---|
| **Foundation** | Project Overview, Conventions, Architecture, ADRs, Tech Stack |
| **Development** | Database Design, API Docs, Workflows, Module Docs, Auth |
| **Operations** | Deployment, Testing, Maintenance, Checklists |
| **End User** | Staff Manual, Customer Manual |

---

## Tooling Notes

- Use `php tools/composer/composer.phar` for Composer commands in this workspace if a global Composer is not available.
- Keep real credentials out of source control — copy `.env.example` to `.env` and fill in values locally.
- The queue driver must be set to `database` in production — `sync` is for development only.
