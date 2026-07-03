# UI AI Prompt Sequence

Use this sequence to work efficiently with the UI planning files.

For normal development, provide only:

1. `UI Build docs/UI-CURRENT-TASK.md`
2. Existing code files related to the current task.

Do not attach all detailed planning files unless the task needs them.

## Mandatory Build Permission Gate

The default workflow is **inspect first, then ask**.

Do not code, build, scaffold, install dependencies, or edit application files unless the latest user message explicitly approves implementation/building/fixing for the current subtask.

"Proceed" only authorizes inspection and planning.

Implementation requires explicit approval of the proposed implementation plan.

Examples:
✓ "Approve the implementation plan"
✓ "Proceed with implementation"
✓ "Implement the approved plan"

Without this approval, do not modify code.

If `UI Build docs/UI-CURRENT-TASK.md` says the current subtask is blocked or pending, do not implement it. Report the status and ask for approval.

## Global UI & Scope Rules

- **Respect Existing Coding Style:** Follow the existing project naming conventions, folder structure, formatting, and coding style unless the current task explicitly introduces a new standard.
- **Never create placeholder UI:** Every implemented screen must connect to real backend data unless the current task explicitly allows mock content. No fake metrics, orders, or hardcoded tables.
- **Existing Component Check:** Search for an existing component before creating a new one. If an equivalent exists, reuse or extend it. Do not duplicate components.
- **Existing Utility Check:** Before introducing new CSS utilities, helper classes, or JavaScript helpers, search for an existing implementation and reuse or extend it where appropriate.
- **File Creation Rules:** Do not create new files unless required. Prefer modifying existing components. Explain why a new file is necessary before creating it.
- **Scope Protection:** If additional improvements are discovered outside the approved subtask, report them, do not implement them. 
- **Avoid Large Refactors:** Do not perform opportunistic refactoring while implementing an unrelated task.
- **Respect Existing Tests:** Never modify existing backend tests unless the current task explicitly requires updating them. Existing tests are treated as behavioral contracts.
- **Astro Frontend Rule:** If the current task belongs to the Astro frontend (Phase 8), consume Laravel APIs only. Astro should also consume the shared Design System. Do not create a separate visual language for the frontend. Never duplicate business rules or validation logic from Laravel. Treat Laravel as the absolute source of truth.
- **Astro Head Rule:** SEO metadata should be generated through a shared Astro layout/component. Do not duplicate `<head>` meta tag templates across pages. Child pages should override metadata only when necessary, otherwise they must inherit from the shared layout.
- **Animation Library Rule:** Use native CSS transitions, Motion Tokens, Alpine.js (Blade), and Astro View Transitions. Do not introduce GSAP, Three.js, or other animation libraries unless the current task explicitly requires advanced marketing animations or 3D visualization and the user approves.

## Architecture Preservation Rules

- Never bypass Services.
- Never duplicate business logic.
- Never bypass Policies.
- Never bypass Form Requests.
- Never move validation or business logic into Blade templates.
- Never change API contracts without approval.
- Keep presentation strictly separate from business logic. (Controllers remain thin).
- Never duplicate Design System tokens.
- Never hardcode colors, typography, spacing, or motion values in components.
- **SEO Data Source:** SEO metadata should originate from backend-managed SEO fields whenever available. Do not hardcode page metadata unless the page is intentionally static.
- **Global Indexing:** Public pages must be indexed unless explicitly excluded. Private pages (dashboard, checkout, portal) must be noindex by default.
- **URL Quality:** Public URLs must be stable, human-readable, slug-based, and lowercase.

## Definition of Done (Per Screen)

Before any screen is marked complete, it must satisfy:
- **Responsive:** Layout functions perfectly from mobile to desktop.
- **Accessible:** Semantic HTML, ARIA labels, contrast, keyboard navigation.
- **Permission-aware:** Components hidden/locked for unauthorized roles.
- **State handling:** Loading state, Empty state, and Error state implemented.
- **Validation:** Real-time client-side validation reflecting backend rules.
- **Performance:** Server-side pagination, no N+1 queries, optimized images, lazy loading, CLS safe, LCP optimized.
- **Motion:** Uses project motion tokens. Respects `prefers-reduced-motion`.
- **Public Pages SEO (Astro Only):** Semantic HTML, single correct H1, Meta Title, Meta Description, Canonical link, Robots directives (index/noindex), Product/Breadcrumb Schema (Structured Data), Image Alt text, Open Graph, Twitter Cards, Internal Links. Admin pages do NOT require SEO.

## Mandatory Git Restore Point Gate

After every approved change batch, update Git so there is a restore point.

Rules:
- Run validation first.
- Run `git status --short`.
- Stage only files changed for the approved task.
- Commit using the task ID in the commit message.
- Use Sequence 12 after implementation, documentation updates, or approved fixes.

---

## Suggested Final Workflow Cycle

To execute a task efficiently, run through these sequences in order:

Sequence 1
Inspect
↓
Sequence 2
Dependency Check
↓
Sequence 2.5
Implementation Plan
↓
User Approval
↓
Sequence 3
Implementation
↓
Sequence 8
Review
↓
Sequence 7
Documentation
↓
Sequence 12
Git
↓
Next Task

---

## Quick Prompt Chooser

| Situation | Use This Sequence |
|---|---|
| Starting a subtask and you are not sure what exists yet | Sequence 1 |
| The task touches shared components, layouts, or routing | Sequence 2 |
| Dependencies verified, you need an implementation plan | Sequence 2.5 |
| Scope is clear and you explicitly approve implementation | Sequence 3 |
| All subtasks under a parent are believed done | Sequence 4 |
| You are moving from one subtask to the next | Sequence 5 |
| Shared UI behavior changed | Sequence 6 |
| One subtask is done and task docs need status updates | Sequence 7 |
| Code was changed and you want a UI/accessibility/quality/SEO review | Sequence 8 |
| A UI standard, route structure, or component pattern is unclear | Sequence 9 |
| **You want to refactor/improve existing UI without changing behavior** | **Sequence 10** |
| **You need to investigate a bug before fixing it** | **Sequence 11** |
| Approved changes are complete and need a restore point | Sequence 12 |

---

## Sequence 1: Start A New Task

Use this before coding.

```text
Read UI Build docs/UI-TASK-LIST.md and UI Build docs/UI-CURRENT-TASK.md.

Do not code yet.

Confirm:
1. Current parent task
2. Current subtask
3. Intended UI deliverables
4. Dependencies (Components, Layouts)
5. Acceptance criteria (including SEO requirements if the task is a public customer page)
6. Files likely affected

Then inspect the existing related code and tell me:
- What already exists
- What is missing
- Any dependency or conflict
- Whether detailed reference docs are needed before coding
```

## Sequence 2: Check Dependencies Only

Use this when a task affects shared layouts or global CSS/components.

```text
Read:
1. UI Build docs/UI-CURRENT-TASK.md

Do not code yet.

Inspect the existing implementation. Do not assume dependencies.
Search the codebase to identify the actual:
- Affected layouts
- Affected Blade components
- Affected routes
- Affected CSS and JavaScript

Return a short impact summary based on actual file inspection and say whether it is safe to proceed.
```

## Sequence 2.5: Implementation Plan & Approval

Use this immediately before Sequence 3.

```text
Read:
1. UI Build docs/UI-CURRENT-TASK.md
2. Related source files
3. Related shared components/layouts (if applicable)

Do NOT implement yet.

Prepare a detailed implementation plan.

The plan must include:

## Goal
Summarize the current subtask.

## Proposed Changes
List every file that will be modified.

For each file explain:
- Why it needs to change.
- What will be added.
- What will be modified.
- What will remain unchanged.

## New Files
List any new files required.
If none are needed, explicitly state: "No new files will be created."

## Existing Components
Identify reusable components.
State whether they will:
- Reuse
- Extend
- Remain unchanged

## Risk Assessment
List:
- Possible regressions
- Dependency risks
- Architecture concerns

## Validation Plan
Explain how the implementation will be verified.

## Questions
If any design or architecture decisions require user approval, ask them now.

Stop here. Do NOT implement anything until the user explicitly approves the implementation plan.
```

## Sequence 3: Implement Current Subtask

Use this only after the start-task check is clear and the user approves implementation.

```text
Read UI Build docs/UI-CURRENT-TASK.md.

If the task references architecture, routing, shared layouts, theme tokens, or design system rules, consult the UI Implementation Plan artifact before implementation.

Only execute after:
✓ Sequence 1 complete
✓ Sequence 2 complete
✓ Sequence 2.5 approved by the user

If approval has not been given:
Stop.
Do not modify code.

Implement only the current subtask.

Rules:
- Search for an existing component before creating a new one. Do not duplicate components.
- Do not create new files unless absolutely required. Prefer modifying existing components. Explain why a new file is necessary before creating it.
- Preserve all existing functionality unless the current task explicitly changes behavior.
- Do not remove or redesign existing functionality outside the approved scope.
- Never create placeholder UI. Connect to real backend data.
- Keep business logic in Services. Controllers remain thin. Do not move logic into Blade.
- Use Design System Tokens only (Color, Typography, Spacing, Motion).
- Never hardcode UI values in components unless explicitly approved.
- Build responsive, accessible HTML. Use:
  - Design System Tokens
  - Shared Components
  - Motion Tokens
  - Existing Layouts
- For public pages, build SEO at the same time: Meta Title, Meta Description, Canonical, Robots directives (index/noindex), Open Graph, Twitter Cards, Structured Data, Image Alt text, and Internal linking where applicable.
- Apply Content Negotiation (wantsJson) where backend integration is required.

After implementation, report:
1. Changed files
2. What was implemented
3. UI Decisions made
4. Manual verification steps required
```

## Sequence 4: Complete A Parent Task

Use this when all subtasks under a parent task are complete.

```text
Read:
1. UI Build docs/UI-CURRENT-TASK.md
2. UI Build docs/UI-TASK-LIST.md
3. UI Build docs/UI-SUBTASK-VALIDATION.md

Validate parent task completion:
- All required subtasks complete
- UI Standards (Responsive, Accessible) met
- SEO acceptance criteria for every completed public page satisfied
- Backend APIs remain unbroken
- Documentation updated

Return:
1. Completion status
2. Missing items, if any
3. Whether the parent task can be marked complete
```

## Sequence 5: Update UI-CURRENT-TASK.md For Next Subtask

Use this when moving to the next subtask.

```text
Read:
1. UI Build docs/UI-TASK-LIST.md
2. UI Build docs/UI-SUBTASK-VALIDATION.md

Prepare UI Build docs/UI-CURRENT-TASK.md for this subtask:
[PUT SUBTASK ID AND NAME HERE]

Include only:
- Current Parent Task
- Current Subtask
- Goal
- Dependencies
- Required Deliverables
- Blocked by (optional)
- Completed prerequisites
```

## Sequence 6: Regression Or Safety Review

Use this before deployment or after changing a core Layout or Component.

```text
Read:
1. UI Build docs/UI-CURRENT-TASK.md

Perform a regression and safety review for the current UI change.

Check:
- Component still works in all instances
- Layout responsive breakpoints remain intact
- Mobile navigation still works
- Motion regression (transitions, durations, reduced-motion behavior preserved)
- Backend tests still pass (Zero Regression)
- SEO regression (Canonicals unchanged, Meta generation still works, Schema still valid, Robots directives preserved)

Return findings first, ordered by severity.
```

## Sequence 7: Documentation Update After Coding

Use this after implementation if task status or docs need updating.

```text
Read:
1. UI Build docs/UI-CURRENT-TASK.md
2. UI Build docs/UI-TASK-LIST.md
3. UI Build docs/UI-SUBTASK-VALIDATION.md

Update only the relevant documentation for the completed subtask.
Move matching task/subtask rows from `Pending` to `Completed`.
```

## Sequence 8: Code Quality And Efficiency Review

Use this after implementation for code-producing subtasks.

```text
Read:
1. UI Build docs/UI-CURRENT-TASK.md
2. All code files changed for the current subtask

Review for:

UI Correctness:
- Responsive across mobile/desktop
- Accessibility (ARIA labels, contrast)
- Layout matches patterns

Motion Review:
- Uses motion tokens
- No hardcoded durations/easing
- Respects `prefers-reduced-motion`
- No unnecessary animation libraries introduced

SEO Correctness (Public Pages Only):
- Heading hierarchy: Single H1, logical H2-H6 structure
- Meta Title & Description present
- Canonical: Present, self-referencing where appropriate, no duplicate canonicals
- Robots directives (index/noindex) present appropriately
- Structured Data: Present, valid JSON-LD, matches visible page content
- Open Graph and Twitter Cards present
- Images: Alt text, correct dimensions, lazy loading where appropriate, modern formats
- Internal links present
- Broken links absent

Laravel Quality:
- Blade components used correctly
- Content negotiation `$request->wantsJson()` is intact
- No N+1 queries introduced into controllers
- No business logic or validation bypassed

Performance:
- No duplicate database queries
- No unnecessary CSS
- No unnecessary JavaScript
- No unused JavaScript dependencies introduced
- Components reused where appropriate

Return findings ordered by severity.
```

## Sequence 9: UI Decision Or Clarification Capture

Use this when a UI pattern or business rule is unclear.

```text
Read UI Build docs/UI-CURRENT-TASK.md.

Identify UI decisions needed before this task can proceed safely.

For each decision, return:
1. Decision question
2. Why it matters
3. Affected components
4. Recommended default
5. Options with tradeoffs
```

## Sequence 10: Refactor Existing UI

Use this to improve code without changing user-facing behavior.

```text
Read UI Build docs/UI-CURRENT-TASK.md and related component files.

Inspect current implementation.

Do not change behavior.

Improve:
- Component reuse
- Readability
- Accessibility
- Removal of duplication
- Responsiveness
- Design system consistency
- Motion consistency

Return proposed changes before implementation.
```

## Sequence 11: Bug Investigation

Use this to understand a UI defect before attempting a fix.

```text
Read UI Build docs/UI-CURRENT-TASK.md.
Inspect related files.

Do not fix yet.

Identify:
- Root cause
- Affected files
- Regression risk (what else might break)
- Recommended fix

Return findings so the fix can be reviewed before coding.
```

## Sequence 12: Git Restore Point

Use this after approved changes are complete.

```text
Run validation first.
Run `git status --short`.
Stage only files changed for the approved UI task.
Commit with the task ID in the commit message (e.g., "UI: [U1.1] Implement Form Components").
```
