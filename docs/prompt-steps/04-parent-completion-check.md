# Step 4: Parent Completion Check

Use only when all subtasks under a parent task are believed complete.

Permission gate:

Do not add new features. Do not mark the parent complete unless every gate passes.

Prompt:

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. docs/task-list.md
4. docs/subtask-validation.md
5. docs/dependency-impact-register.md

Do not add new features.

Validate parent task completion:
- All required subtasks complete
- Subtask acceptance criteria pass
- Parent-level integration tests pass
- Affected modules still work
- APIs and shared data remain correct
- Audit/idempotency rules checked where relevant
- Documentation updated
- Regression checks completed

Return:
1. Completion status
2. Missing items, if any
3. Tests run
4. Regression risks
5. Whether the parent task can be marked complete

Ask for approval before changing any status rows.
```