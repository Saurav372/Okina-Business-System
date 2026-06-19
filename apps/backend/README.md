# Okina Craft Backend

This is the Laravel backend for the Okina Craft Business System.

## Local Commands

```powershell
php artisan about
php artisan test
php artisan serve --host=127.0.0.1 --port=8000
```

Use the workspace Composer when dependency commands are needed:

```powershell
php ../../tools/composer/composer.phar install
```

## Environment

- `.env.example` contains safe placeholders for the planned MySQL setup.
- `.env` is local-only and ignored by Git.
- The current local `.env` may use SQLite for baseline scaffold checks.
- Production hosting and production credentials are not approved in this scaffold task.

## Scope

This scaffold is only the shared Laravel backend skeleton. Admin authentication, customer authentication, catalog, checkout, payments, inventory, CRM, uploads, notifications, audit, and deployment work belong to later tasks.
