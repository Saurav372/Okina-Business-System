# Current Task

## Current Parent Task
Feedback & Overlay (U1.4)

## Current Subtask
U1.4.2 Drawer

## Goal
Build a premium, accessible, and responsive slide-out side panel overlay component (`<x-drawer>`) managed by Alpine.js, supporting slide transitions (from right or left edge), background scroll lock, keyboard focus trap, backdrop overlays, dismissible options, and custom header/footer layout slots.

## Dependencies
**Depends On:**
- U0.5 (Layout Templates)

## Required Deliverables
- `<x-drawer>` blade component.
- Configurable placement/positioning support (`placement="right"` or `"left"`).
- Dynamic size presets matching design system rules (e.g. sm, md, lg, xl, full).
- Safe background scroll lock matching the modal reference counting mechanism.
- Advanced keyboard interaction:
  - Focus trap (constraining focus loop inside drawer when open).
  - Dismissing topmost overlay on `Escape` key.
  - Restoring focus back to the triggering element.
- Semantic ARIA attributes (`role="dialog"`, `aria-modal="true"`, `aria-labelledby`, `aria-describedby`).
- Motion transitions matching design system tokens for backdrop opacity fade and panel slide-in reveals.
- Showcase integration in the playground at `/admin/components#drawer`.

## Completed prerequisites
- U0.1 Design System & Tokens
- U0.5 Layout Templates
- U1.3 Navigation Milestones (Tabs, Breadcrumb, Dropdown, Stepper)
- U1.4.1 Modal
