# Okina Craft Business System

Okina Craft Business System uses one Laravel backend and one Astro frontend.

## Local Apps

| App | Directory | Command | URL |
|---|---|---|---|
| Laravel backend | `apps/backend` | `php artisan serve --host=127.0.0.1 --port=8000` | `http://127.0.0.1:8000` |
| Astro frontend | `apps/frontend` | `npm run dev -- --host 127.0.0.1 --port 4321` | `http://127.0.0.1:4321` |

## Tooling Notes

- Use `php tools/composer/composer.phar` for Composer commands in this workspace.
- Local MySQL is planned at `127.0.0.1:3306`; `apps/backend/.env.example` contains safe MySQL placeholders.
- The live `apps/backend/.env` may use SQLite for baseline scaffold smoke checks until real local MySQL credentials are configured.
- Keep real credentials out of docs and source control.
- Production hosting is not approved yet; A1.5 only confirms the local scaffold.

## Baseline Checks

Backend:

```powershell
cd apps/backend
php artisan about
php artisan test
```

Frontend:

```powershell
cd apps/frontend
npm run build
```
