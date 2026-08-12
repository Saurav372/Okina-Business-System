# Okina Dashboard to Storefront Integration

Updated: 11 August 2026

## Dashboard capability audit

The admin dashboard is the source of truth for:

- Product identity, descriptions, publishing, visibility, ordering limits, customization mode, and fulfilment type
- Product variants, SKU combinations, price, compare-at price, direct-checkout flags, quote requirements, and stock behaviour
- Inventory balances and low-stock state, synchronized back to each SKU
- Product cover/gallery media, ordering, and alt text
- Product canonical, robots, Open Graph, and Twitter metadata
- Order, design, production, shipping, courier tracking, payment, and refund status
- Business name, support contact, currency, tax-display preference, and checkout availability settings

## Connected storefront contracts

| Dashboard source | Public contract | Storefront consumer |
| --- | --- | --- |
| Product and category records | `/api/catalog/categories`, `/api/catalog/products` | Homepage, collections, search, product pages |
| Variants, SKUs, pricing, inventory | Product and customization responses | Product selector, live price, availability, cart validation |
| Product media | Product `cover_image` and ordered `media` plus `/api/catalog/media/{public_id}` | Product cards, homepage feature, PDP gallery, social images |
| Product SEO & Social | Product `seo` object | Title, description, canonical, robots, Open Graph, Twitter, Product schema |
| Business and commerce settings | `/api/catalog/storefront` | Brand name, support links, tax wording, robots and checkout availability |
| Orders and fulfilment | `/api/customer/orders/*` | Account history, order detail, tracking, payment/refund display |

Public media delivery is limited to active image files marked `public_safe_preview` and attached to a publicly visible product. Private customer artwork remains behind authenticated, signed access.

## Intentionally not connected yet

- Customer proof approve/request-change: no authoritative customer proof API exists yet
- Reviews and ratings: no dashboard review module or public review contract exists
- Customer administration: the dashboard navigation currently has no customer-management items
- Production payment verification: requires live gateway credentials and callback testing

These gaps should be implemented in the dashboard/backend first, then exposed through a narrow public or customer-authenticated contract.
