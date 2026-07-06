# Current Task

## Current Parent Task
Feedback & Overlay (U1.4)

## Current Subtask
U1.4.4 SkeletonLoader

## Goal
Build a customizable, fluid placeholder SkeletonLoader system (`<x-skeleton>` or similar) with pulse/shimmer animators, shape configurations (lines, circles, buttons, grids), size modifiers, and pre-built composite placeholders (card layouts, list feeds, text summaries) representing loading UI shapes for async data elements.

## Dependencies
**Depends On:**
- U0.5 (Layout Templates)

## Required Deliverables
- Reusable Skeleton component supporting custom shapes (text line, circle avatar, rectangle card, custom grid block).
- Smooth CSS shimmer and pulsing animation tokens.
- Layout configurations supporting composite loaders (e.g. Card loaders with circular profile pic and two textual lines, table loaders with rows).
- Accessibility attributes masking placeholder layouts (`aria-hidden="true"`, `role="presentation"`).
- Showcase integration in the components playground at `/admin/components#skeleton`.

## Completed prerequisites
- U0.1 Design System & Tokens
- U0.5 Layout Templates
- U1.3 Navigation Milestones (Tabs, Breadcrumb, Dropdown, Stepper)
- U1.4.1 Modal
- U1.4.2 Drawer
- U1.4.3 Toast / Alert
