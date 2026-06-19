# Prompt Step Files

Use these files as copy-ready prompts for each project step.

Purpose:

- Keep every AI session narrow.
- Force inspection before implementation.
- Prevent building, coding, scaffolding, migrations, installs, or broad edits unless the user explicitly approves that action.
- Keep `docs/CURRENT-TASK.md` as the active scope boundary.

Default rule:

If the user asks to check, review, compare, verify, inspect, plan, or summarize, use inspection/review steps only. Do not implement.

Implementation rule:

Use `03-implement-current-subtask.md` only when the user explicitly asks to implement/build/fix/apply the current subtask after scope and dependencies are clear.

Blocked-task rule:

If `docs/CURRENT-TASK.md` says the current subtask is blocked, do not implement it. Report the blocker and ask which prerequisite task should be prepared next.
Git restore point rule:

After every approved change batch, use `12-git-restore-point.md` to stage only the approved task files and create a Git commit restore point. If the repo has no baseline commit or many files are still untracked, ask before creating the initial baseline commit.