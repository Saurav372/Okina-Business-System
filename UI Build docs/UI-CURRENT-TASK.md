# Current Task

## Current Parent Task
Feedback & Overlay (U1.4)

## Current Subtask
U1.4.3 Toast / Alert

## Goal
Build a premium, accessible, and responsive Toast/Alert notification system managed by Alpine.js, supporting stacked flash alerts, dynamic type variants (success, warning, danger, info), custom timer self-dismissals, manual close buttons, and programmatic trigger helpers.

## Dependencies
**Depends On:**
- U0.5 (Layout Templates)

## Required Deliverables
- Reusable Toast alert components.
- Feeds layout stack (e.g. top-right or bottom-right positioning viewport container).
- Dynamic visual styles matching status tokens (Success, Warning, Danger, Info).
- Automatic self-dismiss countdown timer logic with configurable intervals.
- Keyboard support: dismissible close button focus traps and polite ARIA status announcements.
- Global trigger utility: `window.toast({ message, type, duration })` for simple layout notifications.
- Showcase integration in the components playground at `/admin/components#toast`.

## Completed prerequisites
- U0.1 Design System & Tokens
- U0.5 Layout Templates
- U1.3 Navigation Milestones (Tabs, Breadcrumb, Dropdown, Stepper)
- U1.4.1 Modal
- U1.4.2 Drawer
