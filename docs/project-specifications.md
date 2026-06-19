# Okina Craft Project Specifications

## Platform A: Shared Backend and Core Services

Project Name:

Platform A: Shared Backend and Core Services

Purpose:

Provide the common Laravel backend, database, domain rules, APIs, services, security, jobs, files, logs, shared order/payment domain, notifications, and audit foundations used by both the customer website and business admin.

Users:

- Customer website
- Business admin
- Super Admin
- Admin
- Sales Staff
- Inventory Staff
- Finance Staff
- Production Staff
- Background jobs
- External integrations

Main Features:

- A1 Architecture and Database
- A2 Authentication and Permissions
- A3 Shared Business Data
- A4 Platform Services
- A5 Shared Order and Payment Domain

Shared Data:

- Users
- Roles and permissions
- Customers
- Addresses
- Products
- Categories
- Variants
- SKUs
- Orders
- Quotations
- Payments
- Payment attempts
- Uploaded files
- Settings
- Notifications
- Audit logs

Dependencies:

- Must be created before Project B and Project C feature work.
- Must define shared rules before website checkout or admin order management is implemented.

## Project B: Customer Ecommerce Website

Project Name:

Project B: Customer Ecommerce Website

Purpose:

Provide the public Astro ecommerce website for product discovery, customization, cart, checkout, payment, customer account, tracking, SEO pages, and bulk enquiry capture.

Users:

- Website visitors
- Retail/single-order customers
- Bulk buyers
- Existing customers
- Marketing traffic from SEO and ads

Main Features:

- B1 Catalog and Product Discovery
- B2 Product Customization and Mockup Preview
- B3 Cart, Checkout and Website Payment
- B4 Customer Account and Tracking

Shared Data:

- Product categories
- Products
- Variants
- SKUs
- Product images
- Product print areas
- Uploads
- Customers
- Addresses
- Carts
- Orders
- Payments
- Tracking events
- Leads and bulk enquiries
- UTM and visitor tracking data

Dependencies:

- Requires Platform A.
- Uses the shared product, customer, order, payment, upload, and tracking APIs.
- Checkout depends on shared product/SKU data and shared order/payment rules.
- Customer tracking depends on admin order, payment, and shipping updates.

## Project C: Business Operations Admin

Project Name:

Project C: Business Operations Admin

Purpose:

Provide the Laravel Filament admin/business operations system for products, customers, orders, sales orders, quotations, payments, inventory, vendors, purchases, CRM, staff workflows, production, shipping, finance, reporting, automation, audit, and hardening.

Users:

- Super Admin
- Admin
- Sales Staff
- Inventory Staff
- Finance Staff
- Production Staff

Main Features:

- C1 Orders, Sales Orders and Quotations
- C2 Inventory, Vendors and Purchases
- C3 CRM and Staff Workflow
- C4 Production and Shipping
- C5 Finance and Reporting
- C6 Automation, Audit and Hardening

Shared Data:

- Customers
- Leads
- Quotations
- Orders
- Payments
- Refunds
- Products
- SKUs
- Inventory movements
- Vendors
- Purchases
- Production updates
- Shipments
- Uploaded files
- Audit logs
- Notification logs
- Reports

Dependencies:

- Requires Platform A.
- Basic admin order management must exist before customer tracking is complete.
- Inventory, finance, CRM, production, and automation all depend on shared order/payment/domain rules.
