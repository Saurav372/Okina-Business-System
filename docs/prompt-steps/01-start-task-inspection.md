# Step 1: Start Task Inspection

Use when starting or resuming a subtask.

Permission gate:

Do not code, build, run migrations, install dependencies, or edit application files.

Prompt:

```text
Read docs/PROJECT-CONTEXT.md and docs/CURRENT-TASK.md.

Do not code yet.
Do not build yet.
Do not edit files yet unless I explicitly ask for documentation-only cleanup.

Confirm:
1. Current parent task
2. Current subtask
3. Intended deliverables
4. Dependencies
5. Acceptance criteria
6. Tests required
7. Files likely affected
8. Tasks not included

Then inspect the existing related code and tell me:
- What already exists
- What is missing
- Any dependency or conflict
- Whether detailed reference docs are needed before coding
- Whether this task is blocked

Stop after the inspection and wait for my approval before implementing.
```