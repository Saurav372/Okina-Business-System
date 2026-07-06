# Current Task

## Current Parent Task
Navigation (U1.3)

## Current Subtask
U1.3.4 Stepper

## Goal
Build a reusable stepper indicator component (`<x-stepper>`) to visually guide users through multi-step forms, wizards, or sequential processes, supporting both horizontal/vertical layouts, step status states (pending, current, completed, error), and fully accessible descriptions.

## Dependencies
**Depends On:**
- U0.5 (Layout Templates)

## Required Deliverables
- `<x-stepper>` blade component (root container)
- `<x-stepper.step>` blade component (individual step item)
- Layout configurations (horizontal/vertical)
- Status states handling:
  - `completed` (renders a checkmark icon or custom content, styling with primary border/background)
  - `current` (styled as active focused step)
  - `pending` (styled as inactive muted step)
  - `error` (styled with danger border/text indicating issues)
- Fully accessible markup (ARIA step milestones, visual indicator screen-reader descriptions, and focusable elements where navigation is supported).

## Completed prerequisites
- U0.1 Design System & Tokens
- U0.5 Layout Templates
- U1.3.1 Tabs
- U1.3.2 Breadcrumb
- U1.3.3 Dropdown
