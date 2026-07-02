# Okina Craft Business System - Production Rollback Procedure

This runbook documents the official recovery procedures for reversing failed deployments in the production environment. It defines the immediate response workflow, rollback vs. roll-forward decision gates, step-by-step restoration scenarios, queue handling, integration checks, and incident closure rules.

---

## 1. Immediate Response: "Stop the Bleeding"

Before diagnosing the root cause or initiating code/database changes, operators must execute these first actions:

1. **Enable Maintenance Mode**: Immediately lock the site to prevent further client-facing errors or data corruption:
   ```bash
   php artisan down --refresh=15
   ```
2. **Pause Deployment Pipelines**: Stop any ongoing automated builds, releases, or staging syncs to lock the code state.
3. **Control Active Queue Workers**: 
   - If data integrity is at risk (e.g. incorrect payment logs or loops), stop queue processing immediately via Supervisor:
     ```bash
     sudo supervisorctl stop all
     ```
   - If data integrity is *not* at risk, allow active, in-flight jobs to drain normally.
4. **Preserve Log Files**: Copy active log directories to a temporary location to prevent overwrite:
   ```bash
   cp -r storage/logs/ /tmp/incident-logs-$(date +%F-%H%M)/
   ```
5. **Record Current Version**: Record the active git commit hash and database structure version currently running.

---

## 2. Roll Forward vs. Roll Back Guidelines

- **Prefer Rolling Forward**: If the defect is minor (e.g. a UI style error, typo, or minor logic bug), causes no data integrity risk, and can be fixed with a hotfix commit in under **15 minutes**, prefer rolling forward by pushing a hotfix deployment.
- **Roll Back Immediately**: If the issue is critical (e.g., checkout crashes, payment callbacks fail, data corruption occurs), cannot be resolved immediately, or blocks core system features, initiate a rollback.

---

## 3. Rollback Decision Matrix

Select the appropriate rollback scenario depending on the impact level of the failed release:

| Scenario | Migration Changes | Data Impact | Recovery Strategy |
|---|---|---|---|
| **Scenario 1: Code-Only** | None | None | Revert source code commit, restart FPM & queues, clear caches. |
| **Scenario 2: Reversible Migrations** | Schema changes present (but safe and fully reversible) | None | Rollback migrations via Artisan, revert code, restart FPM & queues, clear caches. |
| **Scenario 3: Full Application Recovery** | Destructive / non-reversible schema changes | Potential / actual data corruption | Enable maintenance mode, verify backup, restore code, execute `system:restore`, reload caches & queues. |

---

## 4. Rollback Scenarios

### Scenario 1: Code-Only Rollback
Use this runbook when the failed release introduced no new database migrations.

1. **Revert Git Codebase**: Roll back the local repository to the last stable release tag or commit hash:
   ```bash
   git checkout [STABLE_COMMIT_HASH]
   composer install --no-dev --optimize-autoloader
   ```
2. **Clear & Recompile Caches**:
   ```bash
   php artisan optimize:clear
   php artisan optimize
   php artisan config:cache
   php artisan route:cache
   php artisan event:cache
   php artisan view:cache
   ```
3. **Restart Queue Workers**: Notify Supervisor workers to reload the stable codebase:
   ```bash
   php artisan queue:restart
   ```
4. **Restart PHP-FPM / OPcache**: Reload cached bytecode in memory:
   ```bash
   sudo systemctl restart php8.3-fpm
   ```
5. **Build Frontend**: Re-build frontend Astro files if assets changed:
   ```bash
   cd ../frontend && npm ci && npm run build
   ```

---

### Scenario 2: Reversible Migrations Rollback
Use this runbook when migrations were run but can be safely reversed without data loss.

1. **Rollback Migrations**: Reverse the database changes to the state prior to the release:
   ```bash
   php artisan migrate:rollback --step=[NUMBER_OF_MIGRATIONS]
   ```
2. **Revert Git Codebase**: Roll back to the stable codebase commit (Step 1 of Scenario 1).
3. **Execute Caches & Restart Queue Workers**: Follow Steps 2-5 of Scenario 1.

---

### Scenario 3: Full Application Recovery
Use this runbook in the event of destructive database failures, corrupted schema updates, or failed migration rollbacks.

1. **Verify Backup Integrity**: Before performing the destructive restore command, verify that the backup ZIP file generated prior to deployment exists and is valid:
   - Identify the correct backup ZIP filename in `storage/app/private/backups/backup-YYYY-MM-DD-timestamp.zip`.
   - Verify that the ZIP is not empty and matches the pre-deployment timestamp.
2. **Revert Git Codebase**: Checkout the stable codebase commit:
   ```bash
   git checkout [STABLE_COMMIT_HASH]
   composer install --no-dev --optimize-autoloader
   ```
3. **Restore Backup**: Run the restore utility command with the `--force` option:
   ```bash
   php artisan system:restore [BACKUP_FILENAME.zip] --force
   ```
   *(Note: This utility automatically dry-run extracts, verifies the database SQL checksum in the manifest, iteratively drops active database tables, clears uploaded files, and reconstructs the pre-deployment state).*
4. **Execute Caches & Restart Queue Workers**: Follow Steps 2-5 of Scenario 1.
5. **Disable Maintenance Mode**:
   ```bash
   php artisan up
   ```

---

## 5. Queue Job Handling & External Integration Checks

After a rollback, external and asynchronous integrations must be audited to prevent synchronization gaps:

- **Pending / Failed Queue Jobs**:
  - Run `php artisan queue:failed` to inspect jobs that failed during the incident.
  - Review `google_sheets_sync_logs` to identify any failed row synchronization events.
  - Decide if sync logs should be retried manually or cleared if they conflict with the restored state.
- **Cashfree Webhooks**:
  - Audit Cashfree portal logs. If any webhooks failed to deliver while the system was down/failing, trigger manual webhook replays from the Cashfree dashboard.
- **Google Sheets Sync**:
  - Check the Google Spreadsheet. Verify that row updates match the restored database records exactly. Run manual retries on logs that were in `failed` status if necessary.
- **Mail Delivery**:
  - Review SMTP logs to verify no transactional emails are stuck or dropped.

---

## 6. Post-Rollback Operational Health Checks

Verify that the system has returned to a stable, healthy state by executing this checklist:

1. **App Reachability**: `/up` returns HTTP `200` `"ok"`.
2. **Authentication**: Admin panel and customer login portals allow successful authentication.
3. **Database Connectivity**: Database queries load resources cleanly without throwing exceptions.
4. **Queue Worker Health**: Daemons are running (`supervisorctl status`) and processing jobs.
5. **Cron Scheduler**: Tasks are running.
6. **Order Creation**: Customers can configure customizations and add to cart.
7. **Payment Callback Webhook**: Verified signature rejection checks are fully active.
8. **Google Sheets Connection**: Admin connection test page returns success.
9. **Clean Logs**: `storage/logs/laravel.log` shows no ongoing errors.

---

## 7. Incident Closure & Post-Mortem

Once the system is verified stable:

1. **Document the Incident**: Record the following in the operations log:
   - Root cause of the failure.
   - Rollback timeline (time of incident, time of rollback start, time of service restoration).
   - The restored application release version / git hash.
2. **Analyze Logs**: Perform a post-mortem review of the preserved incident logs `/tmp/incident-logs-*` to address the core code issue.
3. **Define Action Items**: Create development tasks to resolve the underlying bug, improve test coverage, and prevent recurrence.
