# Maintenance Guide

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering / Operations
> **Source of Truth:** `docs/DEPLOYMENT-CHECKLIST.md`, `docs/ROLLBACK-PROCEDURE.md`, `docs/REGRESSION-TEST-CHECKLIST.md`

---

> 📋 This guide covers daily operations and monitoring. Full runbooks for specific procedures are in the linked documents — **do not duplicate content from them here.**

---

## Linked Operational Documents

| Document | When to Use |
|---|---|
| [DEPLOYMENT-CHECKLIST.md](./DEPLOYMENT-CHECKLIST.md) | Deploying a new release to production |
| [ROLLBACK-PROCEDURE.md](./ROLLBACK-PROCEDURE.md) | Rolling back a failed deployment or database change |
| [REGRESSION-TEST-CHECKLIST.md](./REGRESSION-TEST-CHECKLIST.md) | Verifying a release before or after deployment |

---

## Daily Operations Checklist

Run through the following checks at the start of each working day in production:

| Check | How to Verify |
|---|---|
| Queue worker is running | Check supervisor status or process list |
| No failed jobs accumulating | `php artisan queue:failed` — should be zero or stable |
| Scheduler is firing | Check `storage/logs/laravel.log` for scheduler entries |
| Application is responding | `GET /admin/login` returns 200 |
| Webhook endpoint is reachable | `POST /api/webhooks/payments/cashfree` returns 200 or 401 (not 500) |

---

## Queue Worker Monitoring

Failed jobs are stored in the `failed_jobs` table. Monitor regularly:

```powershell
# View failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Flush (delete) all failed jobs (use with caution)
php artisan queue:flush
```

If the same job fails repeatedly, investigate the exception in the `failed_jobs.exception` column and resolve the root cause before retrying.

---

## Log Monitoring

Laravel logs are written to `storage/logs/laravel.log`. In production, rotate logs regularly and watch for:

- `ERROR` level entries (application exceptions)
- `queue.failed` entries (job failures)
- Slow query warnings (if query logging is enabled)

Log rotation can be configured via `config/logging.php` using the `daily` driver, which creates one log file per day and retains them for a configurable number of days.

---

## Backup

### Manual backup
```powershell
cd apps/backend
php artisan system:backup
```

This produces a ZIP archive containing the SQL dump and any private file snapshots. Archives are stored at the path defined in `config/backup.php`.

### Scheduled backup

Backup is scheduled via `routes/console.php`. Verify cron is running correctly (see [Deployment Guide](./11_Deployment_Guide.md)).

### Verifying a backup

Before relying on a backup for restoration, verify its integrity:

```powershell
php artisan system:restore --dry-run /path/to/backup.zip
```

Full restore procedure: → [ROLLBACK-PROCEDURE.md](./ROLLBACK-PROCEDURE.md)

---

## Audit Log Pruning

Audit logs accumulate indefinitely. The `audit:prune` command deletes logs older than the configured retention period.

```powershell
php artisan audit:prune
```

Retention is configured in `config/audit.php` (`retention_days`). This command is scheduled to run daily.

---

## Google Sheets Sync Log Pruning

Sync logs are pruned automatically on a schedule. To run manually:

```powershell
php artisan sheets:prune-logs
```

---

## Dependency Updates

When updating PHP or NPM dependencies:

1. Run `composer update` or `npm update` in the appropriate directory.
2. Run the full test suite: `php artisan test`.
3. Run Pint: `./vendor/bin/pint --test`.
4. Run PHPStan: `./vendor/bin/phpstan analyse`.
5. Update the version table in [05_Technology_Stack.md](./05_Technology_Stack.md).
6. Deploy using the standard procedure in [DEPLOYMENT-CHECKLIST.md](./DEPLOYMENT-CHECKLIST.md).

---

## Performance Monitoring

Watch for the following in production:

| Symptom | Likely Cause | Action |
|---|---|---|
| Slow admin pages | N+1 queries | Check eager loading in controllers |
| Queue backlog growing | External service slow or down | Check notification/Sheets adapter health |
| High memory on queue worker | Large job payload | Check payload size and chunking |
| Audit table growing very large | Retention not configured | Verify `audit:prune` is scheduled and `AUDIT_RETENTION_DAYS` is set |

---

## Security Maintenance

- **Rotate secrets regularly:** `APP_KEY`, Cashfree secret, notification adapter credentials.
- **Review failed login attempts:** Check auth logs for brute-force patterns.
- **Keep dependencies updated:** Run `composer audit` to check for known vulnerabilities.
- **HTTPS certificate renewal:** Ensure SSL certificates are renewed before expiry.
