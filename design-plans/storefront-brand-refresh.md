# Okina storefront brand refresh

## Goal and direction

Help creators, teams, and businesses move from the homepage to a suitable garment. Keep Okina's existing logo, Archivo/DM Sans typography, flame accent (#e83535), and ink neutral (#1a1a1a). Use the darker red token for small text and white-label buttons. Warm paper surfaces and restrained 4px/8px corners support the apparel imagery.

## Mobbin evidence reviewed

- [Urban Outfitters](https://mobbin.com/screens/56b8a9eb-dcf1-4f04-9aae-2810e36500d9): visible category navigation and accessible search alongside collection imagery. Adopt clear discovery entry points; adapt dense navigation to a mobile menu.
- [lululemon](https://mobbin.com/screens/8acb3ddb-3478-4461-b40f-9fad9ed16688): a strong campaign focal point with explicit shopping actions. Adapt to separate text and image columns so Okina's copy remains readable.
- [adidas](https://mobbin.com/screens/e3a4181b-90f7-400b-a6d2-4d4eedc7f0a3): concise campaign actions and product discovery directly below. Adopt the clear hierarchy; reject borrowed brand identity and promotional claims without supporting data.

## Implemented decisions

- First viewport: brand identity, headline, supporting copy, primary collection action, separate how-it-works action, existing campaign asset.
- Mobile: stacked hero, compact brand header, 44px minimum header controls, scrollable menu with labelled search, two-column information strip and process list.
- Product cards: server-provided product and price data; no fabricated rating; extra colors counted from distinct colors rather than SKU count; visible starting-price wording.
- Bag: retain existing cart endpoints; trap keyboard focus, make the background inert while open, restore the triggering control on close, and preserve navigation from the empty-cart link.
- Homepage and announcement: remove unsupported ratings, dispatch guarantees, coupons, fabric-wide claims, and wholesale service promises.
- State ownership: Laravel continues to own catalogue publication, availability, options, prices, cart validation, and checkout. Existing server-rendered empty states remain. This change introduces no async loading state or new analytics provider; existing analytics handling remains in place.

## Acceptance evidence

- Vite production build and Blade template cache succeeded.
- StorefrontPageTest, StorefrontCutoverTest, CartStorageTest, and CartValidationTest: 22 tests, 232 assertions passed with SQLite extensions enabled for the test process.
- Local preview uses PHP with SQLite extensions enabled. A stale public/hot file pointing at an offline Vite server was moved to .codex-run-logs/hot-before-brand-refresh so compiled assets load.
- Catalogue imagery remains dashboard-managed; missing product photos use the existing fallback artwork.
- Desktop and 375px viewport reviewed in the app browser; no horizontal page overflow at the mobile viewport. Mobile navigation opens and exposes labelled search. Bag focus cycles in both directions and Escape returns focus to the trigger.

## Reference image implementation — 10 September 2026

The user's supplied full-page reference supersedes the earlier discovery layout. Implemented its compact black announcement, text wordmark, business-focused split hero, benefit strip, four-step strip, four-column category and product grids, business inspiration gallery, printing-method cards, bulk/small-order split, and dark process band.

- Added eight generated campaign/style assets under public/storefront/business. Homepage style previews are labelled; product-detail catalogue media is preserved. Generated business examples are described as inspiration, not actual customer testimonials.
- Category names, product names, prices, availability data and destinations remain backed by the existing catalogue. Homepage features four products, with all products reachable through View All Products.
- Printing method cards expand with native keyboard-accessible details/summary controls.
- Bulk quote email is offered only when a support email is configured. Otherwise the homepage provides Explore Bulk Orders and Choose Bulk Apparel links, plus order-planning guidance. No unconfigured WhatsApp contact or fake quote submission was added.
- Added mobile-menu dismissal when navigating to a homepage section.
- Validation: production build, Blade cache, JS syntax and diff checks pass. Storefront/cart regression set passes: 22 tests, 235 assertions. Desktop 1440px and mobile 390px viewport checks show no page-level horizontal overflow. Printing-method expansion and mobile bulk navigation verified.
