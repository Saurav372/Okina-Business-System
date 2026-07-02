# Okina Craft Business System - Regression Test Checklist & Release Gate

This document serves as the official regression gate for the Okina Craft Business System. It defines the validation procedures, priority matrix, test mappings, and release criteria that must be satisfied before deploying any updates to a production environment.

> [!IMPORTANT]
> **Deployment Relationship**: This checklist is designed to be executed immediately after following the [DEPLOYMENT-CHECKLIST.md](file:///e:/Saurav/Okina%20Business%20System/docs/DEPLOYMENT-CHECKLIST.md) on a staging or production-ready host to verify the integrity and security of the release.

---

## 1. Release Gate Criteria (Pass/Fail)

A deployment to production may proceed **only** if the following criteria are fully met:

1. **Automated Suite**: 100% of the automated PHPUnit test suite passes with zero failures.
2. **Static Analysis**: PHPStan reports zero errors (`./vendor/bin/phpstan analyse`).
3. **Style Formatting**: Laravel Pint reports all files are formatted and clean (`./vendor/bin/pint --test`).
4. **Critical Manual Flows**: All manual verification scripts defined as **Critical** (Section 4) pass.
5. **Integrations Verified**: Communication with external APIs (Google Sheets connection, Cashfree credentials, and Mail SMTP) is validated.
6. **No Unresolved Defect Gates**: There are no open regression blockers or critical bugs.

---

## 2. Regression Validation Procedure

### Full Automated Regression Gate
To execute the complete automated test suite on the server or build agent:
```bash
php artisan test
```

### Targeted Module Testing (Development / Staging)
During development or hotfix verification, developers may run targeted subsets using filters:
- **Authentication & Security**: `php artisan test --filter=SecurityReviewTest` or `php artisan test --filter=Auth`
- **Shopping Cart & Checkout**: `php artisan test --filter=Cart` or `php artisan test --filter=Checkout`
- **Payments & Webhooks**: `php artisan test --filter=Payment` or `php artisan test --filter=Refund`
- **Google Sheets Sync**: `php artisan test --filter=GoogleSheets`
- **File Uploads**: `php artisan test --filter=File` or `php artisan test --filter=Design`
- **System Backup & Restore**: `php artisan test --filter=Backup`

---

## 3. Module Priority Matrix

In the event of hotfixes or constrained validation windows, test execution and manual reviews must prioritize modules according to their regression impact level:

| Module | Priority | Description |
|---|---|---|
| **Authentication & Permissions** | **Critical** | Prevents unauthorized admin panel access and secures customer sessions. |
| **Checkout & Cart** | **Critical** | Core business path. Validates that customers can select variants and checkout. |
| **Payments & Webhooks** | **Critical** | Guarantees Cashfree transaction logging, refund handling, and webhooks processing. |
| **File Upload & Safety** | **Critical** | Prevents execution of dangerous files (PHP web shells) and enforces upload safety. |
| **Backups & Restore** | **Critical** | Crucial for disaster recovery and rollback states. |
| **Inventory & Stock Management** | **Critical** | Prevents over-selling and maintains stock history. |
| **CRM & Leads** | **High** | Captures inquiries and manages follow-up interactions. |
| **Finance & Expenses** | **High** | Records expense ledgers, purchasing costs, and financial boundaries. |
| **Notifications** | **Medium** | Controls system and transactional email/SMS delivery. |

---

## 4. Module-by-Module Verification Mapping

### 4.1 Authentication & Security (Priority: Critical)
- **Automated Tests**:
  - `AdminAuthenticationTest`
  - `CustomerAuthenticationTest`
  - `SecurityReviewTest`
- **Manual Verification Steps**:
  1. Access `/admin/login` and attempt to log in with invalid credentials. Verify a generic error message is returned and the attempt is throttled after 5 failures.
  2. Log in with a staff account that lacks the `orders.view` permission. Navigate directly to an order detail URL. Verify the system returns `403 Forbidden`.
  3. Attempt to upload a non-image file type (e.g. `.php`, `.exe`, `.js`) via the customer design upload endpoint. Verify it is rejected with a `422` validation status.

### 4.2 Checkout & Cart (Priority: Critical)
- **Automated Tests**:
  - `CartStorageTest`
  - `CartValidationTest`
  - `CheckoutPendingOrderTest`
  - `CheckoutValidationTest`
- **Manual Verification Steps**:
  1. Open a product page, configure custom print settings, upload a design mockup, and add it to the cart.
  2. Modify quantities and remove items in the cart. Verify calculations update instantly.
  3. Click checkout. Verify that it resolves to the checkout validation state and submits successfully, generating a pending order.

### 4.3 Payments & Webhooks (Priority: Critical)
- **Automated Tests**:
  - `PaymentStatusRecalculationIntegrationTest`
  - `PaymentWebhookProcessingTest`
  - `RefundRequestTest`
  - `RefundAuditTrailIntegrationTest`
- **Manual Verification Steps**:
  1. Submit a payment attempt to Cashfree. Ensure the customer handoff redirects correctly to the gateway.
  2. Send a simulated, signed payment success webhook payload to `/api/webhooks/payments/cashfree`. Verify the corresponding order status transition updates to `processing` and creates a payment record.
  3. Issue a partial or full refund from the Admin dashboard. Verify the refund creates a record, recalculates totals, and logs audit trails.

### 4.4 Inventory & Stock (Priority: Critical)
- **Automated Tests**:
  - `InventoryOrderDeductionTest`
  - `InventoryManualAdjustmentTest`
  - `InventoryMovementHistoryTest`
- **Manual Verification Steps**:
  1. Complete an order checkout. Verify the product sku stock is deducted in the database.
  2. Add manual inventory adjustment via the admin panel (stock-in or stock-out). Verify the adjustment updates the stock level and logs an inventory movement record.
  3. Cancel an order. Verify that the inventory reservation is reversed and added back to the active stock.

### 4.5 CRM & Leads (Priority: High)
- **Automated Tests**:
  - `WebsiteLeadCaptureTest`
  - `LeadFollowUpTest`
  - `LeadActivityTimelineTest`
- **Manual Verification Steps**:
  1. Submit a lead inquiry from the frontend contact form. Verify it logs in the admin leads dashboard.
  2. Log in as an admin, edit a lead's follow-up history, and verify changes reflect on the lead activity timeline.

### 4.6 System Backups (Priority: Critical)
- **Automated Tests**:
  - `BackupRestoreTest`
- **Manual Verification Steps**:
  1. Run `php artisan system:backup` and verify a zip archive is created in `storage/app/private/backups/`.
  2. Run `php artisan system:restore` with an invalid/modified ZIP archive (corrupted manifest or bad checksum). Verify the process throws a validation exception and aborts *before* modifying any files or database structures.

---

## 5. Performance Smoke Checks (Sanity Checks)

Verify that load times and response times for core endpoints remain within acceptable margins on the staging/production host:

- **Homepage / Product Listing**: Verify the catalog root loads in under **300ms**.
- **Admin Dashboard**: Load the admin dashboard. Verify that list queries and summary widgets render in under **500ms**.
- **Checkout Submission**: Verify cart checkout validation and pending order creation complete in under **800ms**.
- **Queue Job Processing**: Trigger a Google Sheets sync event. Verify the queue worker picks up and processes the job in under **5 seconds**.
- **Backups Generation**: Verify database and files backup finishes compressing without running out of PHP execution time.

---

## 6. Cross-Browser & Platform Verification Matrix

For manual UI verification, test layouts and interactive components across these browser platforms:

| Platform | OS / Device | Browsers to Validate |
|---|---|---|
| **Desktop** | Windows / macOS / Linux | Chrome (latest), Edge (latest), Firefox (latest), Safari (latest on macOS) |
| **Mobile** | iOS (iPhone) | Safari (default), Chrome Mobile |
| **Mobile** | Android | Chrome Mobile (default) |

Ensure the following components are checked for responsive behavior:
- Cart sidebar and slide-overs.
- Design upload canvas placement coordinates.
- Admin dashboard resource tables.

---

## 7. Exit Criteria

The deployment cycle is completed and the release is marked stable **only when**:
1. All **Release Gate Criteria** (Section 1) are satisfied.
2. All post-deployment health verification checks are completed.
3. No critical UI regressions are reported in the Cross-Browser Matrix.
4. No unresolved performance bottlenecks are flagged in the Smoke Checks.
