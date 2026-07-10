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

## Orders Module (U3.1) Verification Checklist

### [x] U3.1.2 Order Detail
- [x] Confirm no CRM fields (lead ID, quotation reference) exist in the Order Detail page or API response.
- [x] Verify customer data comes from `customer_snapshot` (stored at order time), not from a live CRM contact.
- [x] Confirm `design_status` and `design_issue_message` flow correctly without any lead/quote dependency.
- [x] Verify Reorder action calls `POST /api/customer/orders/{id}/reorder`, not a quotation route.
- [x] Confirm `OrderDetailCatalog::blocked_actions` excludes `create`, `edit`, `delete` (read-only V1 posture).

### [x] U3.1.3 Order Timeline
- [x] Verify `AuditLog` query filters by `subject_type = 'order'` only — no `lead`, `quotation`, or `crm_event` types.
- [x] Confirm no legacy activity mapper references `leads.created` or `quotations.converted`.

### [x] U3.1.4 Order Files
- [x] Verify design file routes are scoped to `order` context — no quotation or lead file bridge.
- [x] Confirm `AdminOrderDesignFileController` uses order-level policy gate.

### [x] U3.1.5 Order Shipping
- [x] Verify `AdminOrderActionController@updateShipping` operates directly on the Order model.
- [x] Confirm no quote-conversion flow triggers shipping assignment.

### [x] U3.1.6 Order PDF / Confirmation
- [x] Confirm PDF renders from `SalesOrderController` — no quotation PDF path.
- [x] Verify audit event dispatched on download.

### [ ] U3.1.1 Order Index *(Pending — current build target)*
- [ ] To be verified after implementation. Must have no CRM filter tabs (no Lead Source, no Quotation Status).
- [ ] Confirm status filters align with V1 order statuses only: pending, confirmed, in_production, ready_to_ship, shipped, delivered, cancelled.

---
 
## Verification Status Tracking
 
| Subtask ID | Task Name | Pivot Impact | Verification Steps | Current Status |
|---|---|---|---|---|
| **U2.2.1** | Widgets | Removed Leads/Quotes metrics. Added V1 operations. | Verify `DashboardService` widgets list. Check layout. | [x] Completed |
| **U2.2.2** | Recent Activity | Filter out crm/lead activity logs. | Verify log activity mapper. | [x] Completed |
| **U2.2.3** | Charts | Replaced Quote Pipeline with Monthly Orders. | Verify SVG rendering and cached queries. | [x] Completed |
| **U2.2.4** | Quick Actions | Removed Quotation Builder link. | Verify Quick Actions routing links. | [x] Completed |
| **U3.1.1** | Order Index | Admin listing — built and verified. | Confirm no CRM scope tabs or quotation filters present. | [x] Completed |
| **U3.1.2** | Order Detail | No CRM/lead references. Snapshot-only customer data. | Verify page, API route, and data flow. | [x] Completed |
| **U3.1.3** | Order Timeline | Audit logs contain no CRM event types. | Verify AuditLog query filter. | [x] Completed |
| **U3.1.4** | Order Files | Design files decoupled from lead/quote context. | Verify file routes and policy gates. | [x] Completed |
| **U3.1.5** | Order Shipping | No quote conversion dependency. | Verify shipping update route and tracking card. | [x] Completed |
| **U3.1.6** | Order PDF | No quotation-to-PDF path. Uses SalesOrder only. | Verify PDF routes and audit dispatch. | [x] Completed |
| **U3.1.1** | Order Index | Admin listing — not yet built. | Implement before marking module complete. | [ ] Pending |

