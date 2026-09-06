# Make the admin dashboard actionable

Written against: 7f30093faef347830491c4360571471f75468892

## Evidence chain

- Surface: `/admin`, `routes/web.php` → `AdminDashboardController` → `DashboardService` → `resources/views/admin/dashboard.blade.php`.
- Design sources: user-supplied dashboard screenshot and explicit requested improvements. Preserve the dark sidebar, red brand accents, white cards, existing typography, and restrained borders.
- Governing owners: `resources/views/components/stat/card.blade.php`, `components/stat/grid.blade.php`, `components/layouts/admin.blade.php`; chart presentation is inline in the dashboard, using `ChartGeometryPresenter` and `ChartPathBuilder`.
- Explicit exceptions: None documented.
- Verified contradictions: DashboardService emits unconditional “action required” and “up” for zero metrics; chart loops draw gridlines without their available tick labels; ActivityMapper supplies generic category titles and fallback descriptions.
- Cards already render as links. Today's Orders and Pending Orders currently lead to creation; Low Stock leads to sync logs. Correct destinations are part of the user's requested action-card behavior.
- Uncertainty: overdue business rules, authoritative receivables reconciliation, and list filter support need further implementation-time tracing. Example numbers in the brief are illustrative only.

## Design decision

Prioritize operational work and trustworthy explanations while retaining the existing dashboard identity. Deliver the changes in the following sequence; financial labels must follow verified data definitions.

## Reuse

- Extend existing DashboardWidgetDTO and stat card, rather than introducing a second card system.
- Reuse chart series/point DTOs, ChartGeometryPresenter tick calculations, ChartPathBuilder, Alpine interactions, and existing semantic color variables.
- Reuse existing navigation groups and their active route matching.

## Changes

1. Accurate KPI states and action destinations.
   - Owners: DashboardService, DashboardWidgetDTO, stat/card, dashboard view.
   - Separate status text/tone from comparative trend data. Never expose internal state names.
   - Zero advances: “No payments awaiting deposit”; zero collections: “No collections today”; zero stock alerts: “All items sufficiently stocked”. No comparison arrows without a named baseline.
   - Use stable widget keys for grouping; current mobile grouping depends on display labels and will break on renaming.
   - Top row: Open Orders, Outstanding Receivables, Payments Awaiting Advance, Orders Today. Second row: Ready to Dispatch, Low Stock Items, Collections Today, Active Purchase Orders.
   - Derive Ready to Dispatch from the existing ReadyToShip status. Derive order breakdowns from all relevant open statuses, including confirmed and shipped; do not imply every open order is stuck.
   - Define needs-attention rules before presenting an aggregate action count; deduplicate overlapping conditions.
   - Trace and reconcile receivables with the customer ledger before adding overdue/upcoming amounts. Missing due dates are unclassified, not upcoming. Omit unsupported overdue claims.
   - Keep cards as a single link with visible action text. Link to verified filtered lists matching the metric, respecting permissions. Add filter support only where necessary to fulfill the promised destination.
   - Reduce excessive internal card spacing using existing spacing tokens, strengthen secondary text/icon contrast, emphasize actual urgency with amber/red. Keep zero states quiet.

2. Interpretable charts.
   - Owners: DashboardService chart methods, ChartSeriesDTO/ChartPointDTO, ChartGeometryPresenter, both dashboard responsive branches.
   - Render existing tick labels with Indian currency abbreviations for monetary scales and integer counts for orders. Display order values above bars.
   - Add selected-period total, exact date range, and full month/year tooltip labels. Revenue tooltip includes the matching month's order count.
   - Current revenue sums non-cancelled order totals; label this “Order value” unless a verified revenue definition supports the Revenue label. Do not silently replace the calculation with collections.
   - Default to six calendar months, marking the current month “through [current date]” and styling its point/bar as partial.
   - Compare equivalent elapsed windows, explicitly label dates, and calculate totals for both windows. Do not compare a partial month to a complete month. A zero prior baseline produces “No comparable prior value”, not 0% growth.
   - Improve existing tooltips for responsive scaling, touch, keyboard focus, and edge clipping. Preserve usable empty states; zero bars must not imply positive orders.
   - Add 3/6/12 month selection only with complete queries, caching, totals, comparison, and labels for each option.

3. Specific recent activity.
   - Owners: DashboardService::getRecentActivity, ActivityMapper, ActivityItemDTO, dashboard activity views.
   - Trace real audit producers and fields before mapping exact actions to readable titles, references, amounts, actor, and time.
   - Use recorded data for “Order #[reference] created”, “[amount] received”, and “Stock adjusted”. Align the event allowlist with supported mappings; inventory currently has a mapper branch but is absent from the allowlist.
   - Link only to existing records the viewer may access. Preserve meaningful historical summaries when structured detail is unavailable; do not invent success or transaction amounts.

4. Sidebar and responsive consistency, as a separate final implementation slice.
   - Owner: components/layouts/admin.blade.php, affecting every admin page.
   - Make existing section headings expandable controls, preserve each user's expansion preference, and automatically expose the current page's group.
   - Retain existing module membership; do not add hypothetical quotations/returns routes or move unrelated accounting modules under Payments.
   - Strengthen active indication with a marker and text weight using existing brand tokens. Preserve compact sidebar and mobile drawer behavior.
   - Apply the same metric names, status rules, and chart semantics on desktop and mobile; ensure urgent metrics are not hidden under More Metrics.

## Scope

- Inherit: dashboard desktop/mobile views and current shared card consumers; inspect other consumers before changing shared defaults.
- Verify: filtered destination pages, role restrictions, sidebar across admin routes, cache freshness after order/payment/stock changes.
- Exclude: storefront redesign, new invoice workflows, invented statuses, sample business values, and a replacement chart library without demonstrated need.

## Validation

- Product: an admin can identify actionable orders, money due, and dispatch work, then open the matching records.
- Interface: verify zero, healthy, urgent, large-value, long-label, partial-month, and unavailable-comparison states at mobile, tablet, and desktop widths.
- Data: test aggregates and list counts, date boundaries, zero comparison baselines, non-overlapping status totals, real event mapping, and permission-sensitive links.
- Repository: run `php artisan test` and `npm run build`; run `./vendor/bin/pint --test` and `./vendor/bin/phpstan analyse` according to repository requirements. Distinguish pre-existing failures.
- Render and inspect the implemented page; confirm tooltips stay inside chart bounds and mobile wording matches desktop.

## Stop conditions

- If due-date or accounting definitions cannot be established, defer overdue breakdowns while completing the supported improvements.
- If a proposed card destination or sidebar item does not exist, resolve scope before advertising it.
- Do not treat illustrative figures as application data.

## Design documentation

- After acceptance and validation, record the final metric definitions, priority ordering, zero-state copy, comparison-window rules, and navigation behavior in the project's applicable admin documentation.
