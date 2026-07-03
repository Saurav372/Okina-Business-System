# Current Task

## Current Parent Task
Foundation (Phase 0.5)

## Current Subtask
U0.3 Navigation Config

## Status
Pending Approval

## Layer
Backend / Config

## Backend Status
🚧 Backend Implementation Required
This task requires setting up centralized navigation configuration.

## Goal
Centralize sidebar links and role-based visibility rules.

---

## Dependencies
**Depends On:**
- U0.2 Admin Route Groups (Completed)

**Blocks:**
- U0.5 Layout Templates

---

## Required Deliverables
- A centralized configuration (e.g., config file or service class) defining navigation links
- Role-based visibility rules for navigation items
- Hierarchy support (nested links)
- Icon references for navigation items

---

## Files To Modify
- `config/navigation.php` (or similar)
- Associated Services or View Composers as needed

## Files Not To Create
- Complex UI components (this is just the configuration structure)

---

## Architecture Rules
- Use a single source of truth for navigation to ensure consistency between the sidebar and mobile menus.
- Navigation structure should define the intended route, label, icon, and required permission/role.

---

## Related Components
This task affects:
- Sidebar Component
- Mobile Menu Component
- Breadcrumbs

---

## Acceptance Criteria
- [ ] Centralized navigation configuration exists
- [ ] Items support labels, routes, icons, and permissions
- [ ] Items support nested children
- [ ] Logic to filter navigation items based on current user permissions

---

## Validation Checklist
Use: `UI-SUBTASK-VALIDATION.md`
Section: **Admin Page Validation Template (Tier 2 Modules: U3.x - U7.x)** *(Note: Config Phase)*

---

## Out Of Scope
Do NOT:
- Build the Sidebar component itself (that's later in U0.5 / U1.1)
- Build the Layouts (U0.5)

---

## References
- UI Task List
- UI AI Prompt Sequence

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
