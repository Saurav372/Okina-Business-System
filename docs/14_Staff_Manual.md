# Staff Manual

> **Last Reviewed:** 2026-07-02
> **Owner:** Operations / Training
> **Source of Truth:** Live admin application

---

## Overview

This manual is for staff who use the Okina Business System admin panel. It covers the day-to-day tasks each role performs.

For technical details, see the [System Architecture](./03_System_Architecture.md) and [Module Documentation](./09_Module_Documentation.md).

---

## Accessing the Admin Panel

1. Open your browser and navigate to your admin URL (e.g. `https://admin.okinacraft.com`).
2. Enter your staff email address and password.
3. On successful login, you will see the admin dashboard.

If you cannot log in, contact your Super Admin to verify your account is active and your role is correctly assigned.

---

## Roles and What You Can Do

| Role | Main Responsibilities |
|---|---|
| **Super Admin** | Everything — users, settings, audit logs |
| **Admin** | Orders, payments, CRM, inventory, vendors |
| **Sales Staff** | Leads, quotations, sales orders |
| **Inventory Staff** | Stock management, purchase orders, vendor receiving |
| **Finance Staff** | Payments, refunds, expenses, financial reports, audit logs |
| **Production Staff** | Order status updates (production and shipping stages only) |

---

## Order Management

### Viewing Orders
- Navigate to **Orders** in the sidebar.
- Use filters to narrow by status, date range, payment status, or search by order number or customer name.
- Click any order to open its detail view showing items, payment summary, outstanding balance, and status history.

### Updating Order Status
1. Open the order detail.
2. Click **Update Status**.
3. Select the new status from the dropdown.
4. Confirm the update.

Valid status transitions:
```
Pending Payment → Confirmed → In Production → Ready to Ship → Shipped → Delivered
Any status → Cancelled (if not already delivered or refunded)
```

### Recording Design Approval
1. Open the order detail.
2. Click **Record Design Approval**.
3. Toggle approved/not approved, add notes if needed, and save.

### Adding Shipping Details
1. Open the order detail.
2. Click **Add Shipping Details**.
3. Enter courier name, tracking number, tracking URL, shipping date, and estimated delivery date.
4. Save — the customer's tracking view will update automatically.

---

## Creating a Manual Sales Order

1. Navigate to **Orders → Create Sales Order**.
2. Search for or select an existing customer (or create a new one).
3. Add order items by selecting SKUs and quantities.
4. Save the order — it will be created in **Pending Payment** status.
5. Record an advance payment using **Record Payment** on the order detail.

---

## CRM — Leads and Quotations

### Creating a Lead
1. Navigate to **CRM → Leads → Create Lead**.
2. Enter the prospect's name, contact details, source, and initial notes.
3. Assign the lead to a sales staff member.

### Adding a Follow-up
1. Open the lead detail.
2. Click **Add Follow-up**.
3. Enter the follow-up date, type (call, email, WhatsApp), and notes.

### Creating a Quotation
1. Open a lead.
2. Click **Create Quotation**.
3. Add quotation items with SKU, quantity, and agreed price.
4. Save the quotation.

### Sending a Quotation for Customer Approval
1. Open the quotation.
2. Click **Send to Customer** — this emails the customer an approval link.
3. The customer can approve or reject via the link.
4. Once approved, click **Convert to Sales Order**.

---

## Finance — Payments and Refunds

### Viewing the Payment Ledger
- Navigate to **Finance → Payments**.
- Use filters: date range, payment method, status, provider.
- The ledger shows all recorded payments with totals.

### Recording a Manual Payment
1. Open the order detail.
2. Click **Record Payment**.
3. Enter the amount, method (bank transfer, cash, cheque), receipt number, and date.
4. Save — the order's payment status updates automatically.

### Requesting a Refund
1. Navigate to **Finance → Refunds → Request Refund**.
2. Select the payment to refund.
3. Choose full or partial refund and enter the amount.
4. Submit — the refund goes to **Requested** status.

### Approving a Refund (Finance Staff only)
1. Navigate to **Finance → Refunds**.
2. Find the refund in **Requested** status.
3. Click **Approve** to move it to processing.

---

## Expenses

### Logging an Expense
1. Navigate to **Finance → Expenses → Add Expense**.
2. Select the category, enter the amount (in ₹), date, and description.
3. Save — the expense starts in **Draft** status.

### Submitting for Approval
1. Open the expense.
2. Click **Submit for Approval**.

### Approving an Expense (Finance Staff only)
1. Navigate to **Finance → Expenses**.
2. Find expenses in **Submitted** status.
3. Click **Approve** or **Reject** with a reason.

---

## Inventory Management

### Viewing Stock Balances
- Navigate to **Inventory**.
- The table shows available, reserved, and on-hand quantities per SKU.

### Recording Stock-In
1. Navigate to **Inventory → Stock-In**.
2. Select the SKU, enter the quantity and reason.
3. Save — an inventory movement is recorded.

### Recording Stock-Out or Manual Adjustment
- Follow the same process under **Stock-Out** or **Manual Adjustment**.
- Manual adjustments require a reason.

---

## Vendors and Purchase Orders

### Creating a Vendor
1. Navigate to **Vendors → Create Vendor**.
2. Enter vendor name, contact details, and GSTIN.

### Creating a Purchase Order
1. Navigate to **Purchase Orders → Create**.
2. Select the vendor, add items with expected quantities and prices.

### Receiving Stock
1. Open the purchase order.
2. On each item that has arrived, click **Receive Stock** and enter the received quantity.
3. The inventory balance updates automatically.

---

## Financial Reports

1. Navigate to **Finance → Reports**.
2. Set the date range and filters.
3. Choose the grouping: by category or by month.
4. The report shows sales, payments, refunds, expenses, and outstanding balances for the period.

---

## Audit Logs (Finance Staff and Super Admin)

1. Navigate to **Audit Logs**.
2. Filter by action type, module, or date range.
3. Click any entry to see the full before/after values and related records.

Audit records cannot be edited or deleted.

---

## Notification Logs

1. Navigate to **Notifications → Logs**.
2. Filter by channel, status, or recipient.
3. For failed notifications, click **Retry** to attempt re-delivery.

---

## Settings (Super Admin)

1. Navigate to **Settings**.
2. Update business configuration (payment credentials, notification settings, Google Sheets credentials, upload limits).
3. Use **Test Google Sheets Connection** to verify Sheets integration after changing credentials.
