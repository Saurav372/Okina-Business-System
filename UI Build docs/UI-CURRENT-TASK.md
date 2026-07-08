# Current Task

## Current Parent Task
Motion & Feedback (U1.6)

## Current Subtask
U1.6.2 Loading Indicators

## Goal
Create reusable loading spinner and loader components complying with the design system's spacing, color, and motion parameters. These components should support inline loads, button embeds, relative containers, and full-viewport overlays.

## Dependencies
**Depends On:**
- U0.1.5 (Motion & Animation Tokens)
- U0.5 Layout Templates

## Required Deliverables
- Reusable Blade component for spinners (`<x-spinner>` or `<x-loading.spinner>`) supporting sizes (`xs`, `sm`, `md`, `lg`, `xl`), thickness, and intents (primary, secondary, success, danger, warning, white, neutral).
- Relative layout loader overlay (`<x-loading.overlay>` or relative wrapper) with standard backdrop blur and accessibility descriptors (`aria-busy="true"`, `aria-live="polite"`).
- Integration and interactive variations showcased inside the components playground at `/admin/components#loading-indicators`.

## Completed prerequisites
- U0.1 Design System & Tokens
- U0.5 Layout Templates
- U1.1 Form Components
- U1.2 Data & Table
- U1.3 Navigation
- U1.4 Feedback & Overlay
- U1.5 Utility & Media
- U1.6.1 Transition Utilities
