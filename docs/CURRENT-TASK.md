# Current Task

## Current Parent Task

C6.4 Backup, security, and regression gates

## Current Subtask

C6.4.5 Rollback procedure

## Current Status

Completed. Parent Task C6.4 is fully completed. All regression gates and deployment files are finalized.

## Goal

Create a comprehensive and production-ready Rollback Procedure runbook (`docs/ROLLBACK-PROCEDURE.md`). The document details how to reverse deployments safely, restore previous code versions and database states synchronously, handle migrations rollback, rebuild caches, reset queues, and verify health after a rollback event.

## Dependencies

- C6.4.3 — Deployment checklist (Completed)

## Required Deliverables

1. **Rollback Procedure Runbook**:
   - `docs/ROLLBACK-PROCEDURE.md` containing detailed rollback strategies, database/filesystem state synchronization guidelines, step-by-step restoration commands (using the custom backup restore tools), cache management, queue resets, and post-rollback health checks.

## Acceptance Criteria

- **Synchronized State Guidelines**: Explains when database restoration is required versus when standard git/migration rollback is sufficient to prevent data mismatch.
- **Actionable Restoration Commands**: Specific, copy-pasteable commands for checking out stable code commits, executing migration rollbacks (`php artisan migrate:rollback`), restoring database backups using the custom `system:restore` command, clearing performance caches, and restarting queue workers.
- **Post-Rollback Health Checks**: Steps to verify the system has returned to a completely stable previous state.

## Tests Required

- **Rollback Runbook Verification** (Manual document review).

## Quality Requirements

- Correct Markdown formatting.
- Thorough and clear structure.

## Files Likely Affected

- `docs/ROLLBACK-PROCEDURE.md` (new)
