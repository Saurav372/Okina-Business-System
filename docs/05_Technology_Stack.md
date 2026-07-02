# Technology Stack

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** `apps/backend/composer.json`, `apps/frontend/package.json`

---

> ⚠️ **Do not edit version numbers in this document manually.**
> This table is generated from `composer.json` and `package.json`.
> Regenerate it whenever dependencies are updated by running:
> ```powershell
> # Backend
> php tools/composer/composer.phar show --format=json | ConvertFrom-Json
> # Frontend
> cat apps/frontend/package.json
> ```

---

## Backend (Laravel)

**Source:** `apps/backend/composer.json`

### Production Dependencies

| Package | Constraint | Role |
|---|---|---|
| `php` | `^8.3` | Language runtime |
| `laravel/framework` | `^13.8` | Backend MVC framework |
| `laravel/tinker` | `^3.0` | Interactive REPL |
| `google/apiclient` | `*` | Google Sheets API integration |

### Development Dependencies

| Package | Constraint | Role |
|---|---|---|
| `fakerphp/faker` | `^1.23` | Fake data generation for tests |
| `laravel/pail` | `^1.2.5` | Log viewer |
| `laravel/pao` | `^1.0.6` | Dev tooling |
| `laravel/pint` | `^1.27` | Opinionated PHP code style fixer |
| `mockery/mockery` | `^1.6` | Mocking library for PHPUnit |
| `nunomaduro/collision` | `^8.6` | Better error reporting |
| `phpstan/phpstan` | `^2.2` | Static analysis |
| `phpunit/phpunit` | `^12.5.12` | Test runner |

### PHP Extensions Required

| Extension | Purpose |
|---|---|
| `pdo_mysql` | MySQL database driver |
| `pdo_sqlite` | SQLite (used in tests) |
| `mbstring` | Multi-byte string handling |
| `openssl` | Encryption and HTTPS |
| `tokenizer` | PHP source tokenization |
| `xml` | XML processing |
| `bcmath` | Arbitrary precision maths (currency safety) |
| `fileinfo` | File MIME type detection |
| `zip` | Backup archive creation |

---

## Frontend (Astro)

**Source:** `apps/frontend/package.json`

> Verify current versions from `apps/frontend/package.json` — regenerate this table on dependency updates.

| Package | Role |
|---|---|
| `astro` | Static site and island framework |
| `@astrojs/tailwind` | Tailwind CSS integration |
| `tailwindcss` | Utility-first CSS framework |

---

## Infrastructure

| Component | Technology | Notes |
|---|---|---|
| Database | MySQL | Primary data store; SQLite used in automated tests |
| Queue driver | `database` (production), `sync` (dev) | Jobs stored in `jobs` table in production |
| Cache driver | `file` (default) | Can be upgraded to Redis if needed |
| Session driver | `file` or `cookie` | Configured in `.env` |
| File storage | `local` (private disk) | `storage/app/private/`; upgradeable to S3-compatible |
| Scheduler | `php artisan schedule:run` via cron | Runs `audit:prune`, daily backup, etc. |

---

## Runtime Environment

| Requirement | Minimum |
|---|---|
| PHP | 8.3 |
| MySQL | 8.0 |
| Node.js | 18 (for Astro frontend build) |
| Composer | 2.x |
| NPM | 9+ |

---

## Key Configuration Files

| File | Purpose |
|---|---|
| `apps/backend/.env` | Environment-specific configuration (never committed) |
| `apps/backend/.env.example` | Safe template for all required env keys |
| `apps/backend/config/audit.php` | Audit log retention duration |
| `apps/backend/config/backup.php` | Backup storage path and retention |
| `apps/backend/config/sheets.php` | Google Sheets credentials and entity column maps |
| `apps/backend/phpstan.neon` | PHPStan analysis configuration |
| `apps/backend/pint.json` | Laravel Pint code style configuration |
