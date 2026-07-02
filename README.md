# Okina Business System

A fully integrated business platform for custom-apparel and print-on-demand operations.

**One Laravel backend · One Astro frontend · One shared database**

→ **[Full Documentation](./docs/00_INDEX.md)**

---

## Quick Start

### Backend (Laravel)

```powershell
cd apps/backend
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

### Frontend (Astro)

```powershell
cd apps/frontend
npm ci
npm run dev -- --host 127.0.0.1 --port 4321
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
| Laravel backend | `http://127.0.0.1:8000` |
| Astro frontend | `http://127.0.0.1:4321` |
| Admin panel | `http://127.0.0.1:8000/admin` |

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
