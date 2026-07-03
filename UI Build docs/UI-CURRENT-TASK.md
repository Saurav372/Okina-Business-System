# Current Task

## Current Parent Task
U0.1 Design System & Tokens

## Current Subtask
U0.1.4 Breakpoints

## Status
Pending Approval

## Layer
Shared

## Backend Status
✅ Backend Ready
No backend implementation required.

## Goal
Define the responsive breakpoint system for the shared design system using a mobile-first approach.

---

## Dependencies
**Depends On:**
- U0.1.3 Spacing Scale (Completed)

**Blocks:**
- U0.1.5 Motion & Animation Tokens

---

## Required Deliverables
- Base breakpoint (0px)
- xs (360px)
- sm (480px)
- md (640px)
- lg (768px)
- xl (1024px)
- 2xl (1280px)
- 3xl (1536px)
- Tailwind v4 breakpoint configuration

---

## Files To Modify
- `apps/backend/resources/css/app.css`

## Files Not To Create
- `breakpoints.css`
*(Unless explicitly approved)*

---

## Architecture Rules
- Use semantic CSS variables and Tailwind v4 theme extensions.
- No breaking changes.

---

## Related Components
This task affects:
- All Layouts
- All Components
- Responsive Behavior

---

## Acceptance Criteria
- [ ] Base styles work from 320px+
- [ ] xs breakpoint defined
- [ ] sm breakpoint defined
- [ ] md breakpoint defined
- [ ] lg breakpoint defined
- [ ] xl breakpoint defined
- [ ] 2xl breakpoint defined
- [ ] 3xl breakpoint defined

---

## Validation Checklist
Use: `UI-SUBTASK-VALIDATION.md`
Section: **U0.1.4 Breakpoints**

---

## Out Of Scope
Do NOT:
- Define motion tokens (these are separate subtasks)
- Build layouts
- Build components
- Define spacing tokens

---

## References
- UI Task List
- UI AI Prompt Sequence
- UI Subtask Validation

---

## Workflow Reminder
Follow:
1. UI AI Prompt Sequence
2. UI Subtask Validation
3. Update documentation
4. Create Git restore point

---

## Completion Requirements
A task is complete only when:
- Acceptance criteria satisfied
- Validation checklist passed
- Documentation updated
- Regression review passed
- Git restore point created

---

## Completion Notes
*(To be filled after implementation)*
