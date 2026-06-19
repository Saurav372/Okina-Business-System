# Step 6: Regression Or Safety Review

Use before deployment or after shared behavior changes.

Permission gate:

Review only. Do not fix anything unless I explicitly approve fixes after seeing findings.

Prompt:

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. docs/dependency-impact-register.md

Perform a regression and safety review for the current change.

Check:
- Checkout still works
- Admin order view still works
- Payment records remain correct
- Shared data remains correct
- Permissions still hold
- Private files remain private
- Audit events/logs still work where relevant
- Idempotency still works where relevant
- No unrelated modules were changed

Return findings first, ordered by severity.
If no issues are found, say that clearly and mention remaining test gaps.

Do not implement fixes until I approve them.
```