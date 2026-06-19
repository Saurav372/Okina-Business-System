# Step 12: Git Restore Point

Use after an approved change batch is complete and validated.

Permission gate:

Create a Git restore point only for the approved task changes. Do not stage unrelated user work.

Stop instead of committing when:

- The repo has no baseline commit and many files are untracked.
- The changed files include unrelated user work.
- Validation failed and the user did not approve committing the failing state.
- The user asked for inspection/review only.

Prompt:

```text
Create a Git restore point for the approved change batch.

Rules:
- Run `git status --short`.
- Identify only files changed for the approved task.
- Stage only those files.
- Commit with a concise message including the task ID when available.
- Do not stage unrelated files.
- Do not run destructive Git commands.
- If the repo has no baseline commit or broad untracked files, stop and ask before creating the initial baseline commit.

Return:
1. Files staged
2. Commit message
3. Commit hash if created
4. Any files intentionally left unstaged
5. Any reason a restore point could not be created
```