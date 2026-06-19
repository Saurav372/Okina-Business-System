# Okina Craft Master Build Plan

Source file reviewed: `okina_craft_full_project_spec.md`

This document turns the project overview into an execution plan. The goal is to prevent the dangerous pattern where Phase 1 is built quickly, then Phase 2 or Phase 3 forces a rewrite because the early database, payment flow, order flow, or product model was too small.

The most important rule:

> Phase 1 may have limited features, but it must use the final shared architecture, final database direction, and final business boundaries from the beginning.

---

# 1. Immediate Findings From Inspection

## 1.1 Current Workspace State

- The workspace currently contains planning/specification documents only.
- The cleaned project spec is `okina_craft_full_project_spec.md`.
- The original instruction file remains as a fallback copy.
- No Laravel backend exists yet.
- No Astro frontend exists yet.
- No database schema exists yet.
- No real code decisions are locked yet.

This is good. The project is still early enough to plan correctly before anything becomes expensive to change.

## 1.2 High-Risk Contradictions To Resolve Before Coding

### Inventory Timing

The instruction file says inventory is required in v1, but the phase plan places full inventory work in Phase 2.

Decision:

- Phase 1 must create product SKUs and simple stock fields.
- Phase 1 must allow product/variant availability rules.
- Phase 1 must not build full inventory accounting.
- Phase 2 adds inventory movements, reservations, purchase stock-in, low stock alerts, and negative stock warnings.

This prevents a rewrite because order items will already reference SKUs from day one.

### Finance Timing

The instruction file says finance is required in v1, but Phase 1 mainly lists payment records.

Decision:

- Phase 1 creates payment records, payment attempts, gateway logs, and refund-ready structure.
- Phase 1 does not build detailed expense categories or profit reports.
- Phase 2 adds customer balance, split payment schedules, vendor payments, and basic finance views.
- Phase 4 adds advanced profit dashboards and reporting.

This prevents the payment model from being too simple for later split payments.

### CRM Timing

Phase 1 needs bulk quote/contact flow, while full CRM is Phase 3.

Decision:

- Phase 1 creates a simple lead/quote request capture path for bulk checkout and contact forms.
- Phase 1 stores source, page URL, referrer, and UTM fields if available.
- Phase 3 expands this into assignments, activities, follow-ups, dashboards, automation, and permissions.

This prevents losing lead data during early sales activity.

### Settings Timing

The instruction file places settings heavily in Phase 4, but payment gateway, upload rules, business contact info, SEO defaults, and notification settings are needed much earlier.

Decision:

- Phase 1 includes a minimal settings foundation.
- Phase 4 expands settings into a complete admin control center.

### Audit Timing

Audit history appears in Phase 4, but order, payment, file, and inventory changes are hard to reconstruct later.

Decision:

- Phase 1 includes simple status history tables for orders, payments, files, and gateway events.
- Phase 4 adds a wider audit log and review tools.

## 1.3 Version And Hosting Risk

The source file selects Laravel 13. Laravel 13 requires PHP 8.3 or newer according to the official Laravel 13 release notes. Shared cPanel hosting must be checked before implementation.

Decision:

- Before project creation, confirm cPanel supports PHP 8.3+, Composer, required PHP extensions, cron jobs, queue processing, file storage permissions, and MySQL version.
- If shared hosting cannot support Laravel 13 reliably, use a VPS for the backend from the beginning or downgrade the Laravel version only after conscious approval.

## 1.4 Source File Normalization

The original instruction file used smart quotes and arrow characters that can display incorrectly in some terminals and AI tooling.

Decision:

- The cleaned project spec is now `okina_craft_full_project_spec.md`.
- Smart quotes were normalized to `"`.
- The curly apostrophe was normalized to `'`.
- Arrow characters were normalized to `->`.
- The original instruction file is kept as a fallback copy because Windows file permissions blocked renaming it directly.

---

# 2. Decisions Needed Before First Code

These decisions should be answered before scaffolding the real Laravel and Astro apps.

## 2.1 Business And Operations

1. Confirm legal business name for invoices, emails, policies, and footer.
2. Confirm GST registration status and whether GST invoice support is required in Phase 1.
3. Confirm whether product prices are GST-inclusive or GST-exclusive.
4. Confirm cancellation, refund, shipping, privacy, and terms page content.
5. Confirm whether COD/manual payment is ever allowed for website checkout.
6. Confirm whether all direct website orders require full payment.
7. Confirm minimum direct checkout quantity and exact bulk threshold behavior.
8. Confirm whether 25+ quantity means per item, per product, or whole cart.
9. Confirm whether sales staff can create direct payment links for manual orders.
10. Confirm if customer support uses only WhatsApp/call or also ticket/email threads.

## 2.2 Product And Pricing

1. Define first product categories for launch.
2. Define exact apparel size set.
3. Define color list and whether color is global or product-specific.
4. Define fabric list.
5. Define print methods and method restrictions.
6. Define price formula for single orders.
7. Define price formula for quantity tiers.
8. Define customization charges.
9. Define shipping charge formula.
10. Define SKU naming format.
11. Define whether SKUs are manually entered, auto-generated, or both.
12. Define how product images and color mockups are prepared.
13. Define product data import format for bulk upload.

## 2.3 Payments

1. Confirm Cashfree account type and API environment.
2. Confirm Cashfree webhook URL requirements.
3. Confirm accepted payment methods.
4. Confirm refund handling in Phase 1 or later.
5. Confirm payment retry rules.
6. Confirm whether partial payment links are needed in Phase 2.
7. Confirm settlement reconciliation needs.
8. Confirm invoice/receipt email requirements.

## 2.4 Shipping

1. Confirm Delhivery account/API availability.
2. Confirm whether Phase 1 shipping is manual or API-assisted.
3. Confirm shipping zones and pricing rules.
4. Confirm packaging dimensions and weights by product type.
5. Confirm whether estimated delivery is required before payment.
6. Confirm whether customer pincode serviceability check is required in Phase 1.

## 2.5 Users, Roles, And Security

1. Confirm admin roles and staff count for launch.
2. Confirm whether customer email verification is required.
3. Confirm whether phone number must be unique.
4. Confirm whether WhatsApp number can differ from phone number.
5. Confirm password reset channel.
6. Confirm who can download uploaded design files.
7. Confirm whether staff activity must be logged from Phase 1.
8. Confirm whether super admin can create other super admins.

## 2.6 Infrastructure

1. Confirm local development environment.
2. Confirm production hosting plan details.
3. Confirm staging environment availability.
4. Confirm database backup method.
5. Confirm queue driver allowed on hosting.
6. Confirm file storage location and space limit.
7. Confirm email provider.
8. Confirm WhatsApp API provider, if any.
9. Confirm Google Sheets account and API access.
10. Confirm domain/subdomain DNS access.

---

# 3. Architecture Guardrails

These guardrails should not change between phases.

## 3.1 Repository Shape

Recommended shape:

```text
okina-craft/
  apps/
    frontend/
    backend/
  docs/
  deployment/
```

Alternative if hosting/deployment prefers separate projects:

```text
okina-frontend/
okina-backend/
docs/
```

Decision:

- Use one Git repository unless deployment constraints force two.
- Keep frontend and backend separate inside the repository.
- Keep shared planning, API contract, and deployment notes in `docs/`.

## 3.2 Backend Architecture

Use one Laravel app and one database.

The backend should be a modular monolith:

```text
app/
  Modules/
    CRM/
    Customers/
    Products/
    Cart/
    Orders/
    Payments/
    Inventory/
    Vendors/
    Finance/
    Shipping/
    Files/
    Notifications/
    GoogleSheets/
    Tracking/
    Settings/
```

Each module should own:

- Models
- Services
- Actions
- Policies
- API controllers when needed
- Filament resources when needed
- Jobs when needed
- Events/listeners when needed
- Tests

## 3.3 Frontend Architecture

Use Astro as a static-first ecommerce frontend.

The frontend should not duplicate business logic that belongs in Laravel.

Astro owns:

- SEO pages
- Product/category rendering
- Cart user interface
- Checkout user interface
- Customer account pages
- Tracking pages
- Landing pages
- WhatsApp/call click UI
- Analytics event dispatch

Laravel owns:

- Product truth
- Variant/SKU truth
- Cart validation
- Checkout validation
- Payment creation and verification
- Order creation
- File storage
- Customer accounts
- Admin operations

## 3.4 Integration Rule

The database action happens first.

External integrations run after the core action:

- WhatsApp
- Email
- Google Sheets
- Analytics server events
- Courier APIs
- Image preview generation

If an integration fails, the order, lead, payment, or customer record must remain saved.

## 3.5 State Separation

Never use one status field for everything.

Orders should keep separate states:

- `order_status`
- `payment_status`
- `design_status`
- `production_status`
- `shipping_status`
- `fulfillment_status`
- `refund_status`

Customer tracking should translate internal states into clean customer-facing text.

## 3.6 Database Migration Rule

Every table created in Phase 1 should be safe to extend later.

Avoid Phase 1 shortcuts such as:

- Saving variant details only as plain text in order items
- Storing uploaded files in MySQL
- Hardcoding Cashfree fields directly on orders
- Creating payment fields only on orders with no payments table
- Creating products without SKUs
- Creating customer orders without customer records
- Creating bulk inquiries without lead source data

---

# 4. Phase 0: Blueprint And Foundation Decisions

Phase 0 happens before app scaffolding. It is a short but critical planning phase.

## 4.1 Stage 0.1: Confirm Hosting Compatibility

Build:

1. Confirm production PHP version.
2. Confirm Laravel 13 compatibility.
3. Confirm Composer availability.
4. Confirm MySQL version.
5. Confirm cron support.
6. Confirm queue worker support.
7. Confirm file storage permissions.
8. Confirm upload size limits.
9. Confirm SSL availability for main and admin domains.

Verify:

1. PHP 8.3+ is available for Laravel 13.
2. Required PHP extensions are available.
3. Scheduled tasks can run.
4. File uploads can be stored outside public web root.
5. Webhooks can reach the backend.

Exit gate:

- Backend hosting path is approved: shared cPanel or VPS.

## 4.2 Stage 0.2: Finalize Data Model Draft

Build:

1. Create ERD for customers, products, SKUs, cart, orders, payments, inventory, files, leads, vendors, shipping, and settings.
2. Define each table's key fields.
3. Define all enum/status values.
4. Define foreign key relationships.
5. Define public ID sequence tables.
6. Define indexes for slugs, public IDs, customer lookup, payment lookup, and tracking lookup.

Verify:

1. A website order can connect to customer, order items, payment, uploaded files, and tracking.
2. A sales/manual order can connect to customer, split payments, inventory, production, and shipping.
3. A lead can convert to customer/order without copying messy duplicate data.
4. A vendor purchase can increase inventory.
5. A payment can exist independently from a gateway.

Exit gate:

- Database schema v1 is approved before migrations are written.

## 4.3 Stage 0.3: Finalize API Contract

Build:

1. Define public catalog endpoints.
2. Define cart endpoints.
3. Define checkout endpoints.
4. Define upload endpoints.
5. Define payment endpoints.
6. Define customer account endpoints.
7. Define order tracking endpoints.
8. Define lead/quote request endpoints.
9. Define admin-only endpoints if Filament cannot cover a workflow.
10. Define response shapes and error formats.

Verify:

1. Astro can render all required frontend pages from these endpoints.
2. Checkout never calls Cashfree directly.
3. Uploads never expose private storage paths.
4. Customer dashboard cannot access another customer's order.

Exit gate:

- API contract is stable enough for frontend and backend to develop in parallel.

## 4.4 Stage 0.4: Finalize Product And Pricing Rules

Build:

1. Define launch categories.
2. Define launch products.
3. Define product attributes.
4. Define SKU format.
5. Define size-wise quantity behavior.
6. Define bulk threshold behavior.
7. Define print method restrictions.
8. Define pricing formula.
9. Define shipping formula.
10. Define discount/coupon decision for Phase 1.

Verify:

1. Apparel size-wise quantity can become order item quantities.
2. Bulk-only product and variant behavior is clear.
3. Direct checkout and quote flow cannot conflict.
4. Admin can later change rules without code edits where practical.

Exit gate:

- Product and pricing rules are documented before catalog coding starts.

## 4.5 Stage 0.5: Finalize Security Baseline

Build:

1. Choose customer authentication approach.
2. Choose admin authentication approach.
3. Choose role/permission package.
4. Define customer session/API auth method.
5. Define upload validation rules.
6. Define signed URL behavior.
7. Define rate limits for forms, login, upload, and payment attempts.
8. Define webhook verification rules.
9. Define admin file download permissions.
10. Define backup rules.

Verify:

1. Admin and customer areas are fully separated.
2. Private files are not web-accessible.
3. Webhooks cannot be spoofed.
4. Staff cannot access finance data unless allowed.

Exit gate:

- Security baseline is approved before public forms and payments are built.

---

# 5. Phase 1: Full Ecommerce Website, Payment, And Basic Admin

Goal:

Launch a working website with product browsing, customization, cart, checkout, full online payment, customer account, file uploads, simple mockup preview, and basic admin management.

Phase 1 must be production usable, even if operations are still simple.

## 5.1 Phase 1 Non-Negotiables

1. Laravel backend is the real shared backend.
2. Astro frontend consumes Laravel APIs.
3. Products have variants and SKUs from the start.
4. Orders reference customers and SKUs from the start.
5. Payments use a gateway interface from the start.
6. Cashfree is an adapter, not checkout logic.
7. Uploads go to private storage, not MySQL.
8. Bulk threshold creates a quote/lead flow, not a broken checkout.
9. Customer account exists in v1.
10. Basic settings exist in v1.

## 5.2 Stage 1.1: Project Scaffolding

Build:

1. Create repository structure.
2. Create Laravel backend app.
3. Create Astro frontend app.
4. Add Tailwind CSS to frontend.
5. Add shared environment documentation.
6. Add local development commands.
7. Add `.env.example` for backend.
8. Add `.env.example` for frontend.
9. Add basic code formatting tools.
10. Add initial test setup.

Verify:

1. Backend boots locally.
2. Frontend boots locally.
3. Backend tests run.
4. Frontend build runs.
5. Environment variables are documented.

Exit gate:

- Empty apps build successfully before feature work starts.

## 5.3 Stage 1.2: Backend Core Foundation

Build:

1. Create modular folder structure.
2. Add base service provider pattern for modules.
3. Add shared API response format.
4. Add shared validation/error format.
5. Add settings foundation.
6. Add public ID sequence service.
7. Add basic event/job pattern.
8. Add queue configuration.
9. Add scheduler placeholder.
10. Add logging channels for payments, uploads, integrations, and admin actions.

Verify:

1. Module service providers load.
2. Public ID generator works by year.
3. API errors are consistent.
4. Queue can run locally.
5. Scheduler command can run locally.

Exit gate:

- Core backend patterns are ready before business modules are added.

## 5.4 Stage 1.3: Admin Authentication And Roles

Build:

1. Install and configure Filament.
2. Create admin login.
3. Create initial super admin seed.
4. Add role/permission package if selected.
5. Create Phase 1 roles: Super Admin, Admin, Sales Staff.
6. Protect admin routes.
7. Add password reset for admin if needed.
8. Add basic profile/password update.

Verify:

1. Super admin can log in.
2. Non-admin cannot enter admin.
3. Sales staff cannot access protected admin sections.
4. Admin seed is safe for production.

Exit gate:

- Admin access is secure enough before product/customer/order data exists.

## 5.5 Stage 1.4: Product Catalog Foundation

Build:

1. Create product categories.
2. Create products.
3. Create product variants.
4. Create product SKUs.
5. Create product images.
6. Create product statuses.
7. Create variant-level availability rules.
8. Create bulk-only product and variant rules.
9. Create basic stock fields on SKUs.
10. Create product slug and SEO fields.
11. Create category slug and SEO fields.
12. Create Filament resources for categories, products, variants, SKUs, and images.

Verify:

1. Active products appear through API.
2. Draft/hidden products do not appear publicly.
3. Out-of-stock products remain visible but not purchasable.
4. Bulk-only variant disables direct checkout.
5. Product page data includes images, variants, SKUs, and SEO fields.
6. Order item can reference SKU ID later.

Exit gate:

- Product model supports future inventory and customization without rewrite.

## 5.6 Stage 1.5: Product Customization Rules

Build:

1. Create print positions.
2. Create product print areas.
3. Create print methods.
4. Link allowed print methods to products/SKUs/fabrics where needed.
5. Create customization fields for product type, color, size, quantity per size, print position, print method, upload, and notes.
6. Create validation service for invalid combinations.
7. Add admin management for print areas and methods.
8. Add API output for customization options.

Verify:

1. Size-wise quantities calculate total quantity correctly.
2. Invalid print method combinations are blocked.
3. Validation returns customer-friendly messages.
4. Admin can update print areas without code change where possible.
5. Customization data can be saved into cart and order items.

Exit gate:

- Custom product selections can move from product page to cart to order.

## 5.7 Stage 1.6: File Upload Module

Build:

1. Create uploaded files table.
2. Create file previews table.
3. Create file deletion logs table.
4. Configure private upload disk.
5. Implement upload validation by MIME type and extension.
6. Enforce 5 MB v1 limit.
7. Reject dangerous file types.
8. Rename stored files.
9. Save original filename separately.
10. Generate preview where possible.
11. Store original file even if preview fails.
12. Create signed preview/download URL service.
13. Add admin upload/file resource.
14. Add upload logs.

Verify:

1. Valid PNG/JPG/WEBP uploads save privately.
2. Oversized file shows customer-friendly message.
3. Dangerous file types are rejected.
4. Original file is not public.
5. Signed URLs expire.
6. Preview failure does not delete original upload.

Exit gate:

- Uploads are secure enough before public forms accept files.

## 5.8 Stage 1.7: Simple Mockup Preview

Build:

1. Define mockup image per product color.
2. Define print area coordinates.
3. Create frontend preview using product mockup and uploaded design.
4. Allow rough move/resize only if practical.
5. Save mockup preview metadata.
6. Save generated preview image when possible.
7. Link mockup preview to cart item/order item.
8. Add admin view of preview and original file.

Verify:

1. Customer can see uploaded design placed on product mockup.
2. Preview uses allowed print area.
3. Preview is saved with cart/order.
4. Admin can download original file separately.
5. Mockup failure does not block order if original file exists.

Exit gate:

- V1 preview is simple, useful, and not pretending to be a full design editor.

## 5.9 Stage 1.8: Customer Accounts

Build:

1. Create users/customer auth separation decision.
2. Create customers table.
3. Create customer addresses table.
4. Add customer registration.
5. Add customer login.
6. Require phone number.
7. Add password reset.
8. Add address create/edit/delete.
9. Add customer dashboard API.
10. Add order list API placeholder.

Verify:

1. Customer can register.
2. Phone number is required.
3. Customer can log in.
4. Customer can manage addresses.
5. Customer cannot access another customer's data.

Exit gate:

- Customer identity is ready before checkout.

## 5.10 Stage 1.9: Cart API

Build:

1. Create carts table.
2. Create cart items table.
3. Support guest cart if approved.
4. Support customer cart.
5. Add add-to-cart endpoint.
6. Add update quantity endpoint.
7. Add remove item endpoint.
8. Add cart summary endpoint.
9. Store customization data in structured JSON plus key foreign IDs.
10. Store upload/mockup references.
11. Enforce product, variant, SKU, print method, and bulk rules.
12. Implement cart merge on login if guest carts are supported.

Verify:

1. Mixed product cart works.
2. Size-wise quantities remain structured.
3. Bulk threshold redirects to quote flow.
4. Hidden/draft products cannot be added.
5. Out-of-stock behavior matches business rule.
6. Price recalculates from backend truth, not frontend values.

Exit gate:

- Cart is valid enough to become checkout.

## 5.11 Stage 1.10: Checkout API

Build:

1. Create checkout sessions table.
2. Validate cart before checkout.
3. Require logged-in customer.
4. Require shipping address.
5. Calculate subtotal.
6. Calculate shipping.
7. Calculate tax if applicable.
8. Calculate final payable amount.
9. Lock checkout session amount.
10. Create pending order or payment-intent flow.
11. Prevent duplicate order creation.
12. Add idempotency keys.

Verify:

1. Invalid cart cannot checkout.
2. Price changes are detected.
3. Duplicate payment clicks do not create duplicate orders.
4. Bulk carts cannot enter direct full-payment checkout.
5. Checkout session expires correctly.

Exit gate:

- Checkout can safely hand off to the payment module.

## 5.12 Stage 1.11: Payment Module And Cashfree Adapter

Build:

1. Create `PaymentGatewayInterface`.
2. Create `PaymentGatewayManager`.
3. Create `CashfreeGateway`.
4. Create `ManualPaymentGateway` placeholder.
5. Create payment gateway settings.
6. Create payments table.
7. Create payment attempts table.
8. Create payment webhook logs table.
9. Create payment verification service.
10. Create Cashfree webhook handler.
11. Verify webhook signature.
12. Add payment retry support.
13. Keep checkout independent from Cashfree-specific code.

Verify:

1. Active gateway can be selected through settings.
2. Checkout calls only the manager/interface.
3. Cashfree attempt can be created.
4. Successful payment marks payment as paid.
5. Failed payment marks attempt failed without corrupting order.
6. Webhook replay is idempotent.
7. Webhook payload is logged.
8. Manual gateway can be added later without checkout rewrite.

Exit gate:

- Payment abstraction is real, not just a folder name.

## 5.13 Stage 1.12: Order Creation And Basic Order Admin

Build:

1. Create orders table.
2. Create order items table.
3. Create order status histories table.
4. Create order designs table.
5. Create order notes table.
6. Create order public ID generator.
7. Create order from paid checkout.
8. Copy product/SKU snapshot into order item.
9. Link order items to original SKUs.
10. Link uploaded files/mockup previews to order items.
11. Set initial statuses.
12. Create customer order confirmation view/API.
13. Create Filament order resource.
14. Create basic payment record view in admin.
15. Allow admin to update basic order/design status.

Verify:

1. Paid checkout creates one order.
2. Order has public ID like `OD26-000001`.
3. Order item keeps SKU link and product snapshot.
4. Customer dashboard shows order.
5. Admin can view order, payment, files, and customization.
6. Status changes are recorded.

Exit gate:

- A real customer can place a paid order and admin can process it manually.

## 5.14 Stage 1.13: Bulk Quote And Contact Flow

Build:

1. Create basic leads table or quote requests table.
2. Capture quote request from bulk cart/product page.
3. Capture name, phone, WhatsApp, product, quantity, city, message, upload, page URL, referrer, UTM values, and timestamp.
4. Add WhatsApp dynamic message builder.
5. Add admin list for quote requests/leads.
6. Add email notification job if email provider is ready.
7. Add WhatsApp notification job only if provider is ready.
8. Ensure notification failures do not block save.

Verify:

1. 25+ quantity triggers quote/contact path.
2. Quote request saves even if notification fails.
3. Admin can see request and uploaded file.
4. Source/UTM data is preserved.
5. WhatsApp button message contains useful context.

Exit gate:

- Bulk customers have a reliable path even before full CRM exists.

## 5.15 Stage 1.14: Astro Frontend Ecommerce Pages

Build:

1. Create main layout.
2. Create header and footer.
3. Create home page.
4. Create product listing page.
5. Create category pages.
6. Create product detail page.
7. Create variant selector.
8. Create size-wise quantity selector.
9. Create print position selector.
10. Create print method selector.
11. Create upload design component.
12. Create mockup preview component.
13. Create cart page/drawer.
14. Create checkout page.
15. Create customer login page.
16. Create customer register page.
17. Create customer dashboard page.
18. Create order detail page.
19. Create services page.
20. Create gallery page.
21. Create about page.
22. Create contact page.
23. Create FAQ page.
24. Create insights index and article template.
25. Create landing page template.

Verify:

1. Pages render from real backend data where needed.
2. Product page handles active, out-of-stock, and bulk-only states.
3. Mobile layout is usable.
4. Forms show helpful errors.
5. Customer account flow works.
6. Cart/checkout flow works end to end.
7. SEO metadata exists on indexable pages.
8. Noindex/canonical rules work for campaign pages.

Exit gate:

- Frontend is a usable ecommerce site, not a static brochure.

## 5.16 Stage 1.15: SEO, Tracking Placeholders, And Content Basics

Build:

1. Add SEO component.
2. Add metadata for home, category, product, services, insights, and landing pages.
3. Add canonical handling.
4. Add noindex handling for campaign pages.
5. Add sitemap generation.
6. Add robots.txt.
7. Add structured data for products where data is available.
8. Add analytics placeholder layer.
9. Track local frontend events before real pixel IDs are added.
10. Add WhatsApp and call click tracking placeholders.

Verify:

1. Product/category pages have title and description.
2. Noindex pages are correctly marked.
3. Similar landing pages can point canonical to main SEO page.
4. Sitemap excludes private/customer/admin pages.
5. Tracking code does not break if IDs are missing.

Exit gate:

- Website can launch with SEO-safe defaults.

## 5.17 Stage 1.16: Phase 1 Quality And Deployment

Build:

1. Add backend feature tests for catalog, cart, checkout, payments, uploads, and orders.
2. Add frontend smoke tests for key pages.
3. Add payment sandbox test checklist.
4. Add upload security test checklist.
5. Add production build scripts.
6. Add cPanel deployment guide.
7. Add database migration/seed guide.
8. Add backup guide.
9. Add rollback guide.
10. Add launch checklist.

Verify:

1. Backend tests pass.
2. Frontend build passes.
3. Payment sandbox success/failure/webhook cases pass.
4. Upload security cases pass.
5. Order creation does not duplicate.
6. Admin can process paid order.
7. Customer can track basic order state.

Phase 1 exit gate:

- A customer can browse, customize, upload, preview, add to cart, pay, receive an order, and see it in their account.
- Admin can manage products, view orders, view payments, and download protected design files.
- Bulk customers can submit a quote/contact request instead of entering the wrong checkout path.

---

# 6. Phase 2: Inventory, Customer, Purchase, Sales Operations, And Tracking

Goal:

Turn the working ecommerce system into a real operations system.

## 6.1 Stage 2.1: Inventory Ledger

Build:

1. Expand SKU inventory fields.
2. Create inventory items if separate from SKUs.
3. Create inventory movements.
4. Create inventory reservations.
5. Create stock adjustments.
6. Create low stock alerts.
7. Add movement types.
8. Add reservation rules.
9. Add negative stock warnings.
10. Add inventory Filament resources.

Verify:

1. Every stock change creates a movement.
2. Order confirmation can reserve stock.
3. Cancellation can release stock.
4. Negative stock creates warning, not crash.
5. Inventory staff cannot see cost if not allowed.

Exit gate:

- Stock quantity becomes explainable through movement history.

## 6.2 Stage 2.2: Customer Records Expansion

Build:

1. Expand customer profile fields.
2. Add customer notes.
3. Add customer order history.
4. Add customer payment history.
5. Add customer address management in admin.
6. Add duplicate customer detection by phone/email.
7. Add customer merge strategy if needed.

Verify:

1. Website customers and manual customers use same customer table.
2. Admin can find customer by phone, email, name, or public customer ID.
3. Customer history shows website and manual orders together.

Exit gate:

- Customer data is unified, not split between ecommerce and sales.

## 6.3 Stage 2.3: Manual/Sales Order Creation

Build:

1. Add manual order creation in admin.
2. Allow sales staff to select/create customer.
3. Allow sales staff to select products/SKUs.
4. Allow custom line items if approved.
5. Allow size-wise quantities.
6. Allow design upload attachment.
7. Allow price override with permission.
8. Allow order notes and production instructions.
9. Create order status history.
10. Connect manual order to same order/payment/customer tables.

Verify:

1. Manual order uses same public order ID format.
2. Manual order appears in customer history.
3. Manual order can be tracked by customer if enabled.
4. Sales staff cannot bypass restricted permissions.
5. Website checkout still works.

Exit gate:

- Sales orders and website orders share one system.

## 6.4 Stage 2.4: Split Payments And Payment Schedules

Build:

1. Create payment schedules.
2. Allow advance payment.
3. Allow partial payment.
4. Allow final/balance payment.
5. Allow multiple installments.
6. Add manual payment records.
7. Add payment proof upload if needed.
8. Add payment links if gateway supports them.
9. Add balance calculation.
10. Add dispatch block when final payment required.

Verify:

1. Order can be partially paid.
2. Balance is calculated correctly.
3. Payment timeline is visible.
4. Finance staff can record payment.
5. Sales staff can see payment status but not restricted profit data.
6. Full-payment website checkout remains unchanged.

Exit gate:

- Manual orders can use realistic business payment terms.

## 6.5 Stage 2.5: Vendor And Purchase Management

Build:

1. Create vendors.
2. Create vendor orders.
3. Create vendor order items.
4. Create vendor payments.
5. Create purchase stock-ins.
6. Link purchase stock-ins to inventory movements.
7. Add vendor order statuses.
8. Add vendor payment statuses.
9. Add vendor history.
10. Add admin resources.

Verify:

1. Vendor purchase can increase stock.
2. Stock-in creates inventory movement.
3. Vendor order has public ID like `VO26-00001`.
4. Vendor data does not live as an isolated list.

Exit gate:

- Purchase workflow feeds inventory.

## 6.6 Stage 2.6: Design, Production, And Shipping Statuses

Build:

1. Add design statuses.
2. Add production statuses.
3. Add shipping statuses.
4. Add production queue.
5. Add design issue workflow.
6. Add ready-to-ship workflow.
7. Add shipment records.
8. Add manual courier/tracking ID entry.
9. Add estimated delivery fields.
10. Add internal vs customer-safe status text mapping.

Verify:

1. Design issue can pause production.
2. Production status changes do not corrupt order status.
3. Shipping status changes do not corrupt payment status.
4. Customer sees friendly tracking text.
5. Internal warnings are hidden from customer where needed.

Exit gate:

- Operations can update order progress without overloading one status field.

## 6.7 Stage 2.7: Dynamic Customer Tracking

Build:

1. Create tracking events.
2. Create timeline builder.
3. Support website direct order timeline.
4. Support sales/manual order timeline.
5. Support design issue timeline.
6. Support cancellation timeline.
7. Add tracking page/API.
8. Add support/contact button.
9. Add feedback form after delivery.
10. Add customer-safe status messages.

Verify:

1. Customer tracking changes based on payment type.
2. Customer tracking changes based on design status.
3. Balance pending status shows clear message.
4. Cancelled order hides unsafe internal reason.
5. Tracking page cannot leak another customer's data.

Exit gate:

- Customers can understand order progress without calling every time.

## 6.8 Stage 2.8: File Cleanup

Build:

1. Add file expiry logic.
2. Add abandoned inquiry/order cleanup rule.
3. Add completed order cleanup rule.
4. Add protected file flag.
5. Add cleanup job.
6. Add cleanup log.
7. Add admin view of upcoming deletions.
8. Add safe delete behavior that preserves database record.

Verify:

1. Expired files can be deleted from storage.
2. Protected files are not deleted.
3. Deleted file records remain in database.
4. Cleanup failure is logged.

Exit gate:

- Upload storage will not grow forever.

## 6.9 Stage 2.9: Basic Finance Views

Build:

1. Add payment dashboard.
2. Add pending balance report.
3. Add customer balance view.
4. Add order payment timeline.
5. Add refund records.
6. Add basic profit calculator if cost data is available.
7. Add permission control around cost/profit data.

Verify:

1. Finance can see payment received and pending.
2. Sales can see safe payment status.
3. Restricted users cannot see profit/cost.
4. Refund record does not erase original payment.

Exit gate:

- Finance can operate without spreadsheets as the main system.

Phase 2 exit gate:

- Website checkout still works.
- Manual sales orders work.
- Split payments work.
- Inventory, purchase, production, shipping, and tracking are connected to the same order/customer system.

---

# 7. Phase 3: CRM, Staff Permissions, Automation, Google Sheets, And Analytics

Goal:

Add the sales workflow and automation layer without letting automation break core business actions.

## 7.1 Stage 3.1: CRM Lead Module

Build:

1. Expand leads table.
2. Add lead activities.
3. Add lead follow-ups.
4. Add lead assignments.
5. Add lead source tracking.
6. Add lead status workflow.
7. Add product inquiry forms.
8. Add landing page lead forms.
9. Add manual lead entry.
10. Add conversion to customer/order.

Verify:

1. Lead can be created from all required sources.
2. UTM/referrer/page URL are saved.
3. Lead can convert without duplicate messy records.
4. Lead status history is visible.

Exit gate:

- Lead flow connects to customer/order flow.

## 7.2 Stage 3.2: Staff Permissions Expansion

Build:

1. Add Inventory Staff role.
2. Add Finance Staff role.
3. Add Production Staff role.
4. Define policies per module.
5. Restrict delete actions.
6. Restrict cost/profit visibility.
7. Restrict payment edits.
8. Restrict inventory cost.
9. Add employee deletion approval workflow if required.
10. Add permission tests.

Verify:

1. Sales staff can see assigned work.
2. Sales staff cannot delete core records.
3. Inventory staff cannot see profit reports.
4. Finance staff can manage payments.
5. Production staff sees design/production queue only.

Exit gate:

- Staff can use the system without exposing sensitive data.

## 7.3 Stage 3.3: Follow-Up Workflow

Build:

1. Add follow-up due dates.
2. Add follow-up reminders.
3. Add lead notes and activity timeline.
4. Add sales dashboard.
5. Add overdue follow-up view.
6. Add important lead marker.
7. Add PNP behavior.
8. Add follow-up notification jobs.

Verify:

1. Sales can see today's follow-ups.
2. Overdue follow-ups are visible.
3. Follow-up notification failure does not break lead update.
4. Lead activity remains auditable.

Exit gate:

- Sales workflow becomes repeatable.

## 7.4 Stage 3.4: Notification Module

Build:

1. Create notification events.
2. Create notification templates.
3. Add email notification channel.
4. Add WhatsApp channel if provider is available.
5. Add business recipient settings.
6. Add customer auto-reply if approved.
7. Add notification logs.
8. Add retry behavior.
9. Add failure dashboard.

Verify:

1. New lead notification works.
2. New order notification works.
3. Payment received notification works.
4. Design issue notification works.
5. Notification failure is logged and does not block save.

Exit gate:

- Notifications are helpful, but never mission-critical blockers.

## 7.5 Stage 3.5: Google Sheets Backup Sync

Build:

1. Create Google Sheets credentials settings.
2. Create sync jobs table.
3. Create sync logs table.
4. Add Leads tab sync.
5. Add Orders tab sync.
6. Add Payments tab sync.
7. Add Inventory Movements tab sync.
8. Add Customers tab sync.
9. Add Follow Ups tab sync.
10. Add Vendor Orders tab sync.
11. Add retry logic.
12. Add admin failure view.

Verify:

1. Database save happens before sync job.
2. Failed sync does not block checkout/lead/order/payment.
3. Retry can recover failed sync.
4. Admin can see failed sync reason.

Exit gate:

- Google Sheets is backup/reporting only, not the source of truth.

## 7.6 Stage 3.6: Tracking And Analytics

Build:

1. Add visitor session tracking.
2. Add tracking events table.
3. Add UTM capture script.
4. Add first landing page storage.
5. Add Meta Pixel integration.
6. Add GA4 integration.
7. Track product view.
8. Track category view.
9. Track WhatsApp click.
10. Track call click.
11. Track form submit.
12. Track quote request.
13. Track add to cart.
14. Track begin checkout.
15. Track payment success.
16. Track purchase.
17. Add consent/privacy behavior if required.

Verify:

1. Tracking IDs can be missing without breaking site.
2. UTM data reaches lead/order where needed.
3. Purchase event fires only after payment success.
4. Customer private data is not leaked to ad platforms.

Exit gate:

- Marketing can measure without weakening checkout reliability.

## 7.7 Stage 3.7: Background Jobs And Failed Job Handling

Build:

1. Standardize job retries.
2. Standardize backoff rules.
3. Add failed job monitoring.
4. Add admin retry buttons where safe.
5. Add queue health dashboard.
6. Add scheduler health checks.
7. Add integration-specific logs.

Verify:

1. Failed email can retry.
2. Failed Google Sheets sync can retry.
3. Failed WhatsApp notification can retry.
4. Replayed jobs do not duplicate orders/payments.

Exit gate:

- Automation is recoverable.

Phase 3 exit gate:

- Leads, follow-ups, roles, notifications, Google Sheets, and analytics are connected to the existing ecommerce/order system without blocking it.

---

# 8. Phase 4: Hardening, Safety, Reporting, And Growth

Goal:

Strengthen the already-connected system. This is not the phase where things finally connect; connection must already exist.

## 8.1 Stage 4.1: Unified Admin Dashboard

Build:

1. Add sales overview.
2. Add order overview.
3. Add payment overview.
4. Add inventory warnings.
5. Add production queue summary.
6. Add shipping summary.
7. Add follow-up summary.
8. Add failed integration summary.
9. Add quick links by role.

Verify:

1. Each role sees relevant dashboard data.
2. Restricted finance data stays hidden.
3. Dashboard queries are fast enough.

Exit gate:

- Admin dashboard becomes the operating cockpit.

## 8.2 Stage 4.2: Audit History

Build:

1. Add global audit log.
2. Track changes to orders.
3. Track changes to payments.
4. Track changes to inventory.
5. Track changes to products.
6. Track file downloads/deletions.
7. Track staff actions.
8. Add audit views.

Verify:

1. Sensitive changes show who/when/what.
2. Audit log cannot be edited by normal staff.
3. Audit log does not expose unnecessary customer file content.

Exit gate:

- Important business changes are traceable.

## 8.3 Stage 4.3: Security Review

Build:

1. Review auth guards.
2. Review policies.
3. Review rate limits.
4. Review upload validation.
5. Review signed URLs.
6. Review webhook signatures.
7. Review admin permissions.
8. Review CORS settings.
9. Review session/cookie settings.
10. Review secrets management.

Verify:

1. Private files are not public.
2. Webhooks require valid signature.
3. Customers cannot access other customer data.
4. Staff cannot access restricted modules.
5. Admin routes are protected.

Exit gate:

- Security posture is ready for growth.

## 8.4 Stage 4.4: Payment Webhook And Reconciliation Hardening

Build:

1. Review all payment states.
2. Add reconciliation report.
3. Add payment mismatch alerts.
4. Add gateway event deduplication.
5. Add retry verification.
6. Add refund workflow improvements.
7. Add settlement report if needed.
8. Add future Razorpay/PayU adapter if needed.

Verify:

1. Duplicate webhook does not duplicate payment.
2. Failed redirect can still be fixed by webhook.
3. Manual verification can repair uncertain payment.
4. Refund records preserve original payment history.

Exit gate:

- Payment records can be trusted.

## 8.5 Stage 4.5: Backup, Export, And Recovery

Build:

1. Add database backup routine.
2. Add file backup routine.
3. Add export tools.
4. Add restore rehearsal guide.
5. Add admin export permission.
6. Add backup monitoring.
7. Add retention rules.

Verify:

1. Database backup can be restored.
2. File backup can be restored.
3. Exports do not expose restricted data to unauthorized staff.

Exit gate:

- Business can recover from hosting/database failure.

## 8.6 Stage 4.6: Performance Optimization

Build:

1. Review slow queries.
2. Add missing indexes.
3. Optimize product/category API.
4. Optimize admin lists.
5. Optimize image delivery.
6. Add caching where safe.
7. Review frontend bundle size.
8. Review Core Web Vitals.
9. Review queue processing.

Verify:

1. Product listing is fast.
2. Product detail is fast.
3. Admin order list is fast.
4. Checkout is not slowed by external integrations.
5. Astro production build is lean.

Exit gate:

- Site and admin are ready for higher traffic.

## 8.7 Stage 4.7: Deployment Hardening And VPS Decision

Build:

1. Review shared hosting limits.
2. Review queue reliability.
3. Review webhook reliability.
4. Review upload/storage limits.
5. Review backup reliability.
6. Prepare VPS migration plan if needed.
7. Prepare `api.okinacraft.com` split if needed.
8. Prepare zero/low downtime migration checklist.

Verify:

1. Current hosting can handle live workload.
2. Migration path is documented.
3. DNS and SSL plan is clear.
4. Backups exist before migration.

Exit gate:

- Hosting is no longer a hidden risk.

## 8.8 Stage 4.8: Reporting And Growth Features

Build as needed:

1. Advanced sales reports.
2. Profit dashboard.
3. Product performance reports.
4. Customer reorder automation.
5. Abandoned cart recovery.
6. Abandoned lead recovery.
7. Product feed for ads.
8. SEO city pages at scale.
9. More payment gateway adapters.
10. Courier API integration.
11. Advanced mockup/editor features.

Verify:

1. Growth features use existing data model.
2. Reports match source transaction data.
3. Automation remains fail-safe.

Phase 4 exit gate:

- System is connected, observable, secure, backed up, and ready for scale.

---

# 9. Database Migration Plan By Phase

## Phase 1 Tables

Create these from the beginning:

- `users`
- `roles`
- `permissions`
- role/permission pivot tables
- `customers`
- `customer_addresses`
- `public_id_sequences`
- `business_settings`
- `payment_settings`
- `notification_settings`
- `seo_settings`
- `product_categories`
- `products`
- `product_variants`
- `product_skus`
- `product_images`
- `product_print_areas`
- `product_print_methods`
- `product_variant_rules`
- `product_bulk_rules`
- `uploaded_files`
- `file_previews`
- `file_deletion_logs`
- `carts`
- `cart_items`
- `checkout_sessions`
- `orders`
- `order_items`
- `order_status_histories`
- `order_designs`
- `order_notes`
- `payments`
- `payment_attempts`
- `payment_gateway_settings`
- `payment_webhook_logs`
- `refunds`
- `leads` or `quote_requests`
- `tracking_events`
- `visitor_sessions`

Important:

- Keep Phase 1 inventory fields simple, but make order items reference SKUs.
- Keep Phase 1 leads simple, but save source/UTM data.
- Keep Phase 1 finance simple, but use real payment tables.

## Phase 2 Tables

Add or expand:

- `customer_notes`
- `inventory_items`
- `inventory_movements`
- `inventory_reservations`
- `stock_adjustments`
- `low_stock_alerts`
- `vendors`
- `vendor_orders`
- `vendor_order_items`
- `vendor_payments`
- `purchase_stock_ins`
- `payment_schedules`
- `shipments`
- `shipment_events`
- `courier_settings`
- `order_tracking_events`
- production queue/status tables if separate

## Phase 3 Tables

Add or expand:

- `lead_activities`
- `lead_follow_ups`
- `lead_assignments`
- `notification_logs`
- `notification_templates`
- `google_sheet_sync_jobs`
- `google_sheet_sync_logs`
- `utm_sources`
- expanded `tracking_events`

## Phase 4 Tables

Add or expand:

- `audit_logs`
- `integration_retry_logs`
- `exports`
- `backup_logs`
- report cache tables if needed
- advanced settings tables if needed

---

# 10. API Plan

## Public Catalog APIs

- `GET /api/categories`
- `GET /api/categories/{slug}`
- `GET /api/products`
- `GET /api/products/{slug}`
- `GET /api/products/{slug}/customization-options`

## Cart APIs

- `GET /api/cart`
- `POST /api/cart/items`
- `PATCH /api/cart/items/{item}`
- `DELETE /api/cart/items/{item}`
- `POST /api/cart/validate`

## Upload APIs

- `POST /api/uploads`
- `GET /api/uploads/{file}/preview`
- `GET /api/uploads/{file}/download`

## Checkout APIs

- `POST /api/checkout/session`
- `GET /api/checkout/session/{id}`
- `POST /api/checkout/session/{id}/confirm`

## Payment APIs

- `POST /api/payments/attempts`
- `GET /api/payments/attempts/{id}`
- `POST /api/payments/verify`
- `POST /api/webhooks/cashfree`

## Customer APIs

- `POST /api/customer/register`
- `POST /api/customer/login`
- `POST /api/customer/logout`
- `GET /api/customer/me`
- `GET /api/customer/addresses`
- `POST /api/customer/addresses`
- `PATCH /api/customer/addresses/{id}`
- `DELETE /api/customer/addresses/{id}`
- `GET /api/customer/orders`
- `GET /api/customer/orders/{publicId}`

## Tracking APIs

- `GET /api/orders/{publicId}/tracking`

## Lead/Quote APIs

- `POST /api/leads`
- `POST /api/quote-requests`
- `POST /api/tracking/events`

API rule:

- Frontend never trusts its own price calculation.
- Backend recalculates cart, checkout, payment amount, and order total.

---

# 11. Testing Plan

## Phase 1 Must-Test Paths

1. Product visible when active.
2. Product hidden when draft/hidden.
3. Out-of-stock product visible but not purchasable.
4. Bulk-only variant redirects to quote flow.
5. Size-wise quantity total is correct.
6. Invalid print method is blocked.
7. Upload accepts valid image.
8. Upload rejects executable/dangerous file.
9. Upload stores original privately.
10. Customer registration works.
11. Customer login works.
12. Cart supports mixed products.
13. Checkout requires login and address.
14. Cashfree payment attempt is created through gateway manager.
15. Payment webhook marks payment paid.
16. Duplicate webhook is safe.
17. Paid checkout creates one order.
18. Customer sees order in dashboard.
19. Admin sees order/payment/upload.
20. Quote request saves when notification fails.

## Phase 2 Must-Test Paths

1. Inventory movement changes stock.
2. Order confirmation reserves stock.
3. Negative stock creates warning.
4. Vendor stock-in creates inventory movement.
5. Manual order creates normal order record.
6. Split payments calculate balance.
7. Dispatch block works when balance is pending.
8. Customer tracking shows sales-order timeline.
9. Design issue updates customer timeline.
10. File cleanup skips protected files.

## Phase 3 Must-Test Paths

1. Lead captures UTM/referrer/page URL.
2. Lead assignment controls action permissions.
3. Follow-up reminder is created.
4. Sales staff cannot delete records.
5. Finance data is restricted.
6. Notification failure does not block lead/order.
7. Google Sheets failure does not block main record.
8. Failed sync can retry.
9. Analytics event does not break if pixel ID missing.

## Phase 4 Must-Test Paths

1. Audit log records sensitive changes.
2. Payment reconciliation catches mismatch.
3. Backup can restore.
4. Signed file URLs expire.
5. Dashboard respects permissions.
6. Performance is acceptable under expected load.

---

# 12. Regression Gates

Before each phase starts:

1. Backup database if production already exists.
2. Run existing tests.
3. Confirm no pending broken migrations.
4. Confirm current checkout still works.
5. Confirm current admin login still works.
6. Confirm current order view still works.

Before each phase deploys:

1. Run automated tests.
2. Run checkout smoke test.
3. Run admin smoke test.
4. Run upload smoke test.
5. Run payment sandbox or test mode.
6. Check logs for errors.
7. Confirm rollback path.

After each phase deploys:

1. Create a real test product.
2. Place a test order.
3. Confirm payment behavior.
4. Confirm admin order visibility.
5. Confirm customer order visibility.
6. Confirm no private file is public.
7. Confirm background jobs are running.

---

# 13. Suggested Build Sequence Summary

This is the safest high-level order:

1. Phase 0: Lock hosting, database, API, product, pricing, and security decisions.
2. Scaffold Laravel and Astro.
3. Build backend foundation, settings, roles, IDs, jobs, and logs.
4. Build catalog with products, variants, SKUs, statuses, and images.
5. Build customization rules and file upload.
6. Build customer account.
7. Build cart and checkout.
8. Build payment interface and Cashfree adapter.
9. Build order creation and basic admin order handling.
10. Build Astro ecommerce pages.
11. Build quote/contact path for bulk orders.
12. Deploy Phase 1.
13. Add inventory movements/reservations.
14. Add manual sales orders and split payments.
15. Add vendor/purchase workflow.
16. Add production, shipping, and tracking.
17. Deploy Phase 2.
18. Add CRM, staff permissions, follow-ups, notifications, Sheets sync, and analytics.
19. Deploy Phase 3.
20. Add audit, retry tools, backups, security review, reports, performance, and hosting hardening.
21. Deploy Phase 4.

---

# 14. What Should Not Be Built Too Early

Do not build these in Phase 1 unless specifically approved:

1. Canva-style editor.
2. Advanced multi-layer design tool.
3. Full expense accounting.
4. Full profit dashboard.
5. Advanced abandoned cart automation.
6. Courier API automation if manual shipping is enough.
7. Multiple payment gateways beyond the interface and Cashfree.
8. Large city SEO page generator.
9. Complex AI features.
10. Advanced product feed automation.

But Phase 1 must leave clean extension points for all of them.

---

# 15. Recommended Next Action

The next safest action is not coding the website yet.

The next action should be:

1. Answer the decisions in Section 2.
2. Create the Phase 0 ERD and API contract.
3. Confirm hosting supports Laravel 13/PHP 8.3+.
4. Only then scaffold Laravel and Astro.

This order protects the project from early structural mistakes.
