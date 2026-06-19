# Step 2: Dependency Impact Check

Use when a task may affect shared rules, files, checkout, orders, payments, inventory, permissions, audit, notifications, or APIs.

Permission gate:

Do not code, build, run migrations, install dependencies, or edit application files.

Prompt:

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. docs/dependency-impact-register.md

Do not code yet.
Do not build yet.

Check the impact of the current subtask:
- Affected projects
- Affected APIs
- Affected database tables
- Affected admin screens
- Affected customer screens
- Affected reports
- Affected notifications
- Affected audit records
- Idempotency concerns
- Tests that must be repeated

Return a short impact summary, say whether it is safe to proceed, and ask for approval before implementation.
```