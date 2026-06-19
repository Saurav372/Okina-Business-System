# Okina Craft Website + Ecommerce + Admin System
## Full AI Coding Instruction File

**Project Name:** Okina Craft  
**Domain:** okinacraft.com  
**Business Type:** Premium B2B ecommerce brand for custom printed products, also accepting single/low-quantity customer orders.  
**Primary Current Product:** Custom printed t-shirts, polo t-shirts, caps, hoodies, sweatshirts, aprons.  
**Future Product Expansion:** Magnetic badges, pen sets, metal keychains, mobile stands, infinity calendars, corporate gift sets, mugs, coasters, tote bags, stationery, notebooks/diaries, wooden stands, mousepads, calendars, laptop skins, and other customizable merchandise.  
**Business Location:** Street No. 11, Uttarakhand Enclave, Nathupura Burari, Delhi 110084  
**Service Area:** Started in Delhi, claims all-India shipping.  
**Primary WhatsApp/Call:** 7827747556  
**Follow-up / Automation Number:** 8383829088  
**Notification Emails:** okinacraft@gmail.com, info@okinacraft.com  

---

# 1. Project Objective

Build a fast, SEO-friendly ecommerce website with a connected Laravel admin system.

The system must support:

- Product browsing
- Variable/customizable products
- Add to cart
- Mixed product cart
- Customer account
- Direct checkout for low/single quantity orders
- Sales-team quote flow for bulk/custom orders
- Simple product mockup preview
- Design/logo uploads
- Product/variant-level inventory
- CRM and lead management
- Customer management
- Order management
- Split/manual payments for sales-team orders
- Payment gateway integration through a replaceable payment module
- Inventory and purchase management
- Finance/payment tracking
- Employee/staff role management
- Google Sheets backup sync
- Meta Pixel / GA4 tracking
- SEO and landing pages
- Dynamic customer order tracking page

The public website and the business management system must be separate but connected.

---

# 2. Final Technical Stack

## Frontend

- Astro
- Tailwind CSS
- Static-first build
- Customer-facing ecommerce frontend
- SEO pages and landing pages
- Product pages, category pages, cart, checkout, customer dashboard
- Connected to Laravel backend/API for dynamic data

## Backend/Admin

- Laravel 13
- Filament Admin Panel
- MySQL
- Modular monolith architecture
- API endpoints for frontend
- Admin dashboard for operations

## Payment

- Cashfree as first active payment gateway
- Must not be hardcoded
- Build gateway module with interface/adapter pattern
- Future support for Razorpay, PayU, manual payment

## Hosting

### Phase 1

- Shared cPanel hosting
- okinacraft.com = Astro frontend
- admin.okinacraft.com = Laravel + Filament admin/backend
- MySQL via cPanel
- Must confirm PHP version supports selected Laravel version

### Phase 2

- Move Laravel backend/API/database to VPS when traffic, automation, queue jobs, uploads, payment callbacks, or reporting load increases
- Astro frontend can remain static on shared hosting/CDN unless Astro SSR is required
- Recommended future structure:
  - okinacraft.com = Astro static frontend
  - admin.okinacraft.com = Laravel admin on VPS
  - api.okinacraft.com = Laravel API on VPS

---

# 3. Architecture Principle

Use a **modular monolith** in Laravel.

One Laravel application and one central database, but with separated modules:

- CRM Module
- Customer Module
- Product Catalog Module
- Ecommerce/Cart Module
- Order/Sales Module
- Payment Module
- Inventory Module
- Vendor/Purchase Module
- Finance Module
- Shipping Module
- File Upload Module
- Notification Module
- Google Sheets Sync Module
- Tracking/Analytics Module

Each module should have its own:

- Models
- Migrations
- Services
- Controllers/API endpoints
- Filament resources
- Policies/permissions
- Jobs/queues
- Logs

## Fail-Safe Rule

Core business actions must complete first.

External services must not break the core system.

Examples:

- If Google Sheets sync fails, lead/order must still save.
- If WhatsApp notification fails, lead/order must still save.
- If image optimization fails, original file must still be saved.
- If courier API fails, admin should still be able to manually enter shipping details.
- If payment webhook fails, system should allow admin verification/retry.
- If inventory goes negative, order can be created but admin must get warning.

External integrations should run through background jobs where possible.

---

# 4. Website Structure

## Main Domain

`okinacraft.com`

Use Astro frontend.

Required pages:

- Home
- Product listing
- Category pages
- Product detail pages
- Cart
- Checkout
- Customer login/register
- Customer dashboard
- Services
- Gallery
- About
- Contact
- FAQ
- Insights/resource hub
- SEO landing pages
- Meta ads landing pages

## Admin Domain

`admin.okinacraft.com`

Use Laravel + Filament.

Required modules:

- Dashboard
- Leads
- Customers
- Products
- Product variants/SKUs
- Orders
- Payments
- Inventory
- Vendors
- Vendor orders/purchases
- Follow-ups
- Employees/staff
- Finance
- Shipping
- Uploads/files
- Google Sheets sync status
- Settings

---

# 5. SEO and Landing Page Indexing Rules

Use both index and noindex depending on page purpose.

## Index These Pages

Index pages that are useful for long-term SEO and stable search intent.

Examples:

- `/custom-tshirts-bulk-order`
- `/corporate-tshirt-printing`
- `/custom-polo-tshirts`
- `/custom-tshirt-printing-delhi`
- `/bulk-tshirt-printing-india`
- City/service pages
- Category pages
- Product pages
- Insights/resource pages

## Noindex These Pages

Noindex targeted offer/retargeting/private campaign pages.

Examples:

- `/meta-offer-50pcs`
- `/retargeting-discount`
- `/festival-campaign-offer`
- `/private-bulk-offer`

## Canonical Rule

If multiple landing pages are very similar, use canonical URL to the main SEO page.

Example:

- Temporary offer page canonical points to `/custom-tshirts-bulk-order`.

---

# 6. Brand and Design Direction

Okina Craft logo has:

- Strong red/orange circle
- White icon mark
- Small blue accent

Website should be:

- Premium B2B ecommerce brand
- Also friendly for single order customers
- Clean and professional
- Product-photography focused
- High whitespace
- Modern ecommerce feel
- Trust-focused for corporate/bulk buyers

Recommended color direction:

- Primary background: white / off-white
- Primary text: charcoal / near-black
- Brand accent: Okina red-orange
- Secondary accent: sky blue from logo
- Neutral: light gray / soft beige

Avoid using too much red/black together because it may feel aggressive or discount-focused.

---

# 7. Product Catalog Requirements

Products are customizable and variant-based.

## Product Types

Support:

- Simple Product
- Variable Product
- Customizable Product
- Bulk-only Product
- Made-to-order Product

## Product Statuses

Use these product statuses:

- Active
- Draft
- Out of Stock
- Bulk Only
- Hidden
- Discontinued

### Product Status Meaning

| Status | Public Website | Checkout | Admin Use |
|---|---|---|---|
| Active | Visible | Allowed | Yes |
| Draft | Not visible | Not allowed | Yes |
| Out of Stock | Visible | Disabled | Yes |
| Bulk Only | Visible | Direct checkout disabled; contact sales | Yes |
| Hidden | Not visible publicly | Not allowed publicly | Yes, internal/manual use |
| Discontinued | Usually hidden or redirected | Not allowed | History only |

## Hidden Product Use Cases

Hidden means product exists in admin/database but is not shown publicly.

Examples:

- Internal/manual sales order product
- Private product for one corporate client
- Product not ready but usable by admin
- Temporary removed product
- Special quote-only product not meant for public display

## Variants / SKU Rules

Products can have multiple variants:

- Product type
- Color
- Size
- Fabric
- Print position
- Print method

Inventory must be tracked by full SKU.

Example:

- Polo T-Shirt / Black / M
- Polo T-Shirt / Maroon / XXL
- Hoodie / Navy / XL

Variant-level rules are required.

Example:

- Polo T-Shirt / Maroon / XXL = bulk only
- Polo T-Shirt / Black / L = single + bulk

Do not only apply bulk-only at product level. It must be possible at variant level.

---

# 8. Product Customization Rules

Customers should be able to select:

- Product type
- Color
- Size
- Quantity per size
- Print position
- Logo/design upload
- Print method
- Notes/instructions for seller

## Size-wise Quantity Selector

Required for apparel.

Example:

- S: 5
- M: 10
- L: 20
- XL: 10
- XXL: 5

The cart should understand total quantity and individual size-wise quantities.

## Print Positions

Each product type must have predefined print areas.

Examples:

- Left chest
- Center chest
- Back
- Sleeve
- Cap front
- Apron center

Different products have different allowed print areas.

Admin must define print areas per product/product type.

## Print Method Rules

Admin defines available print methods using checkboxes.

Examples:

- DTF
- Sublimation
- Embroidery
- Screen printing
- Vinyl
- Other/custom

Some print methods depend on product/fabric/color.

Example rule:

- Sublimation requires polyester/light fabric.
- Black cotton + sublimation must be disabled.

Frontend should block invalid combinations with explanation.

Example message:

"Sublimation is not available on black cotton fabric. Please choose DTF/embroidery or select a light polyester fabric."

---

# 9. Mockup Generator Requirements

Use a simple mockup preview in v1.

Do not build a Canva-style editor in v1.

## Simple Mockup Preview

Customer can:

- Select product
- Select color
- Select print position
- Upload logo/design
- See uploaded design placed on predefined print area
- Move/resize roughly if possible
- Save preview with inquiry/order

## Canva-Style Editor Not Required in v1

Do not build advanced features like:

- Multi-layer editing
- Text editor
- Fonts
- Stickers/elements
- Advanced drag/drop
- Multi-page editing
- Print-ready design export

Canva-style editor can be future version.

## Product Mockup Structure

Each product should support:

- Base mockup images per color
- Print area definitions
- X/Y coordinate
- Print area width/height
- Allowed print method
- Allowed file type
- Preview image output

Admin should be able to manage print areas later.

---

# 10. File Upload Rules

Uploaded files are important because printing depends on logo/design quality.

## Max File Size

v1 max file size: 5 MB

Show message for larger files:

"For files larger than 5 MB, please send the design on WhatsApp after placing inquiry/order."

## Accepted File Types

Preview-supported:

- PNG
- JPG
- JPEG
- WEBP
- PDF
- SVG when safely handled

Store-only, preview not required:

- AI
- CDR
- PSD

Reject:

- ZIP
- RAR
- EXE
- Unknown formats
- Suspicious files

## Storage Rule

Do not store actual files inside MySQL.

Store files in Laravel storage/private disk or cloud storage later.

Database stores:

- Original filename
- Stored filename
- File path
- MIME type
- File size
- Linked type: lead/order/customer/mockup
- Linked ID
- Preview path if available
- Expiry date
- File status

## Original + Preview Rule

Always store original uploaded file.

Generate optimized preview only when possible.

Examples:

- PNG upload -> store original PNG + generate WebP preview
- JPG upload -> store original JPG + generate WebP preview
- AI/CDR/PSD upload -> store original only, no preview needed
- PDF upload -> store original + generate first-page preview if possible

## Auto Delete Rule

- Uploaded files attached to abandoned inquiries/orders: delete after 45 days
- Uploaded files attached to completed orders: delete 45 days after order completion
- Admin can mark file as protected/keep file
- After deletion, keep database record with deleted status

## File Statuses

- active
- used_in_order
- expired
- deleted
- protected

## Security Rules

- Validate by MIME type, not only extension
- Rename uploaded files
- Never use customer's original filename as stored filename
- Store original filename separately
- Block executable files
- Store original files outside public web root
- Admin download only after login
- Use secure signed URLs for downloads/previews
- Log file uploads and deletions

## Storage Folder Example

`storage/app/private/uploads/`

Suggested structure:

- leads/YYYY/MM/
- orders/YYYY/MM/
- mockups/YYYY/MM/
- temp/YYYY/MM/

---

# 11. Ecommerce Flow

## Website Direct Order Flow

Used for single/low-quantity orders.

Flow:

1. Customer browses product
2. Selects variant/customization
3. Uploads design/logo if needed
4. Adds item to cart
5. Cart can contain mixed products
6. Customer logs in or creates account
7. Enters shipping/billing address
8. Makes full online payment
9. Order is created/paid/confirmed
10. Admin reviews design
11. Production starts
12. Shipping created
13. Customer tracks order

Website direct checkout orders require full online payment by default.

## Bulk Order Flow

Bulk threshold: 25+ quantity.

If cart/product quantity reaches 25+:

- Show "Contact sales for bulk pricing"
- Disable normal direct checkout or redirect to sales quote flow
- Collect quote request
- Sales team handles customer manually
- Sales team creates manual order in admin
- Payment may be advance/partial/final
- Website has no further direct checkout role after customer contacts sales

## Sales-Team / Manual Order Flow

Sales team can create orders of any size.

Important: small sales-team orders can also use split payments.

Manual/sales orders support:

- Full payment
- Advance payment
- Partial payments
- Multiple installments
- Final payment before dispatch
- Manual payment records

Order confirmation depends on required booking amount received.

Dispatch can be blocked until required balance/final payment is received.

---

# 12. Customer Account / Dashboard

Customer account required in v1.

## Login

Build flexible authentication system so future methods can be added.

Possible methods:

- Email/password
- Phone OTP later
- WhatsApp OTP later
- Google login later

v1 can start with email/password, but phone number must be required.

## Customer Dashboard

Customer dashboard must show:

- My orders
- Order status
- Payment status
- Uploaded designs
- Saved addresses
- Reorder button
- Support/contact button
- Tracking status

---

# 13. Dynamic Customer Order Tracking

Order tracking timeline must be dynamic.

It should change based on:

- Order source: website / sales team / manual admin order
- Payment type: full payment / advance + final payment / partial payments
- Design status: uploaded / under review / issue found / approved
- Production status: not started / in production / completed
- Final/balance payment status: pending / received
- Shipping status
- Cancellation/refund status

## Customer Tracking Page Should Show

- Order ID
- Current status
- Product summary
- Quantity
- Payment status
- Design status
- Production status
- Shipping status
- Courier name
- Tracking ID
- Estimated delivery
- Timeline updates
- Support/contact button
- Feedback form after delivery

## Website Direct Order Timeline

For full-payment website orders:

- Order Placed
- Payment Received
- Design Review
- Printing Process
- Ready to Ship
- Shipped
- Delivered

## Sales-Team Order Timeline

For advance/partial/manual orders:

- Order Created
- Advance/Partial Payment Received
- Design Review
- Printing Process
- Balance Payment Pending
- Balance Payment Received
- Ready to Ship
- Shipped
- Delivered

## Design Issue Timeline

- Order Created
- Payment Received / Advance Received
- Design Review
- Waiting for Customer
- Design Approved
- Printing Process
- Ready to Ship

## Cancelled Order Timeline

- Order Created
- Cancelled
- Cancellation reason shown only if customer-safe

## Customer-Friendly Status Text

Admin can see internal statuses.

Customer should see clean, non-confusing messages.

Example:

Internal admin status:

- Negative stock: Polo Black XL -10

Customer-facing status:

- Material Arranging

Example:

Internal status:

- Balance payment pending

Customer-facing status:

- Your order is ready. Please complete the remaining payment to dispatch.

---

# 14. Order Statuses

Use these order statuses:

- Pending Payment
- Paid
- Confirmed
- Design Review
- In Production
- Ready to Ship
- Shipped
- Delivered
- Cancelled
- Refunded

Order status is separate from:

- Payment status
- Production status
- Shipping status
- Design status

Do not overload one status field with every type of process.

---

# 15. Payment Module

Payment gateway must be changeable.

Do not hardcode Cashfree/Razorpay/PayU directly into checkout logic.

## Payment Gateway Design

Use common interface:

- `PaymentGatewayInterface`
- `PaymentGatewayManager`
- Gateway implementations:
  - `CashfreeGateway`
  - `RazorpayGateway`
  - `PayUGateway`
  - `ManualPaymentGateway`

Cashfree is first active gateway in v1.

Admin should be able to choose active gateway from settings.

## Suggested Laravel Structure

`app/Modules/Payments/`

- Contracts/
  - PaymentGatewayInterface.php
- Gateways/
  - CashfreeGateway.php
  - RazorpayGateway.php
  - PayUGateway.php
  - ManualPaymentGateway.php
- Services/
  - PaymentGatewayManager.php
  - PaymentVerificationService.php
- Webhooks/
  - CashfreeWebhookHandler.php
  - RazorpayWebhookHandler.php
  - PayUWebhookHandler.php
- Models/
  - Payment.php
  - PaymentAttempt.php
  - PaymentGatewaySetting.php

## Payment Actions

Checkout should only call:

- Create payment request
- Redirect/customer pays
- Verify payment
- Handle webhook
- Mark order/payment status

Checkout should not know which gateway is active.

## Payment Support

Support:

- Online full payment
- Manual full payment
- Advance payment
- Partial payments
- Multiple installments
- Final/balance payment
- Refund record
- Failed payment
- Payment retry

---

# 16. Order / Customer / Vendor ID Format

Use public-facing formatted IDs separate from database IDs.

## Customer ID

Format:

`OCC + YY + - + 5 digits`

Example:

`OCC26-00043`

Capacity:

99,999 new customers per year.

## Customer Order ID

Format:

`OD + YY + - + 6 digits`

Example:

`OD26-001001`

Capacity:

999,999 orders per year.

## Vendor Order ID

Format:

`VO + YY + - + 5 digits`

Example:

`VO26-00034`

Capacity:

99,999 vendor orders per year.

## Sequence Rule

Sequences reset every year based on YY.

Examples:

- OCC26-00001
- OCC27-00001
- OD26-000001
- OD27-000001

## Important Rule

Public formatted ID must be different from database primary key.

---

# 17. CRM / Lead Management

Lead data sources:

- Website inquiry form
- Bulk quote request
- Product page WhatsApp click
- Landing page form
- Meta ads traffic
- Manual sales entry
- WhatsApp/manual import later

## Lead Form Fields

Collect:

- Name
- Phone number
- WhatsApp number
- Product/service needed
- Quantity
- City
- Message
- Logo/design upload

Automatically capture:

- Page URL
- Referrer
- First landing page
- UTM source
- UTM campaign
- UTM ad ID
- UTM content
- Device type
- Timestamp

## Lead Statuses

Use these lead statuses:

- New
- Message Sent
- PNP
- Important
- Follow Up
- Order Confirmed
- Not Interested
- Quoted
- Lost
- Converted to Customer

## Lead Workflow

When lead is submitted:

1. Save lead in Laravel admin
2. Send WhatsApp notification to both business numbers if integration is available
3. Send customer auto-reply if WhatsApp API is available
4. Send email notification
5. Add Google Sheets sync job
6. Assign to sales staff manually or by rule
7. Show follow-up reminder if date exists

## Sales Staff Rules

- Sales staff can see all leads
- Sales staff can act only on assigned leads
- Sales staff can see customer phone numbers
- Sales staff can see order status and payment status
- Sales staff cannot delete records
- Employee deletion requests require admin approval

---

# 18. Staff Roles and Permissions

Use these roles:

- Super Admin
- Admin
- Sales Staff
- Inventory Staff
- Finance Staff
- Production Staff

## Permission Rules

### Super Admin

Full access.

### Admin

Manage most operations except system-owner settings if needed.

### Sales Staff

Can access:

- Assigned leads
- Customer phone numbers
- Customer communication notes
- Order status
- Payment status
- Follow-ups

Cannot:

- Delete records
- See sensitive finance/profit unless allowed
- Modify inventory cost

### Inventory Staff

Can access:

- Stock quantity
- Stock movements
- Low stock alerts
- Packing/stock status

Cannot:

- See purchase cost
- See profit reports

### Finance Staff

Can access:

- Payments
- Pending balance
- Expenses/cost data if enabled
- Profit estimates
- Payment reports

### Production Staff

Can access:

- Design review status
- Production queue
- Print instructions
- Production status updates

---

# 19. Inventory Management

Inventory required in v1.

## Inventory Rules

- Track inventory by full SKU
- Support variant-level stock
- Support stock in/out
- Support reserved/assigned stock
- Support negative stock with warning
- Support low stock alerts
- Inventory staff sees quantity only, not purchase cost

## Inventory Reservation Rule

When order is confirmed / booking amount received:

- Assign/reserve inventory to customer order
- If stock is insufficient, allow negative stock but notify admin
- Show procurement/material arranging status internally

## Stock Movement Types

- Purchase stock in
- Manual stock adjustment
- Order reservation
- Order cancellation release
- Production usage
- Return/restock
- Damage/loss

## Bulk-Only and Stock Logic

If variant is bulk-only:

- Product/variant is visible
- Single checkout disabled
- Add to cart may be disabled or redirected to quote
- Show "Available for bulk order only"

If out of stock:

- Product page remains visible for SEO
- Add to cart disabled/greyed out
- Show out-of-stock message
- Do not delete SEO pages

---

# 20. Vendor / Purchase Management

Vendor order IDs use:

`VO26-00034`

Vendor module should manage:

- Vendors
- Vendor contact details
- Purchase orders
- Vendor order status
- Stock-in records
- Vendor payment status
- Purchase history
- Linked inventory movements

Vendor data should sync to Google Sheets backup tab: `Vendor Orders`.

---

# 21. Finance Management

Finance module required in v1, but start basic.

Required:

- Payment received
- Payment pending
- Partial payments
- Advance payments
- Final/balance payments
- Manual payment records
- Refund records
- Basic profit calculator
- Customer balance
- Order payment timeline

Do not overbuild expense categories in v1. Expense categories can be added later.

## Payment Status Examples

- Unpaid
- Partially Paid
- Paid
- Balance Pending
- Refunded
- Failed
- Cancelled

---

# 22. Shipping and Delivery

Current courier: Delhivery.

Use courier API if available later.

## v1 Shipping

- Pincode availability check if possible
- Delivery estimate based on courier API / pincode / zone
- Manual shipping charge formula
- Manual tracking ID entry allowed
- Admin can create shipment status manually

## Shipping Charge Rule

Do not show raw courier charge directly to customer.

Shipping/packing/convenience fee should use business formula.

Factors:

- Package size
- Product type
- Weight
- Destination pincode/zone
- Packing/convenience margin

## Shipping Statuses

Use:

- Not Started
- Packing
- Ready for Pickup
- Picked Up
- In Transit
- Out for Delivery
- Delivered
- RTO
- Lost/Damaged

## Customer Tracking

Once shipping order is created, customer should see:

- Courier name
- Tracking ID
- Tracking link if available
- Current shipping status
- Estimated delivery

---

# 23. Google Sheets Backup Sync

Google Sheets is not the main system.

Laravel dashboard/database is the main system.

Google Sheets is for offline backup/reporting.

## Sheets / Tabs

Create separate tabs:

- Leads
- Orders
- Payments
- Inventory Movements
- Customers
- Follow Ups
- Vendor Orders

## Sync Rule

Use background sync.

Flow:

1. Laravel database saves first
2. System creates sync job
3. Google Sheets updates in background
4. If Google Sheets fails, main system continues
5. Failed sync is retried later
6. Admin can see sync status/failure log

Do not make Google Sheets sync block checkout, lead save, order save, or payment update.

---

# 24. Tracking and Analytics

Install later after website creation:

- Meta Pixel
- Google Analytics 4
- UTM tracking

## Track These Actions

- Page view
- Product view
- Category view
- WhatsApp click
- Call click
- Form submit
- Quote request
- Add to cart
- Begin checkout
- Payment success
- Purchase
- Bulk quote request

## Data to Store

Capture:

- UTM source
- UTM campaign
- UTM ad ID
- UTM content
- Page URL
- Referrer
- First landing page
- Device type
- Timestamp

## WhatsApp Dynamic Messages

Every product/page should generate a contextual WhatsApp message.

Example:

Custom T-shirt page:

"Hi, I want a quote for custom t-shirts. Product page: [URL]"

Uniform page:

"Hi, I want a quote for uniforms. Product page: [URL]"

Product page:

"Hi, I am interested in [Product Name], [Color], [Size], quantity [Qty]. Page: [URL]"

---

# 25. Notifications

Send notifications to:

- okinacraft@gmail.com
- info@okinacraft.com
- WhatsApp/call number: 7827747556
- Follow-up/automation number: 8383829088

## Notification Events

- New lead
- New order
- Payment received
- Payment failed
- Design upload received
- Design issue found
- Low stock warning
- Negative stock warning
- Follow-up due
- Shipping/tracking created
- Google Sheets sync failure

Use queued jobs for external notifications.

---

# 26. Insights / Resource Hub

Use name:

`Insights`

This replaces traditional blog.

Content types:

- Product News & Spotlight
- Product material explanation
- Fabric guide
- Corporate kit guide
- Product review/case study
- Large order showcase
- Social media content support
- Brand mention/new category announcement

Resource pages should indirectly promote products.

Use SEO-friendly structure.

Example:

- `/insights/best-fabric-for-corporate-tshirts`
- `/insights/how-to-build-employee-welcome-kit`
- `/insights/minimalist-corporate-merchandise-case-study`

---

# 27. Recommended Database Modules / Tables

This is a first draft. AI coding tools/developer should refine before implementation.

## Users and Roles

- users
- roles
- permissions
- role_user / model_has_roles depending on package

## Customers

- customers
- customer_addresses
- customer_notes

## Leads / CRM

- leads
- lead_activities
- lead_follow_ups
- lead_assignments

## Products

- products
- product_categories
- product_variants
- product_skus
- product_images
- product_print_areas
- product_print_methods
- product_variant_rules
- product_bulk_rules

## Cart / Checkout

- carts
- cart_items
- checkout_sessions

## Orders

- orders
- order_items
- order_status_histories
- order_tracking_events
- order_designs
- order_notes

## Payments

- payments
- payment_attempts
- payment_schedules
- payment_gateway_settings
- payment_webhook_logs
- refunds

## Inventory

- inventory_items
- inventory_movements
- inventory_reservations
- stock_adjustments
- low_stock_alerts

## Vendors / Purchases

- vendors
- vendor_orders
- vendor_order_items
- vendor_payments
- purchase_stock_ins

## Shipping

- shipments
- shipment_events
- courier_settings

## Files

- uploaded_files
- file_previews
- file_deletion_logs

## Google Sheets

- google_sheet_sync_jobs
- google_sheet_sync_logs

## Tracking

- tracking_events
- visitor_sessions
- utm_sources

## Settings

- business_settings
- notification_settings
- payment_settings
- seo_settings

---

# 28. Frontend Folder Structure Suggestion

Astro project example:

```text
okina-frontend/
  src/
    pages/
      index.astro
      products/
        index.astro
        [slug].astro
      categories/
        [slug].astro
      cart.astro
      checkout.astro
      account/
        login.astro
        register.astro
        dashboard.astro
        orders/
          [id].astro
      services.astro
      gallery.astro
      about.astro
      contact.astro
      faq.astro
      insights/
        index.astro
        [slug].astro
      landing/
        [slug].astro
    components/
      layout/
        Header.astro
        Footer.astro
        MainLayout.astro
      product/
        ProductCard.astro
        ProductGallery.astro
        VariantSelector.astro
        SizeQuantitySelector.astro
        PrintPositionSelector.astro
        UploadDesign.astro
        MockupPreview.astro
      cart/
        CartDrawer.astro
        CartSummary.astro
      checkout/
        AddressForm.astro
        PaymentSummary.astro
      account/
        OrderList.astro
        OrderTrackingTimeline.astro
      common/
        WhatsAppButton.astro
        CTASection.astro
        FAQBlock.astro
        SEO.astro
    lib/
      api.ts
      tracking.ts
      whatsapp.ts
      seo.ts
    styles/
      global.css
  public/
    images/
    logo/
```

---

# 29. Backend Folder Structure Suggestion

Laravel project example:

```text
okina-backend/
  app/
    Modules/
      CRM/
      Customers/
      Products/
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
    Filament/
      Resources/
      Pages/
      Widgets/
    Jobs/
    Services/
    Policies/
  database/
    migrations/
    seeders/
  routes/
    api.php
    web.php
    admin.php
  storage/
    app/private/uploads/
```

This is a suggested modular structure. It can also use a package-based modular structure if the developer prefers.

---

# 30. Deployment Plan

## Phase 1: Shared Hosting

### Astro Frontend

1. Build Astro:
   - `npm run build`
2. Upload contents of `dist/` to:
   - `public_html/`
3. Configure domain:
   - `okinacraft.com`
4. Add tracking scripts after Meta Pixel and GA4 are created.
5. Ensure static assets are cached.

### Laravel Backend

1. Create subdomain:
   - `admin.okinacraft.com`
2. Create MySQL database in cPanel.
3. Upload Laravel project.
4. Configure `.env`.
5. Run migrations/seeders.
6. Set public folder correctly.
7. Configure storage link if applicable.
8. Configure cron job for Laravel scheduler:
   - file cleanup
   - Google Sheets sync retry
   - follow-up reminders
   - queue processing if supported
9. Confirm PHP version supports Laravel version.

## Phase 2: VPS

Move Laravel backend/API/database to VPS when needed.

Reasons to move:

- More traffic
- More order volume
- Payment webhook reliability
- Queued jobs
- Image optimization
- WhatsApp automation
- Google Sheets sync
- Courier API integration
- Better performance/security control

Astro frontend can remain on shared/static hosting unless SSR is needed.

---

# 31. Phased Build Plan

Build the system in connected working phases.

Every phase must run as a usable production system with limited but complete features.

Do not build separate disconnected systems for each phase.

The Laravel backend/admin must start as the shared backend from Phase 1. Later phases extend the same backend modules and database instead of replacing them.

Phase 4 should not be the first time everything connects. Phase 4 is for hardening, safety, reporting, cleanup, and deeper integration of the already-connected system.

## Phase 1: Full Ecommerce Website + Payment

Goal:

Launch a full working customer website with product browsing, cart, checkout, payment, and basic backend management.

Phase 1 must support:

- Astro public website
- Home page
- Category pages
- Product listing pages
- Product detail pages
- Product variants
- Size-wise quantity selector for apparel
- Product customization options
- Design/logo upload
- Simple mockup preview
- Add to cart
- Mixed product cart
- Customer login/register
- Checkout
- Cashfree payment integration
- Payment gateway abstraction/interface
- Order creation after payment
- Basic order status
- Admin login
- Product/category/variant/SKU management
- Basic order management
- Basic payment records
- Upload/file management
- Bulk threshold 25+ contact sales flow
- WhatsApp/contact option
- SEO basics
- Tracking placeholders

Phase 1 must run with:

```text
okinacraft.com = Astro frontend
admin.okinacraft.com = Laravel + Filament admin/backend
Laravel API = shared backend for website and admin
Cashfree = first active payment gateway
```

Important Phase 1 rules:

- Do not hardcode Cashfree directly into checkout.
- Use `PaymentGatewayInterface` and gateway adapter pattern from the beginning.
- Keep inventory simple if needed, but keep SKU structure ready for Phase 2.
- Keep CRM simple if needed, but keep lead/contact data ready for Phase 3.
- Customer orders and payment records must use the same database that later phases will extend.

## Phase 2: Inventory + Customer + Purchase + Sales Operations

Goal:

Add real business operations around customer orders, inventory, purchase, sales/manual orders, and customer tracking.

Phase 2 must add or expand:

- Product SKU inventory
- Variant-wise stock
- Stock in/out
- Stock reservation for orders
- Low stock alerts
- Negative stock warning
- Customer records
- Customer address management
- Sales/manual order creation
- Split payment support
- Advance payment
- Balance/final payment
- Basic finance/payment tracking
- Vendor records
- Vendor orders/purchases
- Purchase stock-in
- Production/design status
- Shipping status
- Dynamic customer order tracking page
- Customer-friendly tracking timeline/status text
- File cleanup after 45 days

Phase 2 connection flow:

```text
Website Order
  -> Customer
  -> Order Management
  -> Payment Record
  -> Inventory Reservation
  -> Production Status
  -> Shipping Status
  -> Customer Tracking
```

Important Phase 2 rules:

- Checkout from Phase 1 must continue working.
- Inventory warnings must not crash checkout or order creation.
- If stock is insufficient, allow order creation when business rules allow it, but warn admin.
- Purchase/vendor management must connect to inventory, not exist as a separate isolated list.
- Customer tracking must show customer-friendly status, not every internal operational status.

## Phase 3: CRM + Staff Permissions + Automation

Goal:

Add sales department workflow, staff permissions, lead management, follow-ups, and automation.

Phase 3 must add or expand:

- CRM lead capture
- Lead sources
- Product inquiry leads
- Landing page leads
- WhatsApp click tracking
- UTM/referrer tracking
- Lead assignment
- Lead notes/activity
- Follow-up reminders
- Lead statuses
- Sales staff dashboard
- Staff roles and permissions
- Sales staff permissions
- Inventory staff permissions
- Finance staff permissions
- Production staff permissions
- Notification system
- WhatsApp automation if available
- Email notifications
- Google Sheets backup sync
- Meta Pixel / GA4 event tracking
- Background jobs
- Failed job retry/logging

Phase 3 connection flow:

```text
Lead
  -> Sales Follow-up
  -> Customer
  -> Quote/Order
  -> Payment
  -> Inventory
  -> Production
  -> Delivery
```

Important Phase 3 rules:

- Automation must run through background jobs where possible.
- WhatsApp, email, Google Sheets, and tracking failures must not block lead/order/customer creation.
- Staff permissions must protect finance cost/profit data and deletion actions.
- Sales staff can work on assigned leads/orders but cannot delete core records.

## Phase 4: Unified Backend Safety + Hardening

Goal:

Strengthen the already-shared backend so website, admin, ecommerce, inventory, sales, CRM, finance, and automation work safely as one connected system.

Phase 4 must add or improve:

- Unified admin dashboard
- Shared customer/order/payment/inventory views
- Module-level logs
- Audit history
- Retry system for failed integrations
- Backup/export system
- Settings panel
- Security review
- Permission review
- Payment webhook review
- File upload security review
- Database cleanup rules
- Performance optimization
- Deployment hardening
- Reporting improvements
- Error handling improvements
- Queue/scheduler reliability
- VPS migration if shared hosting becomes limiting
- Future gateway adapters such as Razorpay/PayU
- Advanced reports
- Profit dashboard
- Customer reorder automation
- SEO city pages at scale
- Product feed for ads
- Advanced abandoned cart/lead recovery

Final connected system:

```text
Website
  -> Products
  -> Cart
  -> Checkout
  -> Payment
  -> Orders

Orders
  -> Customer
  -> Inventory
  -> Production
  -> Shipping
  -> Finance
  -> Tracking

CRM
  -> Leads
  -> Sales Staff
  -> Follow-ups
  -> Quotes
  -> Orders

Admin
  -> Products
  -> Customers
  -> Orders
  -> Payments
  -> Inventory
  -> Purchases
  -> CRM
  -> Staff
  -> Reports
  -> Settings
```

Important Phase 4 rules:

- Do not rebuild the system from scratch.
- Do not create a second backend.
- Do not break working Phase 1/2/3 features.
- Use safe migrations, tests, backups, and staged deployment.
- Failure in one module must not stop unrelated modules.
- Core business actions must complete first; integrations and automation are secondary.

---

# 32. AI Coding Tool Instructions

Use this project as a professional production system, not a quick prototype.

## General Coding Rules

- Write clean, modular code.
- Do not hardcode business-critical settings.
- Use environment variables for keys/secrets.
- Validate all user inputs.
- Secure all file uploads.
- Use background jobs for slow integrations.
- Keep public website fast.
- Avoid blocking checkout with external sync.
- Separate frontend, backend, CRM, inventory, finance, and sales modules logically.
- Use database transactions for order/payment/inventory changes.
- Log integration errors clearly.
- Never expose admin/private files publicly.
- Build with future scalability in mind.

---

# 33. Initial Codex Prompt

Use this prompt to start the project with an AI coding tool:

```text
You are building the Okina Craft ecommerce and admin system.

Create a production-ready project architecture based on this instruction file.

The system has:
- Astro + Tailwind frontend for okinacraft.com
- Laravel 13 + Filament + MySQL backend/admin for admin.okinacraft.com
- Modular monolith backend architecture
- Ecommerce product catalog with variable/customizable products
- Cart and checkout
- Customer accounts
- Cashfree payment through replaceable PaymentGatewayInterface
- Manual/sales-team split payment support
- Inventory by SKU
- CRM leads/follow-ups
- Order management
- File uploads with previews and 45-day cleanup
- Dynamic customer order tracking
- Google Sheets background backup sync
- Tracking/SEO rules

Start by generating:
1. Recommended repository/folder structure
2. Database migration plan
3. Laravel module structure
4. Astro page/component structure
5. Four-phase build order
6. Security and deployment checklist

Follow this phase structure:
- Phase 1: full ecommerce website with product listing, cart, checkout, Cashfree payment, customer account, uploads, simple mockup preview, and basic admin.
- Phase 2: inventory, customer, purchase, sales/manual orders, split payments, production/shipping statuses, and customer tracking.
- Phase 3: CRM lead management, staff permissions, notifications, Google Sheets backup sync, tracking, and automation.
- Phase 4: unified backend hardening, safety, logs, reports, retries, backups, permissions review, and performance/deployment hardening.

The backend must be shared from Phase 1. Phase 4 is not the first connection phase; it is the safety and hardening phase for the already-connected system.

Do not hardcode Cashfree directly into checkout. Build a payment gateway abstraction.
Do not store uploaded files in MySQL. Store files in private storage and save metadata in database.
External integrations must fail safely and run in background jobs where possible.
```

---

# 34. Build Order for AI Coding Tool

Follow this connected phase order:

## Phase 1 Build Order: Ecommerce Website + Payment

1. Laravel backend foundation
2. Modular backend structure
3. Admin login
4. Basic roles required for Phase 1
5. Product categories
6. Products
7. Product variants/SKUs
8. Product images
9. Product customization fields
10. File upload module
11. Simple mockup preview storage
12. Customer accounts
13. Cart API
14. Checkout API
15. Payment module interface
16. Cashfree gateway implementation
17. Payment attempts and verification
18. Order creation after payment
19. Basic order management
20. Basic payment records
21. Astro frontend pages
22. Astro product listing/detail pages
23. Astro cart/checkout/customer account pages
24. Bulk threshold 25+ quote/contact flow
25. WhatsApp/contact buttons
26. SEO basics and tracking placeholders
27. Phase 1 deployment instructions

## Phase 2 Build Order: Inventory + Customer + Purchase + Sales Operations

1. Expand SKU inventory structure
2. Inventory movements
3. Inventory reservations
4. Low stock alerts
5. Negative stock warning
6. Customer records and addresses
7. Manual/sales order creation
8. Split payment support
9. Advance/balance/final payment records
10. Vendor records
11. Vendor orders/purchases
12. Purchase stock-in
13. Production/design statuses
14. Shipping statuses
15. Dynamic customer order tracking
16. Customer-friendly tracking timeline
17. Basic finance/payment tracking
18. File cleanup after 45 days
19. Phase 2 testing and migration safety checks

## Phase 3 Build Order: CRM + Staff Permissions + Automation

1. CRM lead module
2. Lead source tracking
3. Product inquiry and landing page lead capture
4. UTM/referrer tracking
5. Lead statuses
6. Lead assignment
7. Lead notes/activity
8. Follow-up reminders
9. Sales staff dashboard
10. Staff roles and permissions expansion
11. Sales/inventory/finance/production permission rules
12. Notification module
13. Email notifications
14. WhatsApp automation if available
15. Google Sheets background backup sync
16. Meta Pixel / GA4 tracking
17. Failed job retry/logging
18. Phase 3 automation safety checks

## Phase 4 Build Order: Unified Backend Safety + Hardening

1. Unified admin dashboard
2. Shared operational views across modules
3. Module-level logs
4. Audit history
5. Integration retry system
6. Backup/export system
7. Settings panel
8. Security review
9. Permission review
10. Payment webhook review
11. File upload security review
12. Database cleanup rules
13. Performance optimization
14. Queue/scheduler reliability
15. Reporting improvements
16. Deployment hardening
17. VPS migration if shared hosting becomes limiting
18. Future gateway adapters such as Razorpay/PayU
19. Advanced growth features as needed

---

# 35. Final Confirmation

This instruction file defines the full Okina Craft system:

- Website frontend is different from Laravel backend
- CRM is a backend module using lead/customer/order data
- Inventory is a backend module connected to products/orders/vendor purchases
- Finance is a backend module connected to payments/orders/purchases
- Sales management connects CRM, orders, payments, and inventory
- Google Sheets is backup/reporting only, not the main system
- Frontend should be fast, SEO-friendly, and conversion-focused
- Backend should be operationally reliable, modular, and scalable
