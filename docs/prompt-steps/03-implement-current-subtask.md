# Step 3: Implement Current Subtask

Use only after Step 1 and Step 2 are clear, dependencies are understood, and the user explicitly approved implementation.

Permission gate:

Implementation is allowed only if the latest user message explicitly asks to implement, build, fix, or apply the current subtask.

Stop instead of implementing when:

- `docs/CURRENT-TASK.md` says the task is blocked.
- Dependencies are unclear.
- The requested work is outside the current subtask.
- A business decision is missing.

Prompt:

```text
Read docs/PROJECT-CONTEXT.md and docs/CURRENT-TASK.md.

Implement only the current subtask because I am explicitly approving implementation now.

Rules:
- Do not implement future tasks.
- Do not change unrelated modules.
- Follow the architecture and business rules in PROJECT-CONTEXT.md.
- Follow the quality rules in PROJECT-CONTEXT.md.
- Use CURRENT-TASK.md as the scope boundary.
- Add or update only the tests required for this subtask.
- Avoid unnecessary abstraction and premature optimization.
- Run formatting, static analysis, and relevant tests where available.
- Do not claim the implementation is optimized without evidence.
- If a dependency is unclear, stop and check the relevant detailed reference document.

Git restore point:

After validation passes, use `12-git-restore-point.md` to create a restore-point commit for only this approved change batch. If Git cannot be updated safely, report why.

After implementation, report:
1. Changed files
2. What was implemented
3. Important decisions
4. Tests run and results
5. Any dependency or documentation update needed
6. Quality checks run
7. Performance assumptions not measured
```