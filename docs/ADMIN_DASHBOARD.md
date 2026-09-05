# Admin dashboard

The dashboard keeps operational queues ahead of reporting. Desktop and mobile share the same cards and definitions. Open Orders and Outstanding Receivables appear first, followed by advances and today's orders, then dispatch, inventory, collections, and active purchases.

## Metric definitions

- **Open Orders:** all orders except delivered, cancelled, or refunded. The description lists actual workflow statuses. Pending payment and ready to ship are concrete queues; the dashboard does not infer that an order is late or stuck.
- **Outstanding Receivables:** the existing finance report calculation, using receivable-eligible orders and successful payments/refunds through the current time, with each order's balance clamped at zero. It links to the finance report because the customer ledger currently uses a different aggregate formula. Due-date aging and overdue/upcoming splits remain unavailable pending a defined due-date source.
- **Payments Awaiting Advance:** non-cancelled/non-refunded orders whose expected advance exceeds successful payments, including the existing legacy payment-schedule fallback. Its destination uses the same selection rule.
- **Orders Today:** orders placed today, including cancellations, matching the order list's placed-date filters.
- **Ready to Dispatch:** orders with `ready_to_ship` status.
- **Low Stock Items:** Stock Balances records whose available quantity is at/below the resolved threshold, or whose on-hand quantity is negative. Includes depleted stock. Uses the same `needs_attention` filter on the destination. Missing inventory records are not treated as healthy stock.
- **Collections Today:** successful payments with `paid_at` today, independent of when the record was created. The payment list preserves the `paid_on` filter.
- **Active Purchase Orders:** draft, ordered, or partially received purchases. Received, closed, and cancelled purchases are excluded. The purchase list preserves `scope=active`.

Operational figures and activity are read on each page request. Actions are only offered when the viewer has permission for their destination. Zero values have natural, non-alarming descriptions; trend arrows require a real comparison. Currency uses Indian grouping, with exact amounts in chart details and finance records.

## Charts

The Order Value chart sums non-cancelled order totals by placed month. It is not cash collections or recognized revenue. Monthly Orders uses the same date range and cancellation exclusion.

The selector supports 3, 6, and 12 calendar months including the current partial month. Each chart shows its total, exact dates, monetary/integer scales, monthly details, and full-date tooltips available through hover, focus, and touch. Current-month data has an explicit through-date label and differentiated line/bar styling.

Comparison windows have the same elapsed length. The prior window ends on the day before the selected period starts, at the same time of day as the current cutoff. Its dates are shown beside the percentage. When the prior value is zero, no growth percentage is claimed. Calendar month generation starts from day one, avoiding month-end overflow.

## Activity and navigation

The feed recognizes both persisted audit action names and legacy aliases. Future events retain a small explicit set of identifiers and amounts for readable activity, without copying arbitrary payloads. Existing summaries are preserved. Legacy vendor events without a reference or summary are omitted from the dashboard feed; the audit log is unchanged. Missing/deleted/unauthorized targets do not get links.

Sidebar groups preserve expansion choices per user. The current page's group opens on navigation. Existing module membership, the compact sidebar, and mobile drawer remain in use.
