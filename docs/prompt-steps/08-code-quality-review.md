# Step 8: Code Quality And Efficiency Review

Use after code-producing subtasks.

Permission gate:

Review only. Do not refactor or fix until I approve the findings.

Prompt:

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. All code files changed for the current subtask
4. Related tests

Do not add features.
Do not refactor unrelated code.
Do not fix findings yet.

Review for correctness, Laravel quality, database efficiency, runtime efficiency, maintainability, and safety.

For every finding include:
- File and location
- Problem
- Why it matters
- Recommended correction
- Whether it must be fixed before task completion

Also report:
- Checks executed
- Test results
- Remaining test gaps
- Performance assumptions not measured
- Whether the implementation is acceptable

Ask for approval before applying fixes.
```