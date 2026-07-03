# UI Task List

## Status Definitions
- **Pending:** Not started
- **In Progress:** Currently being implemented
- **Review:** Implementation complete, awaiting validation
- **Blocked:** Cannot proceed due to dependency
- **Completed:** Validated and committed

## Parent Task Rule
Parent tasks are organizational milestones.
A parent task is marked Completed only when every child subtask is Completed.
Parent tasks are never implemented directly unless explicitly stated.

## Shared Design System Rule
All public-facing customer pages must use the shared Design System (Color, Typography, Spacing, Motion) to prevent the Astro frontend from drifting visually from the admin panel over time.

## SEO Rule
All public-facing customer pages must implement their SEO acceptance criteria during development.
SEO is not implemented during Release. Release only validates compliance.

## Task Fields
- Task ID
- Task Name
- Tier
- Phase
- Description & Acceptance
- Depends On
- Status

Subtasks use the parent task ID plus a sequence number. Example: `U0.1.1`.
Detailed validation for each subtask is maintained in `UI-SUBTASK-VALIDATION.md`.

## Tier 1: Foundation

| Task ID | Task Name | Tier | Phase | Description & Acceptance | Depends On | Status |
|---|---|---|---|---|---|---|
| **U0.1** | **Design System & Tokens** | Foundation | 0 | **Milestone** | None | Milestone |
| U0.1.1 | CSS Color Tokens | Foundation | 0 | Define semantic CSS color variables. | None | Completed |
| U0.1.2 | Typography Scale | Foundation | 0 | Define H1-H6, body, and caption sizes/weights. | U0.1.1 | Completed |
| U0.1.3 | Spacing Scale | Foundation | 0 | Define gap, padding, and margin scales. | U0.1.2 | Completed |
| U0.1.4 | Breakpoints | Foundation | 0 | Define a mobile-first responsive breakpoint system (Base, xs, sm, md, lg, xl, 2xl, 3xl). | U0.1.3 | Completed |
| U0.1.5 | Motion & Animation Tokens | Foundation | 0 | Define motion durations, easings, and transitions. <br> **Acceptance:** Duration scale, Easing scale, Transition tokens, Focus animations, Hover animations, Reduced motion support defined and documented. | U0.1.4 | Pending |
| U0.2 | Admin Route Groups | Foundation | 0.5 | Setup admin route groups and auth/permission middleware. | U0.1.5 | Pending |
| U0.3 | Navigation Config | Foundation | 0.5 | Centralize sidebar links and role-based visibility rules. | U0.2 | Pending |
| U0.4 | Breadcrumb Builder | Foundation | 0.5 | Automated breadcrumb generation strategy. | U0.2 | Pending |
| U0.5 | Layout Templates | Foundation | 0.5 | Create app, admin, customer, auth, and guest blade layouts. | U0.1.5 | Pending |
| **U1.1** | **Form Components** | Foundation | 1 | **Milestone** | U0.5 | Milestone |
| U1.1.1 | Form Wrapper | Foundation | 1 | Form wrapper component. | U0.5 | Pending |
| U1.1.2 | Input Field | Foundation | 1 | Standard text/number input. | U1.1.1 | Pending |
| U1.1.3 | Select Dropdown | Foundation | 1 | Standard select input. | U1.1.1 | Pending |
| U1.1.4 | SearchBox | Foundation | 1 | Search input with debounce. | U1.1.1 | Pending |
| U1.1.5 | DatePicker | Foundation | 1 | Date selection component. | U1.1.1 | Pending |
| U1.1.6 | FileUpload | Foundation | 1 | File upload with preview. | U1.1.1 | Pending |
| **U1.2** | **Data & Table** | Foundation | 1 | **Milestone** | U0.5 | Milestone |
| U1.2.1 | DataTable | Foundation | 1 | Table with sorting/columns. | U0.5 | Pending |
| U1.2.2 | Pagination | Foundation | 1 | Server-side pagination controls. | U1.2.1 | Pending |
| U1.2.3 | Timeline | Foundation | 1 | Chronological event list. | U0.5 | Pending |
| U1.2.4 | StatsGrid | Foundation | 1 | Grid for key metrics. | U0.5 | Pending |
| U1.2.5 | EmptyState | Foundation | 1 | Placeholder for empty data. | U0.5 | Pending |
| **U1.3** | **Navigation** | Foundation | 1 | **Milestone** | U0.5 | Milestone |
| U1.3.1 | Tabs | Foundation | 1 | Tabbed navigation. | U0.5 | Pending |
| U1.3.2 | Breadcrumb | Foundation | 1 | Hierarchical links. | U0.5 | Pending |
| U1.3.3 | Dropdown | Foundation | 1 | Context menu dropdown. | U0.5 | Pending |
| U1.3.4 | Stepper | Foundation | 1 | Multi-step wizard indicator. | U0.5 | Pending |
| **U1.4** | **Feedback & Overlay** | Foundation | 1 | **Milestone** | U0.5 | Milestone |
| U1.4.1 | Modal | Foundation | 1 | Dialog overlay. | U0.5 | Pending |
| U1.4.2 | Drawer | Foundation | 1 | Side panel overlay. | U0.5 | Pending |
| U1.4.3 | Toast / Alert | Foundation | 1 | Flash notifications. | U0.5 | Pending |
| U1.4.4 | SkeletonLoader | Foundation | 1 | Placeholder loading state. | U0.5 | Pending |
| **U1.5** | **Utility & Media** | Foundation | 1 | **Milestone** | U0.5 | Milestone |
| U1.5.1 | Button & Badge | Foundation | 1 | Buttons and status badges. | U0.5 | Pending |
| U1.5.2 | Avatar | Foundation | 1 | User profile image. | U0.5 | Pending |
| U1.5.3 | FileCard & Preview | Foundation | 1 | Media display. | U0.5 | Pending |
| **U1.6** | **Motion & Feedback** | Foundation | 1 | **Milestone** | U0.1.5, U0.5 | Milestone |
| U1.6.1 | Transition Utilities | Foundation | 1 | Reusable transition wrappers. | U0.1.5, U0.5 | Pending |
| U1.6.2 | Loading Indicators | Foundation | 1 | Spinners and loading states. | U0.1.5, U0.5 | Pending |
| U1.6.3 | Progress Indicators | Foundation | 1 | Progress bars. | U0.1.5, U0.5 | Pending |
| U1.6.4 | Skeleton Animation | Foundation | 1 | Shimmer effects for skeletons. | U0.1.5, U0.5 | Pending |
| U1.6.5 | Scroll Animations | Foundation | 1 | Scroll-triggered reveals. | U0.1.5, U0.5 | Pending |
| U1.6.6 | Page Transitions | Foundation | 1 | View transition API integration. | U0.1.5, U0.5 | Pending |
| U1.7 | UI Component Showcase | Foundation | 1.5 | `/admin/ui` route to display and document all reusable components, design tokens, and motion patterns. | U1.1, U1.2, U1.3, U1.4, U1.5, U1.6 | Pending |
| U2.1 | Application Shell | Foundation | 2 | Wire up Sidebar and Topbar with dynamic user session and navigation data. | U0.3, U0.5, U1.3 | Pending |
| **U2.2** | **Dashboard** | Foundation | 2 | **Milestone** | U2.1 | Milestone |
| U2.2.1 | Widgets | Foundation | 2 | KPI stat cards. | U2.1, U1.2.4, U1.6 | Pending |
| U2.2.2 | Recent Activity | Foundation | 2 | Timeline of recent system events. | U2.1, U1.2.3 | Pending |
| U2.2.3 | Charts | Foundation | 2 | Data visualizations. | U2.1, U1.6 | Pending |
| U2.2.4 | Quick Actions | Foundation | 2 | Common action buttons. | U2.1, U1.5.1 | Pending |
| U2.2.5 | Notification Center | Foundation | 2 | Alerts and messages panel. | U2.1, U1.4.3 | Pending |

## Tier 2: Admin Modules

| Task ID | Task Name | Tier | Phase | Description & Acceptance | Depends On | Status |
|---|---|---|---|---|---|---|
| **U3.1** | **Orders Module** | Admin | 3 | **Milestone** | U2.1 | Milestone |
| U3.1.1 | Order Index | Admin | 3 | Main listing with filters and bulk actions. | U2.1, U1.2.1 | Pending |
| U3.1.2 | Order Detail | Admin | 3 | Detail view with customer, total, and status cards. | U3.1.1 | Pending |
| U3.1.3 | Order Timeline | Admin | 3 | Chronological history of order changes. | U3.1.2 | Pending |
| U3.1.4 | Order Files | Admin | 3 | Uploads and mockup preview panel. | U3.1.2 | Pending |
| U3.1.5 | Order Shipping | Admin | 3 | Shipping tracking and status updates. | U3.1.2 | Pending |
| U3.1.6 | Order Invoice | Admin | 3 | Invoice generation and PDF preview. | U3.1.2 | Pending |
| **U3.2** | **Products Module** | Admin | 3 | **Milestone** | U2.1 | Milestone |
| U3.2.1 | Product Index | Admin | 3 | Main listing with status filters. | U2.1, U1.2.1 | Pending |
| U3.2.2 | Product Detail & Edit | Admin | 3 | Core product edit form and metadata. | U3.2.1 | Pending |
| U3.2.3 | Categories & Attributes | Admin | 3 | Category assignment and custom attributes. | U3.2.2 | Pending |
| U3.2.4 | Variants & SKUs | Admin | 3 | Matrix generation and SKU pricing/stock. | U3.2.2 | Pending |
| U3.2.5 | Product Media | Admin | 3 | Image gallery and video management. | U3.2.2 | Pending |
| U3.2.6 | SEO Metadata Management | Admin | 3 | Meta title, description, canonical, OG image, slug preview. | U3.2.2 | Pending |
| **U4.1** | **CRM Module** | Admin | 4 | **Milestone** | U2.1 | Milestone |
| U4.1.1 | Leads Index | Admin | 4 | Lead capture listing. | U2.1, U1.2.1 | Pending |
| U4.1.2 | Lead 360 View | Admin | 4 | Lead detail, activity logging, and follow-ups. | U4.1.1 | Pending |
| U4.1.3 | Quotation Builder | Admin | 4 | Quote creation and pricing engine. | U4.1.2, U3.2.4 | Pending |
| U4.1.4 | Quotation Conversion | Admin | 4 | Conversion from Quote to Sales Order. | U4.1.3, U3.1.6 | Pending |
| **U5.1** | **Inventory & Purchasing** | Admin | 5 | **Milestone** | U3.2.4 | Milestone |
| U5.1.1 | Stock Balances | Admin | 5 | Real-time SKU stock levels. | U3.2.4 | Pending |
| U5.1.2 | Stock Movements | Admin | 5 | Movement history and adjustment modal. | U5.1.1 | Pending |
| U5.1.3 | Vendors Directory | Admin | 5 | Vendor management. | U2.1 | Pending |
| U5.1.4 | Purchase Orders | Admin | 5 | PO creation and Stock Receiving flow. | U5.1.3, U5.1.1 | Pending |
| **U6.1** | **Finance & Reports** | Admin | 6 | **Milestone** | U3.1.6 | Milestone |
| U6.1.1 | Payments Ledger | Admin | 6 | History of all order payments. | U3.1.6 | Pending |
| U6.1.2 | Refunds Workflow | Admin | 6 | Refund processing and status tracking. | U6.1.1 | Pending |
| U6.1.3 | Expenses Reporting | Admin | 6 | Expense capture. | U2.1 | Pending |
| U6.1.4 | Finance Reports | Admin | 6 | Sales, Revenue, and Outstanding Balance visualizations. | U6.1.1 | Pending |
| **U7.1** | **System Administration** | Admin | 7 | **Milestone** | U2.1 | Milestone |
| U7.1.1 | Access Control | Admin | 7 | Users list, Roles, and Permissions screens. | U2.1 | Pending |
| U7.1.2 | Business Settings | Admin | 7 | System settings and API keys. | U2.1 | Pending |
| U7.1.3 | System Logs | Admin | 7 | Audit Logs, Notification Logs, System Health. | U2.1 | Pending |

## Tier 3: Customer Experience

| Task ID | Task Name | Tier | Phase | Description & Acceptance | Depends On | Status |
|---|---|---|---|---|---|---|
| **U8.1** | **Core Storefront** | Customer | 8 | **Milestone** | U3.2.4 | Milestone |
| U8.1.1 | Homepage | Customer | 8 | Landing page with featured products. <br> **Acceptance:** Responsive, Accessibility, Title, Description, Organization Schema, Website Schema, Open Graph, Canonical, Breadcrumb, Performance, Analytics Ready, Internal Links (Featured Categories, Related). | U3.2.4 | Pending |
| U8.1.2 | Categories | Customer | 8 | Category listing. <br> **Acceptance:** Responsive, Accessibility, Category Title, Category Description, Collection Schema, Canonical, Breadcrumb, Pagination SEO, Performance, Analytics Ready. | U3.2.4 | Pending |
| U8.1.3 | Product List | Customer | 8 | Listing with filters/pagination. <br> **Acceptance:** Responsive, Accessibility, Title, Meta Description, Canonical, Breadcrumb, Pagination SEO, Collection Schema, Open Graph, Performance, Analytics Ready. | U3.2.4 | Pending |
| U8.1.4 | Product Detail | Customer | 8 | Individual product view and selection. <br> **Acceptance:** Responsive, Accessible, Product Schema, Breadcrumb Schema, Canonical, Open Graph, Twitter Card, Image Alt, Internal Links (Related), Reviews, Availability, Analytics Ready. | U3.2.4 | Pending |
| U8.1.5 | Cart | Customer | 8 | Shopping cart review. <br> **Acceptance:** Responsive, Accessible, Noindex, Nofollow, Analytics Ready. | U8.1.4 | Pending |
| U8.1.6 | Checkout | Customer | 8 | Order placement and payment flow. <br> **Acceptance:** Responsive, Accessible, Noindex, Robots, Canonical, Analytics Ready. | U8.1.5 | Pending |
| **U8.2** | **Customization Workflow** | Customer | 8 | **Milestone** | U8.1.6 | Milestone |
| U8.2.1 | Product Options | Customer | 8 | Customization selections (color, size, etc). | U8.1.6 | Pending |
| U8.2.2 | Design Upload | Customer | 8 | Customer design upload UI. | U8.2.1 | Pending |
| U8.2.3 | Preview | Customer | 8 | Real-time mockup preview. | U8.2.2 | Pending |
| U8.2.4 | Approval | Customer | 8 | Customer proof approval. | U8.2.3 | Pending |
| U8.2.5 | Cart Integration | Customer | 8 | Adding customized item to cart. | U8.2.4 | Pending |
| U8.3 | Customer Authentication | Customer | 8 | Branded Login, Register, Forgot Password. (Astro consuming Laravel APIs). <br> **Acceptance:** Responsive, Accessible, Noindex, Canonical, Analytics Ready. | None | Pending |
| U8.4 | Customer Portal | Customer | 8 | Customer dashboard, tracking, addresses. <br> **Acceptance:** Responsive, Accessible, Noindex, Nofollow (if appropriate), Private routes, Robots. | U8.1.6, U8.3 | Pending |

## Tier 4: Release

| Task ID | Task Name | Tier | Phase | Description & Acceptance | Depends On | Status |
|---|---|---|---|---|---|---|
| U9.1 | UI Polish & QA | Release | 9 | Verify all implemented motion, timings, easing, and transitions match the design system, plus visual bug fixes. | All Tasks | Pending |
| U9.2 | SEO Validation | Release | 9 | Validate Schema, Canonical, Meta Tags, and internal links for public pages. | U9.1 | Pending |
| U9.2.1| Search Console Readiness | Release | 9 | Check robots.txt, sitemap.xml, canonical, 404, redirects, structured data, hreflang, feed validation. | U9.2 | Pending |
| U9.3 | Accessibility Audit | Release | 9 | ARIA, contrast, and keyboard navigation review. | U9.2.1 | Pending |
| U9.4 | Performance Profiling | Release | 9 | Lighthouse score optimization and Core Web Vitals check. | U9.3 | Pending |
| U9.5 | Cross-Browser QA | Release | 9 | Safari, Edge, Firefox, Mobile Chrome validation. | U9.4 | Pending |
