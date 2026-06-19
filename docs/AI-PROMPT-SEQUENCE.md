# AI Prompt Sequence

Use this sequence to work efficiently with the planning files.

For normal development, provide only:

1. `docs/PROJECT-CONTEXT.md`
2. `docs/CURRENT-TASK.md`
3. Existing code files related to the current task

Do not attach all detailed planning files unless the task needs them.

## Mandatory Build Permission Gate

The default workflow is inspect first, then ask.

Do not code, build, scaffold, install dependencies, run migrations, or edit application files unless the latest user message explicitly approves implementation/building/fixing for the current subtask.

If the user says check, compare, verify, review, inspect, plan, or summarize, use Sequence 1, 2, 4, 6, 8, 9, 10, or 11 only. Stop after reporting findings and ask before implementation.

If `docs/CURRENT-TASK.md` says the current subtask is blocked, do not implement it. Report the blocker and ask which prerequisite should be handled next.

Before moving between Project B and Project C subtasks, read `docs/project-b-c-build-runway.md` and verify that the selected subtask is the next unblocked step. Do not move directly to a later checkout/order/admin step just because it is the next visible row in one parent table.

Copy-ready step files live in `docs/prompt-steps/`. Prefer those files when starting a new AI session so the permission gate is visible in the prompt itself.

## Mandatory Git Restore Point Gate

After every approved change batch, update Git so there is a restore point.

Rules:

- Run `git status --short` before and after edits.
- Stage only files changed for the approved task.
- Commit after validation passes, using the task ID in the commit message when available.
- Do not stage unrelated user work.
- If the repo has no baseline commit or most files are untracked, ask before creating the initial baseline commit.
- If a restore point cannot be created, report the reason and the files changed.

Use `docs/prompt-steps/12-git-restore-point.md` after implementation, documentation updates, or approved fixes.
## Quick Prompt Chooser

Use this table when deciding what to send next.

| Situation | Use This Sequence | Why |
|---|---|---|
| Starting a subtask and you are not sure what exists yet | Sequence 1 | Confirms scope, deliverables, missing code/docs, and whether extra references are needed. |
| The task touches checkout, orders, payments, inventory, files, permissions, audit, notifications, or shared APIs | Sequence 2 | Checks cross-module impact before work begins. |
| Scope is clear and you explicitly approve implementation/building | Sequence 3 | Produces only the active unblocked subtask deliverable after approval. |
| One subtask is done and task/docs need status updates | Sequence 7 | Updates only the matching task rows and completion notes. |
| Code was changed and you want a bug/risk review before calling it done | Sequence 8 | Reviews correctness, tests, Laravel quality, database efficiency, and safety. |
| Shared behavior changed or the work is close to deployment | Sequence 6 | Looks for regressions in connected flows. |
| All subtasks under a parent are believed done | Sequence 4 | Checks whether the parent task can actually be marked complete. |
| You are moving from one subtask to the next | Sequence 5 | Rewrites `CURRENT-TASK.md` for the next narrow scope. For Project B/C, check the Project B/C build runway first. |
| A business rule, pricing rule, payment rule, SKU rule, or staff policy is unclear | Sequence 9 | Captures a decision before implementation locks in the wrong behavior. |
| You need to confirm shared hosting, PHP, Composer, cron, queues, MySQL, storage, or webhooks | Sequence 10 | Checks whether the planned stack can run in the target environment. |
| Two docs disagree, or a new decision may have made older docs stale | Sequence 11 | Finds conflicts and tells you what must be updated. |
| Approved changes are complete and need a restore point | Step 12 | Stages only the approved task files and creates a Git restore-point commit. |

If unsure, start with Sequence 1. If Sequence 1 finds cross-module risk, run Sequence 2 before implementation.

## Sequence 1: Start A New Task

Use this before coding or writing a planning artifact.

Best situations:

- You are starting a new subtask.
- `CURRENT-TASK.md` was just changed.
- You need to confirm what is already present in the project.
- You are unsure whether extra reference docs are needed.
- You want the AI to inspect before making edits.

Do not use this when:

- The subtask has already been inspected and you are ready to implement.
- You only need a documentation status update.

```text
Read docs/PROJECT-CONTEXT.md and docs/CURRENT-TASK.md.

Do not code yet.

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
```

## Sequence 2: Check Dependencies Only

Use this when a task may affect another module or shared business rule.

Add `docs/dependency-impact-register.md` only for this prompt.

Best situations:

- Product/SKU changes may affect cart, order items, inventory, or public APIs.
- Customer/address changes may affect checkout, sales orders, or CRM.
- Order or payment changes may affect admin, finance, tracking, refunds, or notifications.
- File/upload changes may affect private storage, mockups, order files, or admin access.
- Role/permission changes may affect admin screens or finance visibility.
- You are uncertain whether a change is safe to proceed with.

Skip this when:

- The subtask is isolated documentation and no dependency changed.
- Sequence 1 already confirmed there is no cross-module impact.

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. docs/dependency-impact-register.md

Do not code yet.

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

Return a short impact summary and say whether it is safe to proceed.
```

## Sequence 3: Implement Current Subtask

Use this only after the start-task check is clear, dependencies are understood, the current task is not blocked, and the user explicitly approves implementation/building.

Best situations:

- You want the AI to create the current planning document.
- You want the AI to write code for the current subtask.
- You want tests added or updated for the current subtask.
- You have already confirmed the task scope and are ready for action.

For planning subtasks, "implement" means create or update the relevant planning artifact only.

Do not use this when:

- You only want inspection.
- The current task file is stale or still points to the previous subtask.
- Parent completion is being checked.

```text
Read docs/PROJECT-CONTEXT.md and docs/CURRENT-TASK.md.

Implement only the current subtask.

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

After implementation, report:
1. Changed files
2. What was implemented
3. Important decisions
4. Tests run and results
5. Any dependency or documentation update needed
6. Quality checks run
7. Performance assumptions not measured
```

## Sequence 4: Complete A Parent Task

Use this only when all subtasks under a parent task are believed complete.

Best situations:

- All subtask rows for a parent look complete.
- You want to verify the parent completion gate before marking it complete.
- You need a final list of missing items.
- You need to confirm parent-level integration/regression checks.

Do not use this after only one subtask is completed. Use Sequence 7 instead.

Add:

- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`

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
```

## Sequence 5: Update CURRENT-TASK.md For Next Subtask

Use this when moving to the next subtask.

Best situations:

- You finished one subtask and want to start the next.
- `CURRENT-TASK.md` still points to old work.
- You want the next task narrowed before inspection or implementation.
- You need dependencies and acceptance criteria copied from the task docs into the active task file.

Do not use this to mark a completed subtask. Use Sequence 7 for completion/status updates.

Add:

- `docs/task-list.md`
- `docs/subtask-validation.md`

```text
Read:
1. docs/task-list.md
2. docs/subtask-validation.md

Prepare docs/CURRENT-TASK.md for this subtask:
[PUT SUBTASK ID AND NAME HERE]

Include only:
- Current Parent Task
- Current Subtask
- Goal
- Dependencies
- Required Deliverables
- Acceptance Criteria
- Tests Required
- Quality Requirements
- Files Likely Affected
- Tasks Not Included
- Reference Details

Keep it narrow. Do not copy unrelated project information.
```

## Sequence 6: Regression Or Safety Review

Use this before deployment or after changing shared behavior.

Best situations:

- Checkout, payment, order status, inventory, permissions, files, audit, or notifications changed.
- A shared API or shared table changed.
- A bug fix might affect another module.
- You are preparing to deploy or complete a high-risk task.

Skip this for isolated planning-only subtasks unless they changed an approved shared rule.

Add:

- `docs/dependency-impact-register.md`
- relevant test files or code files

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
```

## Sequence 7: Documentation Update After Coding

Use this after implementation if task status or docs need updating.

Best situations:

- A subtask deliverable was completed.
- A task/subtask row should move from `Not Started` to `In Progress` or `Completed`.
- A completed artifact should be linked from `CURRENT-TASK.md`.
- Tests, decisions, or discovered dependencies need to be recorded.

Do not use this to rewrite broad planning sections or start the next subtask.

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
```

## Sequence 8: Code Quality And Efficiency Review

Use this after implementation for code-producing subtasks.

Best situations:

- Laravel, Astro, API, database, or test code changed.
- You want a review focused on bugs, risks, missing tests, and maintainability.
- The task touches performance-sensitive database access.
- The task touches security, payment, inventory, permissions, files, or idempotency.

Skip this for documentation-only planning subtasks unless the document contains technical rules you want reviewed manually.

Provide:

- `docs/PROJECT-CONTEXT.md`
- `docs/CURRENT-TASK.md`
- Changed code files
- Related test files

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. All code files changed for the current subtask
4. Related tests

Do not add features.
Do not refactor unrelated code.

Review for:

Correctness:
- Acceptance criteria are satisfied
- Edge cases and failure paths are handled
- Tests verify actual behavior

Laravel quality:
- Normal Laravel conventions are followed
- Validation and authorization are correctly placed
- Controllers are focused
- Unnecessary services, repositories, or abstractions are avoided

Database efficiency:
- No N+1 query risks
- Relationships are eager loaded where appropriate
- Large datasets use pagination or chunking
- Unnecessary columns and records are not loaded
- Repeated queries are avoided
- Required indexes and constraints exist
- Multi-record writes are transaction-safe

Runtime efficiency:
- Expensive work is not repeated unnecessarily
- Large collections are not loaded fully into memory
- Slow external operations use queues where appropriate
- Caching is added only when justified

Maintainability:
- Logic is not duplicated
- Names and responsibilities are clear
- Methods are not excessively large
- The implementation is not more complex than the requirement

Safety:
- Input validation and authorization are present
- Sensitive values remain protected
- Idempotency is preserved where required
- Payment, inventory, webhook, and notification behavior remains safe

Run where applicable:
- ./vendor/bin/pint --test
- ./vendor/bin/phpstan analyse
- php artisan test

Return findings first, ordered by severity.

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

Do not claim that code is optimized without query inspection, benchmark results, memory measurements, or other evidence.
```

## Sequence 9: Business Decision Or Clarification Capture

Use this when a business or operational decision is unclear and implementation would be risky without an answer.

Best situations:

- GST, invoice, tax, refund, cancellation, COD, or payment policy is undecided.
- Pricing, bulk threshold, discount, shipping charge, or SKU naming rules are unclear.
- Product options, print methods, upload limits, file cleanup, or mockup rules need approval.
- Staff permission, customer verification, file access, or audit expectations need confirmation.
- The AI finds a decision listed in the master build plan that blocks the current task.

Do not use this for normal coding once the decision is already documented.

Add only the reference docs needed for the decision:

- `docs/PROJECT-CONTEXT.md`
- `docs/CURRENT-TASK.md`
- `okina_craft_master_build_plan.md` for open decisions
- `docs/main-system-requirements.md` or `docs/feature-list.md` if the rule belongs there

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. okina_craft_master_build_plan.md
4. docs/main-system-requirements.md or docs/feature-list.md only if needed

Do not code.

Identify business or operational decisions needed before this task can proceed safely.

For each decision, return:
1. Decision question
2. Why it matters
3. Affected tasks/modules
4. Recommended default, if there is a safe conservative default
5. Options with tradeoffs
6. Where the final answer should be documented

If all needed decisions are already clear, say that clearly.

Do not invent final business decisions unless the docs already support them.
```

## Sequence 10: Environment And Hosting Readiness Check

Use this before scaffolding, before choosing the Laravel version, before deployment planning, or before deciding shared hosting versus VPS.

Best situations:

- Confirming whether cPanel can run Laravel 13 and PHP 8.3+.
- Checking Composer, PHP extensions, MySQL version, cron, queue driver, storage permissions, and webhooks.
- Deciding whether backend should start on shared hosting or VPS.
- Preparing deployment or rollback planning.
- Checking whether uploads, queues, scheduler, payment callbacks, and background jobs are practical on the target host.

Do not use this for ordinary feature work after the environment has already been approved.

Add:

- `docs/PROJECT-CONTEXT.md`
- `docs/CURRENT-TASK.md`
- `okina_craft_master_build_plan.md`
- Any hosting notes, cPanel screenshots, server info, or deployment docs available

```text
Read:
1. docs/PROJECT-CONTEXT.md
2. docs/CURRENT-TASK.md
3. okina_craft_master_build_plan.md
4. Any provided hosting/server details

Do not code.

Check environment and hosting readiness for the current phase.

Verify:
- Required PHP version for the selected Laravel version
- Required PHP extensions
- Composer availability
- MySQL/MariaDB compatibility
- File storage permissions
- Cron/scheduler support
- Queue worker feasibility
- Payment webhook accessibility
- Upload size and storage limits
- SSL/domain/subdomain readiness
- Backup and rollback options
- Whether shared hosting is acceptable or VPS is recommended

Return:
1. Readiness status
2. Missing information
3. Blocking risks
4. Non-blocking risks
5. Recommended hosting path
6. Checks to perform manually
7. Documentation updates needed

Do not make hosting assumptions without evidence.
```

## Sequence 11: Spec Conflict Or Decision Drift Review

Use this when two documents disagree, when a new decision may have made older docs stale, or before a major phase begins.

Best situations:

- The full project spec, master build plan, task list, and current task do not say the same thing.
- A completed schema/design decision may conflict with older planning text.
- A business decision changes scope, dependencies, build order, or acceptance criteria.
- Before parent completion if several documents were edited across multiple sessions.
- Before scaffolding or a phase transition.

Do not use this for every small task. It is a control check for important planning drift.

Add the smallest useful set of docs. For broad drift checks, add:

- `docs/PROJECT-CONTEXT.md`
- `docs/CURRENT-TASK.md`
- `docs/task-list.md`
- `docs/subtask-validation.md`
- `docs/dependency-impact-register.md`
- Relevant completed planning artifact
- `okina_craft_master_build_plan.md` if the conflict may come from the master plan
- `okina_craft_full_project_spec.md` only if the original full spec must be checked

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

If no conflicts are found, say that clearly and mention remaining uncertainty.
```

## Best Default Flow

For most subtasks, use this order:

0. Project B/C runway check: read `docs/project-b-c-build-runway.md` before moving between Project B/C workflow subtasks.
1. Sequence 5: update `CURRENT-TASK.md`
2. Sequence 1: start and inspect
3. Sequence 9: business decision capture, only if a needed decision is unclear
4. Sequence 10: environment readiness, only before scaffolding/deployment or hosting-sensitive work
5. Sequence 11: spec conflict review, only if docs disagree or a major decision changed
6. Sequence 2: dependency check, only if cross-module impact exists
7. Sequence 3: implement only after explicit user approval
8. Sequence 8: quality review for code-producing tasks
9. Sequence 6: regression/safety review, if shared behavior changed
10. Sequence 7: update docs
11. Sequence 4: complete parent task, only after all subtasks are done

For the current planning-to-scaffold transition, use:

1. Sequence 11: resolve any drift if `CURRENT-TASK.md`, the task list, and the master build plan disagree
2. Sequence 5: point `CURRENT-TASK.md` at the next narrow task
3. Sequence 10: complete environment and hosting readiness before scaffolding
4. Sequence 3: scaffold only after the readiness task is complete and `CURRENT-TASK.md` is moved to A1.5
5. Sequence 7: update documentation after the scaffold checks pass

Do not start A2.1 Admin authentication until the Laravel backend scaffold exists and passes its basic boot/test checks.

## Which Files To Attach

Normal coding:

- `PROJECT-CONTEXT.md`
- `CURRENT-TASK.md`
- related code files

Code quality review:

- `PROJECT-CONTEXT.md`
- `CURRENT-TASK.md`
- changed code files
- related test files

Dependency or cross-module change:

- add `dependency-impact-register.md`
- add `project-b-c-build-runway.md` when moving between Project B/C workflow subtasks

Parent task completion:

- add `task-list.md`
- add `subtask-validation.md`
- add `dependency-impact-register.md`
- add `project-b-c-build-runway.md` when moving between Project B/C workflow subtasks

Business rule clarification:

- add `main-system-requirements.md`
- add `feature-list.md`
- add `okina_craft_master_build_plan.md` when open decisions are involved

Project ownership clarification:

- add `project-specifications.md`

Environment or hosting readiness:

- add `okina_craft_master_build_plan.md`
- add hosting/server details if available

Spec conflict or decision drift:

- add the smallest set of conflicting docs
- add `okina_craft_full_project_spec.md` only when the original full spec must be checked

## Important Rule

If the AI asks for all documents every time, do not provide them.

Instead, provide:

```text
Use PROJECT-CONTEXT.md and CURRENT-TASK.md first.
Ask for a specific reference document only if a dependency, business rule, or completion gate is unclear.
```
