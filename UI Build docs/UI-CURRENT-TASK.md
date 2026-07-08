# Current Task

## Current Parent Task
Application Shell (U2.1)

## Current Subtask
U2.1 Application Shell

## Goal
Wire up the Sidebar and Topbar layouts with dynamic user session metadata, active route tracking, and role-based navigation menus generated from the centralized navigation configuration.

## Dependencies
**Depends On:**
- U0.3 (Navigation Config)
- U0.5 (Layout Templates)
- U1.3 (Navigation components)

## Required Deliverables
- Dynamic user session details (User name, role badge, profile avatar link) displayed in the Topbar.
- Dynamic sidebar navigation lists populated using `config('navigation.sidebar')` with support for role authorization checks.
- Active navigation item visual highlights using semantic primary/brand active states.
- Sidebar expand/collapse toggle action state synced with Alpine.js or cookie session storage.

## Completed prerequisites
- U0.1 Design System & Tokens (U0.1.1 to U0.1.6)
- U0.5 Layout Templates
- U1.1 Form Components
- U1.2 Data & Table
- U1.3 Navigation
- U1.4 Feedback & Overlay
- U1.5 Utility & Media
- U1.6 Motion & Feedback
- U1.7 UI Component Showcase
