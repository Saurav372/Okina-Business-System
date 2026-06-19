# Step 7: Documentation Update After Coding

Use after implementation if task status or docs need updating.

Permission gate:

Documentation-only. Do not edit application code.

Prompt:

```text
Read:
1. docs/CURRENT-TASK.md
2. docs/task-list.md
3. docs/subtask-validation.md
4. docs/dependency-impact-register.md only if dependencies changed

Update only the relevant documentation for the completed subtask.

Update:
- Matching task/subtask rows if appropriate
- Notes about changed decisions
- Any new dependency discovered, only if dependency impact changed
- Any tests added or required

Do not rewrite unrelated planning sections.
Do not start the next implementation.

After documentation changes are complete, use `12-git-restore-point.md` to create a restore-point commit for only this documentation update.
```