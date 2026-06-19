# Step 10: Environment And Hosting Readiness Check

Use before scaffolding, deployment planning, or hosting-sensitive work.

Permission gate:

Do not scaffold, install, migrate, deploy, or change environment files unless I explicitly approve it.

Prompt:

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. okina_craft_master_build_plan.md
4. Any provided hosting/server details

Do not code.
Do not install dependencies.
Do not migrate or deploy.

Check environment and hosting readiness for the current phase.

Verify:
- Required PHP version for the selected Laravel version
- Required PHP extensions
- Composer availability
- MySQL/MariaDB compatibility
- File storage permissions
- Cron/scheduler support
- Queue worker feasibility
- Payment webhook accessibility
- Upload size and storage limits
- SSL/domain/subdomain readiness
- Backup and rollback options
- Whether shared hosting is acceptable or VPS is recommended

Return readiness status, missing information, risks, recommended hosting path, manual checks, and documentation updates needed.

Ask for approval before making changes.
```