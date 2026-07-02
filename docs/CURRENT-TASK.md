# Current Task

## Current Parent Task

C6.4 Backup, security, and regression gates

## Current Subtask

C6.4.4 Regression test checklist

## Current Status

Not Started. C6.4.3 (Deployment checklist) is fully completed and verified. Ready to begin C6.4.4.

## Goal

Provide a clear, complete, and production-ready Deployment Checklist and runbook. Document all setup steps, environment variable configurations, queue and job setups, cron scheduler actions, and storage/permission rules to verify a successful deployment dry-run.

## Dependencies

- A1.2 — Host deployment configuration (Completed)

## Required Deliverables

1. **Deployment Checklist Document**:
   - `docs/DEPLOYMENT-CHECKLIST.md` containing absolute setup guidelines, environment variable matrices, system configurations (supervisor queues, system cron scheduler), database migrations, build targets, and verification steps.

## Acceptance Criteria

- **Comprehensive Configuration**: Detailed mappings for all essential environment variables (`APP_KEY`, database credentials, file storage paths, Google Sheets client configs, Cashfree settings).
- **Execution Runbook**: Step-by-step instructions from cloning the repository to starting queue workers, scheduling cron commands, building frontends, and initial backup testing.
- **Dry-run validation**: Clear checklist to perform verification steps on a fresh production-like host container/server.

## Tests Required

- **Deployment Dry-Run verification** (Manual checklist verify).

## Quality Requirements

- Correct Markdown formatting.
- Absolute clarity and readability.

## Files Likely Affected

- `docs/DEPLOYMENT-CHECKLIST.md` (new)
