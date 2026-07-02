# Okina Craft Business System - Production Deployment Runbook & Checklist

This document details the step-by-step instructions, environment requirements, security practices, and verification steps necessary to successfully deploy and operate the Okina Craft Business System in a production environment.

---

## 1. System Prerequisites

The hosting environment must meet or exceed the following specifications:

- **PHP**: Version `8.3` or `8.4` (CLI and FPM/WebServer thread)
  - Required Extensions: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `PDO`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `gd`, `intl`, `sodium`, and `zip`.
  - PHP settings in `php.ini`:
    - `upload_max_filesize = 5M` (minimum required to accommodate design uploads)
    - `post_max_size = 10M`
    - `memory_limit = 256M` (minimum recommended for background queues/PDF processing)
- **Database**: MySQL Server `8.0+` or MariaDB `10.5+`
- **Node.js**: Version `22.x` (LTS) & `npm` (for building the Astro frontend)
- **Process Manager**: Supervisor or equivalent (to monitor queue workers)
- **Scheduler**: System Cron (to trigger Laravel scheduler)
- **SSL Certificate**: Valid HTTPS certificate for the root domain and all api/admin subdomains (e.g., Let's Encrypt).

---

## 2. Secrets Handling & Operational Security

- **Never Commit `.env`**: The `.env` file must never be committed to repository control. It must be generated directly on the server with secure file permissions (`600` or `640`).
- **Secret Key Uniqueness**: Run `php artisan key:generate --show` to create a unique `APP_KEY` for production. Do not copy keys from local or staging environments.
- **Service Account Principle**: Database, Google Sheets, and Cashfree service accounts must use restricted access controls (e.g., read-only scopes where appropriate, restricted sheet sharing access, and IP-whitelisted API keys).
- **Secrets Rotation**: In the event of credentials compromise, immediately rotate the `APP_KEY`, database passwords, Google private keys, and Cashfree signature secrets. Note that rotating the `APP_KEY` will invalidate any active cookies/sessions.

---

## 3. Environment Variables Matrix (`.env` Configuration)

Configure these variables in the server's `.env` file:

### Core Configuration
| Key | Example Value | Description |
|---|---|---|
| `APP_NAME` | `Okina Business System` | Application Name |
| `APP_ENV` | `production` | Application Environment |
| `APP_KEY` | `base64:....` | 32-byte encryption key |
| `APP_DEBUG` | `false` | Must be `false` in production to prevent exposing stack traces |
| `APP_URL` | `https://okinacraft.com` | Root application URL |
| `FRONTEND_URL` | `https://okinacraft.com` | Frontend URL for CORS mapping |

### Database
| Key | Example Value | Description |
|---|---|---|
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` | Database server address |
| `DB_PORT` | `3306` | Database server port |
| `DB_DATABASE` | `okina_production` | Database name |
| `DB_USERNAME` | `okina_db_user` | Database username |
| `DB_PASSWORD` | `[SecurePassword]` | Database password |

### Filesystems & Storage
| Key | Example Value | Description |
|---|---|---|
| `FILESYSTEM_DISK` | `private` | Default file upload disk |
| `SYSTEM_BACKUP_DISK` | `private` | Backup save disk |
| `SYSTEM_BACKUP_KEEP_COPIES` | `5` | Number of backup ZIPs to retain |

### Google Sheets Synchronization
| Key | Example Value | Description |
|---|---|---|
| `SHEETS_ENABLED` | `true` | Enables background synchronization pipelines |
| `SHEETS_SPREADSHEET_ID` | `1a2b3c...` | Target Google Spreadsheet ID |
| `SHEETS_CREDENTIALS` | `{"type": "service_account", ...}` | Complete Service Account JSON credentials |

### Cashfree Payment Gateway
| Key | Example Value | Description |
|---|---|---|
| `CASHFREE_API_ENV` | `production` | Production payment gateway environment |
| `CASHFREE_APP_ID` | `CF_APP_123` | Cashfree Application Identifier |
| `CASHFREE_SECRET_KEY` | `cf_sec_abc...` | Cashfree Client Secret Key |

### Mail configuration
| Key | Example Value | Description |
|---|---|---|
| `MAIL_MAILER` | `smtp` | Mail driver connection |
| `MAIL_HOST` | `smtp.mailgun.org` | Outgoing SMTP host |
| `MAIL_PORT` | `587` | SMTP port |
| `MAIL_USERNAME` | `postmaster@domain` | SMTP user |
| `MAIL_PASSWORD` | `[MailPassword]` | SMTP password |
| `MAIL_ENCRYPTION` | `tls` | SMTP encryption protocol |

---

## 4. Step-by-Step Deployment Runbook

Perform these steps sequentially when deploying a new release:

### Step 4.1: Enable Maintenance Mode
Place the application in maintenance mode to block incoming requests and display a user-friendly notice while upgrading database schemas and clearing caches:
```bash
php artisan down --secret="deploy-bypass-token" --refresh=15
```
*(You can bypass the maintenance gate by visiting `https://okinacraft.com/deploy-bypass-token` in your browser).*

### Step 4.2: Backup Active State
Before running database migrations, execute a system backup to capture the database SQL structure and active upload directories.
```bash
php artisan system:backup
```
Verify that the backup ZIP file is created successfully in the backups storage directory.

### Step 4.3: Update Source Code & Install Dependencies
Pull the latest source changes and install Composer dependencies with production optimizations:
```bash
git pull origin master
composer install --no-dev --optimize-autoloader
```

### Step 4.4: Execute Database Migrations
Run the database migration scripts:
```bash
php artisan migrate --force
```
*(The `--force` option is required to run migrations in production without prompting).*

### Step 4.5: Configure Optimizations & Cache Compilation
Clear old caches and re-compile performance caches:
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
```

### Step 4.6: Symlink Public Storage
Ensure public files are linked to the web directory (required for mockup image access):
```bash
php artisan storage:link
```

### Step 4.7: Configure Write Permissions
Verify that the web server user (e.g., `www-data` or `nginx`) has read/write permissions for directories where Laravel compiles runtime data:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Step 4.8: Restart Queue Workers
Restart background workers to ensure Supervisor loads the newly deployed PHP codebase:
```bash
php artisan queue:restart
```

### Step 4.9: Restart PHP OPcache
Clear cached PHP bytecode to reload PHP file modifications:
- For **PHP-FPM** (recommended): `sudo systemctl restart php8.3-fpm` (or the active PHP version service).
- For **FrankenPHP/RoadRunner**: Restart the process daemon.
- For **Apache**: `sudo systemctl reload apache2`.

### Step 4.10: Build and Deploy Frontend
Install node dependencies and build the Astro static/SSR frontend assets:
```bash
cd ../frontend
npm ci
npm run build
```

### Step 4.11: Disable Maintenance Mode
Once everything is verified, disable the maintenance gate:
```bash
cd ../backend
php artisan up
```

---

## 5. Deployment Health Checks & Verification Matrix

After bringing the application online, run the following operational check sequence to verify health:

1. **Application Reachability**: Verify `https://okinacraft.com/up` returns HTTP `200` with the status `"ok"`.
2. **Database Integrity**: Access the admin panel and load the dashboard. Verify that no database exceptions are logged.
3. **Queue Health**: Check running background queue worker daemons (e.g., `supervisorctl status`). Run `php artisan queue:failed` to ensure there are no unhandled failed jobs.
4. **Cron Scheduler Verification**: Confirm that the system cron is triggering the schedule. Run `php artisan schedule:list` to ensure scheduled commands are visible and active.
5. **Logs Cleanliness**: Run `tail -n 100 storage/logs/laravel.log` to check for any critical errors or warnings.
6. **Storage Writable Check**: Upload a small test image on a product mockup page as a customer. Verify that the file registers in the `stored_files` database table and saves on the private storage disk.
7. **Mail Verification**: Trigger a password reset email request. Check the mail server logs or sandbox to verify delivery.
8. **Google Sheets Sync Pipeline**: Modify a lead or record that triggers synchronization. Check the logs database (`google_sheets_sync_logs` table or the admin sync log page) to verify it records status `success` and maps the payload correctly.
9. **Cashfree Signature Verification**: Trigger a simulated signature validation payload using an incorrect signature header to the webhook endpoint `/api/webhooks/payments/cashfree`. Verify that the endpoint returns `401 Unauthorized` and records the attempt inside the `payment_webhook_logs` table.

---

## 6. Rollback Procedure

If the deployment fails or serious regressions are discovered, execute this recovery runbook:

1. **Enable Maintenance Mode**:
   ```bash
   php artisan down
   ```
2. **Restore Previous Source Code Release**:
   Checkout the previous stable git commit or rollback the symlink to the previous release folder.
3. **Restore Database Backup**:
   Run the restore utility using the backup ZIP generated prior to the deployment (Step 4.2):
   ```bash
   php artisan system:restore --force
   ```
4. **Clear Caches**:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   php artisan config:clear
   php artisan route:clear
   ```
5. **Restart Queue Workers**:
   ```bash
   php artisan queue:restart
   ```
6. **Restart PHP-FPM / OPcache**:
   ```bash
   sudo systemctl restart php8.3-fpm
   ```
7. **Disable Maintenance Mode**:
   ```bash
   php artisan up
   ```
8. **Run Deployment Health Checks**: Execute the post-deployment verification checks in Section 5 to confirm the system has successfully returned to its previous stable state.
