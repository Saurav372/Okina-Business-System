# Okina Craft - Version 1 Pivot Checklist
 
Whenever a major architectural change occurs (such as the V1 Business Pivot removing CRM and multi-staff management), previously completed tasks must be systematically verified to ensure they still connect to the new configuration.
 
## Configuration
- **Architecture Version:** V1 Business Pivot
- **Verification Initiated:** 2026-07-09
 
---
 
## Impact Assessment & Revalidation Flow
 
For each affected component:
1. **Identify Changes**: List what was removed or modified (e.g. CRM, leads, quotes, role-based checks).
2. **Search References**: Search the files for legacy structures.
3. **Establish New Standards**: Update DTOs, queries, controllers, and tests.
4. **Execute Verification**: Validate visually, run tests, and confirm compliance before marking `Completed`.
 
---
 
## Dashboard (U2.2) Verification Checklist
 
### [x] U2.2.1 Widgets (KPI Stat Cards)
- [x] Verify that no CRM widgets (Leads, Quotation Conversion, Pipeline stats) remain in dashboard metrics.
- [x] Confirm active widgets align with operational fields: Today's Orders, Pending Orders, Advance Payments Pending, Outstanding Balance, Low Stock, Today's Collections, and Purchase Orders.
- [x] Ensure DB queries retrieve correct, uncancelled orders and payments.
- [x] Check responsive behavior on mobile stacked grids.
 
### [x] U2.2.2 Recent Activity Timeline
- [x] Ensure that no CRM logs (`leads.created`, `quotations.converted`, etc.) populate the timeline or are referenced in activity filters.
- [x] Confirm initials fallback displays correctly for Japanese, Hindi, and system-deleted actors.
- [x] Verify timeline links keyboard focus outlines.
 
### [x] U2.2.3 Charts (Revenue & Orders)
- [x] Verify that the Quote Pipeline (Bar) chart is removed.
- [x] Confirm the new Monthly Orders (Bar) chart calculates and displays correctly alongside the Sales Revenue Trend (Line) chart.
- [x] Verify SVGs include dynamic point mapping (`ChartGeometryPresenter` & `ChartPathBuilder`).
- [x] Check accessibility attributes (`<title>`, `<desc>`, `tabindex="0"` on SVG coordinates).
 
### [x] U2.2.4 Quick Actions
- [x] Confirm all action links route to existing operational targets: Create Sales Order, Product Catalog, Stock adjustments, Expenses, and Ledgers.
- [x] Ensure no legacy links (e.g., Quotation Builder, Lead Capture) exist in Quick Actions panel.
 
---
 
## Verification Status Tracking
 
| Subtask ID | Task Name | Pivot Impact | Verification Steps | Current Status |
|---|---|---|---|---|
| **U2.2.1** | Widgets | Removed Leads/Quotes metrics. Added V1 operations. | Verify `DashboardService` widgets list. Check layout. | [x] Completed |
| **U2.2.2** | Recent Activity | Filter out crm/lead activity logs. | Verify log activity mapper. | [x] Completed |
| **U2.2.3** | Charts | Replaced Quote Pipeline with Monthly Orders. | Verify SVG rendering and cached queries. | [x] Completed |
| **U2.2.4** | Quick Actions | Removed Quotation Builder link. | Verify Quick Actions routing links. | [x] Completed |
