# API Documentation

> **Last Reviewed:** 2026-07-02
> **Owner:** Engineering
> **Source of Truth:** `apps/backend/routes/web.php`, `apps/backend/routes/api.php`, `apps/backend/app/Http/Controllers/*`, `apps/backend/app/Http/Requests/*`

---

## API Version

**Version:** v1

There is no version prefix in URL paths (e.g. no `/api/v1/`). This is a V1 baseline. If a breaking change is needed, a version prefix will be introduced at that point.

---

## Base URLs

| Context | Base URL |
|---|---|
| Public API (customer website) | `/api/` |
| Admin (web routes, session auth) | `/admin/` |
| Payment webhooks | `/api/webhooks/` |

---

## Authentication

### Admin (Staff)
- Session-based authentication using Laravel's `web` guard.
- Login via `POST /admin/login`.
- All subsequent admin requests require a valid session cookie.
- Unauthenticated requests return `302 redirect` to `/admin/login`.

### Customer
- Session-based authentication using a separate `customer` guard.
- Login via `POST /api/auth/login`.
- Unauthenticated customer requests return `401 Unauthorized`.

### Webhooks
- Cashfree webhook signature verification via HMAC (`X-Webhook-Signature` header).
- No session or API key — verified by signature only.

---

## Standard Response Formats

### Success (200)
```json
{
  "data": { ... }
}
```

### Paginated List (200)
```json
{
  "data": [ { ... } ],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 87
  }
}
```

### Validation Error (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message here."],
    "nested.field": ["Another error."]
  }
}
```

### Unauthorized (403)
```json
{
  "message": "This action is unauthorized."
}
```

### Not Found (404)
```json
{
  "message": "No query results for model [App\\Models\\Order]."
}
```

### Server Error (500)
```json
{
  "message": "Server Error"
}
```

---

## Pagination

Default page size: **20 records per page**.
Maximum allowed page size: **100 records per page** (enforced in controllers).
Query parameter: `?page=2&per_page=50`

---

## Rate Limiting

| Route | Limit |
|---|---|
| `POST /api/webhooks/payments/cashfree` | 5 requests per minute |
| Admin login | Default Laravel throttle |
| Customer login | Default Laravel throttle |

---

## Endpoint Groups

### Public Catalog (No Authentication)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/catalog/categories` | List public product categories |
| `GET` | `/api/catalog/categories/{slug}` | Category detail with products |
| `GET` | `/api/catalog/products` | List public products |
| `GET` | `/api/catalog/products/{slug}` | Product detail with variants and SKUs |
| `GET` | `/api/catalog/customization-options` | Customization options for a SKU |

---

### Customer Authentication

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/auth/register` | Create customer account |
| `POST` | `/api/auth/login` | Customer login |
| `POST` | `/api/auth/logout` | Customer logout |
| `GET` | `/api/auth/me` | Get authenticated customer profile |

---

### Customer Dashboard (Requires Customer Auth)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/customer/orders` | List customer orders |
| `GET` | `/api/customer/orders/{public_id}` | Order detail with payment summary |
| `GET` | `/api/customer/orders/{public_id}/tracking` | Customer-safe tracking view |
| `GET` | `/api/customer/addresses` | List saved addresses |
| `POST` | `/api/customer/addresses` | Add address |

---

### Cart and Checkout (Requires Customer Auth)

| Method | Path | Description |
|---|---|---|
| `GET` | `/api/cart` | Get current cart |
| `POST` | `/api/cart/items` | Add item to cart |
| `PATCH` | `/api/cart/items/{id}` | Update cart item |
| `DELETE` | `/api/cart/items/{id}` | Remove cart item |
| `POST` | `/api/cart/checkout` | Submit checkout → creates pending order + payment attempt |

---

### Admin Orders (Requires `orders.view` / `orders.manage`)

| Method | Path | Permission | Description |
|---|---|---|---|
| `GET` | `/admin/orders` | `orders.view` | Paginated order list with filters |
| `GET` | `/admin/orders/{public_id}` | `orders.view` | Order detail with payment summary |
| `PATCH` | `/admin/orders/{public_id}/status` | `orders.manage` | Update order status |
| `PATCH` | `/admin/orders/{public_id}/design-approval` | `orders.manage` | Record design approval |
| `PATCH` | `/admin/orders/{public_id}/shipping` | `orders.manage` | Record shipping details |
| `POST` | `/admin/orders` | `orders.create` | Create manual sales order |

---

### Admin Payments and Finance (Requires `finance.*` permissions)

| Method | Path | Permission | Description |
|---|---|---|---|
| `GET` | `/admin/payments` | `finance.view` | Paginated payment ledger |
| `GET` | `/admin/payments/{public_id}` | `finance.view` | Payment detail |
| `POST` | `/admin/orders/{order}/payments` | `payments.record` | Record manual payment |
| `GET` | `/admin/refunds` | `finance.view` | List refunds |
| `POST` | `/admin/refunds` | `refunds.request` | Request a refund |
| `POST` | `/admin/refunds/{id}/approve` | `refunds.approve` | Approve a refund |
| `GET` | `/admin/finance/report` | `reports.view` | Financial summary report |
| `GET` | `/admin/expenses` | `finance.manage_expenses` | List expenses |
| `POST` | `/admin/expenses` | `finance.manage_expenses` | Create expense |
| `GET` | `/admin/expenses/report` | `reports.view` | Expense report |

---

### Admin Inventory (Requires `inventory.*` permissions)

| Method | Path | Permission | Description |
|---|---|---|---|
| `GET` | `/admin/inventory` | `inventory.view` | SKU stock balances |
| `POST` | `/admin/inventory/{sku}/stock-in` | `inventory.manage` | Record stock-in |
| `POST` | `/admin/inventory/{sku}/stock-out` | `inventory.manage` | Record stock-out |
| `POST` | `/admin/inventory/{sku}/adjust` | `inventory.manage` | Manual adjustment |
| `GET` | `/admin/inventory/{sku}/movements` | `inventory.view` | Movement history |

---

### Admin CRM (Requires `crm.*` permissions)

| Method | Path | Description |
|---|---|---|
| `GET/POST` | `/admin/leads` | List / create leads |
| `GET/PATCH` | `/admin/leads/{id}` | Lead detail / update |
| `POST` | `/admin/leads/{id}/follow-ups` | Add follow-up |
| `GET/POST` | `/admin/quotations` | List / create quotations |
| `POST` | `/admin/quotations/{id}/send` | Send quotation to customer |
| `GET` | `/admin/quotations/{token}/approve` | Customer approval (public link) |

---

### Admin Vendors and Purchases

| Method | Path | Description |
|---|---|---|
| `GET/POST` | `/admin/vendors` | List / create vendors |
| `GET/POST` | `/admin/purchase-orders` | List / create purchase orders |
| `POST` | `/admin/purchase-orders/{id}/status` | Update purchase order status |
| `POST` | `/admin/purchase-orders/{id}/items/{item}/receive` | Receive stock |

---

### Admin Audit Log (Requires `audit.view`)

| Method | Path | Description |
|---|---|---|
| `GET` | `/admin/audit-logs` | Paginated audit log with filters |
| `GET` | `/admin/audit-logs/{event_id}` | Audit log event detail |

---

### Admin Notifications (Requires `notifications.view`)

| Method | Path | Description |
|---|---|---|
| `GET` | `/admin/notification-logs` | Paginated notification log |
| `GET` | `/admin/notification-logs/{id}` | Log detail with delivery attempts |

---

### Admin Settings (Requires `settings.manage`)

| Method | Path | Description |
|---|---|---|
| `GET` | `/admin/settings` | Read all settings |
| `PATCH` | `/admin/settings` | Update settings |
| `POST` | `/admin/google-sheets/test-connection` | Test Google Sheets connection |

---

### Payment Webhooks (Signature-verified, No Session)

| Method | Path | Description |
|---|---|---|
| `POST` | `/api/webhooks/payments/cashfree` | Receive and process Cashfree payment webhook |
