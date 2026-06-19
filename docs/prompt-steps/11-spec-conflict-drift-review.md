# Step 11: Spec Conflict Or Decision Drift Review

Use when documents disagree or before a major phase begins.

Permission gate:

Review only. Do not rewrite docs until I approve the recommended updates.

Prompt:

```text
Read the provided planning documents.

Do not code.
Do not rewrite docs yet.

Review for spec conflicts or decision drift.

Check:
- Conflicting business rules
- Conflicting build order
- Conflicting dependencies
- Conflicting task statuses
- Conflicting acceptance criteria
- Completed decisions not reflected in task docs
- Old broad spec text that should no longer drive implementation
- Missing documentation updates caused by recent decisions

Return:
1. Conflicts found, ordered by severity
2. Exact files/sections involved
3. Recommended source of truth for each conflict
4. Suggested documentation update
5. Whether work can continue before resolving each conflict

Ask for approval before editing files.
```