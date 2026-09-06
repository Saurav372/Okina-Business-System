# Astro-to-Blade Storefront Migration Plan

> **Status:** Completed 2026-09-03
> **Prepared:** 2026-09-01
> **Approved design:** `UI test/concepts/design-05/`
> **Scope:** Replace the customer-facing Astro runtime with Laravel Blade, implement Design 05 as the storefront UI, and preserve the current public URLs, SEO, business rules, and production behavior.
> **Out of scope:** Creating another visual direction, changing payment providers, changing the database schema, or replacing the existing admin interface.
> **Implemented 2026-09-01:** Design 05 Blade/Vite foundation, shared storefront layout/components, responsive drawer/mobile dock, live home page, collection index/detail, search, local Archivo/DM Sans assets, catalogue/SEO tests, and desktop/mobile rendered QA.
>
> **Implemented 2026-09-02:** Design 05 Blade product detail and customizer, live option/SKU matching, price and availability feedback, print-position/method compatibility, quantity presets, media/fallback preview, product metadata/JSON-LD, preserved validation errors, and a same-origin CSRF-protected add-to-cart action using the existing cart snapshot/service rules.
>
>
> **Repository Layout Update 2026-09-06 (ADR-10):** Following the successful migration and retirement of Astro, the repository was flattened from `apps/backend/` into the project root. Historical paths in this document referencing `apps/backend/` now reside directly at the project root (`/`).

## 1. Recommendation

Migrate the storefront into the existing Laravel application, implement Design 05 in Blade, and deploy Okina Craft as one PHP application.

This is a presentation-layer migration and approved redesign, not a business-logic rewrite. Design 05 is the visual and interaction target. The current Astro application is the behavioral reference for URLs, metadata, structured data, authentication recovery, and production edge cases. Laravel remains the authority for catalog, customization, pricing, cart, checkout, customer, payment, proof, file, and order rules.

Laravel already owns the important rules and operations through `PublicCatalogContract`, `CustomizationOptionContract`, `CartService`, `CartResponsePresenter`, `CartValidationService`, `CheckoutValidationService`, `CheckoutPendingOrderService`, `SettingsService`, customer authentication, protected files, payment initiation, and order timelines. Blade controllers and views should call those services directly instead of making HTTP requests back into the same Laravel application.

Keep JavaScript only where it materially improves the experience:

- product option/SKU selection and artwork preview;
- the canvas-based mockup studio;
- catalog filtering and sorting;
- navigation, announcement rotation, and analytics;
- optional progressive enhancement for cart, addresses, checkout, and reorder actions.

Normal page content, authentication state, cart state, account data, order data, errors, and empty states should be rendered on the server.

## 2. Approved Design Baseline — Design 05

Design 05 is the approved storefront direction. Its retail shell, proof-first messaging, red/ink/white visual system, product cards, customizer composition, cart summary, checkout split layout, success panels, customer order cards, proof timeline, mobile drawer, and mobile bottom navigation should be translated into reusable Blade components.

### Reference priority

Use the following order when implementation evidence conflicts:

1. Laravel services and tests for business rules, availability, prices, totals, permissions, ownership, payments, and files.
2. Existing public Astro URLs, SEO output, redirects, and meaningful production states for compatibility.
3. Approved Design 05 screenshots for visual composition and responsive intent.
4. Design 05 HTML/CSS/JavaScript for component anatomy and prototype interactions.

Phase 0 must resolve and record any discrepancy between the stored Design 05 screenshots and the latest prototype CSS before visual implementation begins.

### Prototype-to-production mapping

| Design 05 reference | Production destination | Migration rule |
|---|---|---|
| `index.html` | `/` | Use the hero, trust row, category circles, product rails, campaign panels, proof-first steps, reviews, retail header/footer, and mobile dock with real catalog/settings data. |
| `shop.html` | `/categories`, `/categories/{slug}`, `/search` | Reuse the collection hero, sticky filter toolbar, product grid, sort/filter controls, result count, and empty state. Query parameters and server data remain authoritative. |
| `create.html` | `/products/{slug}` and `/mockup-generate` | Use the gallery/configurator split, quantity tiers, size distribution, colour/artwork controls, proof notice, pricing summary, delivery check, and sticky mobile action. Bind all options to real SKU/customization rules. |
| `cart.html` | `/cart` | Use the configured-item anatomy, edit/remove/restore affordances, coupon area, proof message, empty state, and sticky desktop summary. Totals and validity come from Laravel. |
| `checkout.html` | `/checkout` | Use the focused checkout header, delivery form, payment cards, proof-before-print notice, retained-input messaging, and order summary. Use the existing gateway and checkout services. |
| `confirmation.html` | `/order-confirmation/{order:public_id}` | Use the success hero and fact grid with the real order, payment, proof, and customer state. |
| `quote.html` | Bulk handoff result from product/cart/checkout | Use this as the visual confirmation state for the existing 25+ quantity handoff. Add a dedicated public route only when a real customer quotation/request record and ownership rules are implemented. |
| `account.html` | `/account` | Use the account rail, attention banner, order cards, status labels, payment recovery, reorder, quotation, address, and saved-design sections backed by the customer portal presenter. |
| `track.html` | `/track-order` and `/account/orders/{order:public_id}` | Use the progress timeline, proof review card, support block, approval/change actions, and production gate with customer-scoped order/proof data. |

### Design system to port

- **Typography:** Archivo for display/brand text and DM Sans for interface/body text, using locally hosted font files or a documented production font strategy. Do not depend on a runtime Google Fonts request unless deployment and privacy policy explicitly allow it.
- **Palette:** ink `#151515`, primary red `#ef3038`, strong red `#c8202a`, blush surface `#fff1f1`, muted text `#686263`, border `#e6dada`, and white. Convert these into semantic storefront tokens rather than scattering literal values.
- **Shape and density:** mostly square controls and cards, low radii, thin rules, generous editorial whitespace, bold display headings, compact metadata, and 48–50 px primary controls.
- **Responsive intent:** desktop retail navigation and split layouts; below the established breakpoints, use a drawer, two-column product grids where safe, stacked creator/cart/checkout/account layouts, a sticky creator action, and the Design 05 mobile bottom dock.
- **Motion:** restrained image scale/hover feedback and optional announcement behavior; provide the prototype's reduced-motion override and do not make motion necessary to understand state.

### Prototype details that must not enter production unchanged

- Replace `localStorage` and `sessionStorage` demo state with Laravel session/database state and server-rendered recovery.
- Replace hard-coded products, prices, ratings, discounts, coupons, delivery charges, customer names, order IDs, quotation IDs, dates, proof data, and availability with real presenter/service output.
- Replace static links and timed fake checkout success with named routes, CSRF-protected forms, actual validation, and the existing payment gateway flow.
- Treat the 25+ quote screen as a presentation reference. The backend currently exposes a `bulk_handoff` decision but does not expose a public quotation-request route that safely creates a customer-owned quotation.
- Use real catalog media for product cards and galleries. Move prototype campaign artwork only after confirming ownership and recording the final asset source.
- Replace text glyphs used as interface icons with the project's approved icon system while preserving accessible labels and touch targets.
- Productionize every demo-only interaction with disabled, busy, success, validation, unavailable, permission, server-error, retry, and no-JavaScript states.

## 3. Evidence from the Repository

### Current frontend

- `apps/frontend/astro.config.mjs` uses `output: 'server'` with the standalone Node adapter, so the current frontend requires a running Node process.
- `apps/frontend/src/` contains 15 route modules, three reusable Astro components, one shared layout, one catalog client, one 430-line mockup TypeScript module, and approximately 196 KB of page source.
- `apps/frontend/src/layouts/CatalogLayout.astro` owns SEO metadata, structured data support, navigation, footer, announcements, analytics hooks, authentication state, cart count, and responsive menus.
- `apps/frontend/src/lib/catalog.ts` makes server-side HTTP calls to Laravel for public catalog and storefront settings data.
- Cart, checkout, account, order detail, tracking, confirmation, product customization, and mockup flows make browser-side cross-origin JSON requests to Laravel with session credentials.
- `apps/frontend/src/styles/global.css` contains the storefront tokens and global styling. Many route and component styles are additionally scoped inside `.astro` files.
- Storefront assets currently exist only under `apps/frontend/public/brand` and `apps/frontend/public/mockups`; the corresponding files are not present in `apps/backend/public`.

### Current Laravel application

- `apps/backend/resources/views` already contains 116 Blade views and an established Blade component/layout system.
- `apps/backend/routes/web.php` owns authentication, admin, file, manifest, and current placeholder root routes.
- `apps/backend/routes/api.php` owns public catalog, customization, cart, customer account, checkout, and payment webhook endpoints.
- The public catalog and customization payload rules already live behind reusable contracts rather than inside Astro.
- Cart pricing and mutations already live in reusable services; Blade must not recalculate money, stock, availability, or checkout eligibility.
- Customer account formatting is currently private to `CustomerApiController`. It needs extraction into a shared presenter/query service before Blade and JSON endpoints can reuse it without duplication.
- `CustomerAuthController::account()` currently redirects to `PUBLIC_SITE_URL/account`; this must become a Blade response at cutover.
- `config/cors.php` is hard-coded to local Astro origins.
- Session-coupled cart and customer mutation routes currently disable CSRF validation to support the cross-origin Astro client. This exception should not survive the final same-origin architecture.
- The backend already has extensive feature coverage for catalog, authentication, cart, checkout, customer dashboard, tracking, design uploads, protected mockups, payments, and security. There are no dedicated Blade storefront page tests yet.

### Documentation and deployment

- The README and source-of-truth architecture, stack, deployment, and rollback documents still describe a Laravel-plus-Astro deployment.
- The Laravel Vite output under `apps/backend/public/build` is ignored by Git. If Node/NPM will not run on cPanel, deployment must upload a locally/CI-built release artifact containing `public/build`.
- `apps/backend/public/hot` is ignored and must never be included in a production release because it points Laravel at a development asset server.

## 4. Web Decision Brief

- **User and product job:** Let buyers discover apparel, configure artwork, maintain a cart, complete payment, and track orders; let returning customers manage addresses and order history.
- **Surface type:** Customer product application with a high-trust checkout flow.
- **Primary objects:** Product, customization, cart, address, order, payment, and proof.
- **Implementation track:** Existing Laravel stack using Blade, Laravel Vite, native HTML forms, and targeted TypeScript/JavaScript.
- **Composition:** Implement Design 05's large retail hero, centered commerce navigation, trust strip, circular category entry points, dense product grid, proof-first customizer, focused split checkout, success fact grid, account order cards, and tracking/proof timeline.
- **Typography:** Use Design 05's Archivo display/brand face and DM Sans interface/body face with production-safe font delivery and robust fallbacks.
- **Material, palette, and motif:** Implement Design 05's white/ink/red/blush palette, low-radius surfaces, thin rules, garment-led imagery, red editorial marks, compact commerce metadata, and proof-before-print trust messaging.
- **Data strategy:** Server-render real catalog, settings, cart, customer, and order data from Laravel services. Do not introduce fixtures or duplicate pricing logic.
- **Required states:** Loading only for true browser-side work; server-render empty, permission, validation, unavailable, and success states. Preserve input on errors.
- **Responsive behavior:** Follow Design 05's mobile composition: compact header and drawer, mobile bottom dock, image-first hero, two-column discovery grid where safe, stacked creator/cart/checkout/account layouts, sticky creator action, touch controls, and no page-level overflow.
- **Motion:** Use Design 05's restrained local transitions and announcement treatment with `prefers-reduced-motion` behavior. Do not add a motion library.
- **Accessibility:** Native links, buttons, forms, fieldsets, labels, tables, and disclosures first. Tabs, dynamic statuses, and mockup controls must retain keyboard, focus, live-region, and touch behavior.
- **Acceptance evidence:** Route and feature tests, desktop/mobile comparison against the approved Design 05 baseline, compatibility comparison against current Astro output, keyboard flows, invalid/server-error recovery, console checks, production asset build, and a staged end-to-end purchase flow.

## 5. Target Architecture

```text
Customer browser
    -> Laravel web routes
        -> Storefront controllers
            -> Existing contracts/services/presenters
                -> Eloquent/database, payments, files, queues
        -> Blade views + Laravel Vite assets

External integrations
    -> Existing Laravel API/webhook routes
```

The browser must no longer need `PUBLIC_API_BASE_URL` or a second origin for normal storefront use.

### Proposed Laravel structure

```text
apps/backend/
├── app/Http/Controllers/Storefront/
│   ├── CatalogController.php
│   ├── CartController.php
│   ├── CheckoutController.php
│   ├── CustomerPortalController.php
│   ├── CustomerAddressController.php
│   ├── OrderController.php
│   ├── MockupController.php
│   └── SeoDocumentController.php
├── app/Http/Requests/Storefront/
│   ├── StoreAddressRequest.php
│   └── UpdateAddressRequest.php
├── app/Services/
│   ├── StorefrontSettingsPresenter.php
│   └── CustomerPortalPresenter.php
├── resources/views/components/layouts/storefront.blade.php
├── resources/views/components/storefront/
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── category-card.blade.php
│   ├── product-card.blade.php
│   ├── product-visual.blade.php
│   └── state-panel.blade.php
├── resources/views/storefront/
│   ├── home.blade.php
│   ├── categories/index.blade.php
│   ├── categories/show.blade.php
│   ├── products/show.blade.php
│   ├── search.blade.php
│   ├── cart.blade.php
│   ├── checkout.blade.php
│   ├── account/index.blade.php
│   ├── account/order.blade.php
│   ├── track-order.blade.php
│   ├── order-confirmation.blade.php
│   ├── mockup-generate.blade.php
│   ├── how-it-works.blade.php
│   ├── policy.blade.php
│   └── errors/not-found.blade.php
├── resources/css/storefront.css
├── resources/css/storefront/
│   ├── base.css
│   ├── components.css
│   └── pages.css
└── resources/js/storefront/
    ├── index.js
    ├── navigation.js
    ├── analytics.js
    ├── catalog.js
    ├── product.js
    ├── cart.js
    ├── checkout.js
    ├── account.js
    ├── order.js
    └── mockup-studio.ts
```

Names may be consolidated during implementation, but the boundaries must remain clear: views render, controllers orchestrate, presenters shape display data, and existing domain services own rules.

### Asset isolation

Add `resources/css/storefront.css` and `resources/js/storefront/index.js` as separate Laravel Vite entry points. The storefront layout must not load the admin `app.css` bundle, and admin layouts must not load the storefront bundle. This avoids token and global-selector collisions between the existing admin design system and the customer storefront.

Use one storefront JavaScript entry with route-level dynamic imports selected by a stable `data-page` value on `<body>`. This retains code splitting without maintaining many manual script tags.

## 6. Route and View Mapping

All public URLs must remain unchanged so bookmarks, SEO, payment returns, emails, and customer history keep working.

| Current URL | Proposed web action | Proposed Blade view | Notes |
|---|---|---|---|
| `/` | `CatalogController::home` | `storefront.home` | Replace `welcome.blade.php` route; use catalog/settings contracts directly. |
| `/categories` | `CatalogController::categories` | `storefront.categories.index` | Render published categories and empty/unavailable state. |
| `/categories/{category:slug}` | `CatalogController::category` | `storefront.categories.show` | Preserve filtering/sorting enhancement and Collection/Breadcrumb JSON-LD. |
| `/products/{product:slug}` | `CatalogController::product` | `storefront.products.show` | Load product and customization options directly; preserve product metadata and JSON-LD. |
| `/search` | `CatalogController::search` | `storefront.search` | Server-render initial products; keep client filtering for instant feedback. |
| `/cart` | `CartController::show` | `storefront.cart` | Server-render cart and validation. Mutations use CSRF-protected web routes. |
| `/checkout` | `CheckoutController::show` | `storefront.checkout` | Server-render auth, cart, address, payment-enabled, empty, and blocked states. |
| `/checkout` `POST` | `CheckoutController::store` | redirect/validation response | Call existing validation and pending-order services; redirect to gateway or confirmation. |
| `/account` | `CustomerPortalController::index` | `storefront.account.index` | Protected by `customer.access`; replace redirect in `CustomerAuthController`. |
| `/account/orders/{order:public_id}` | `CustomerPortalController::order` | `storefront.account.order` | Customer-scoped binding/query; render proofs, payments, refunds, timeline, and shipment. |
| `/track-order` | `OrderController::trackForm` | `storefront.track-order` | Keep lookup privacy; unauthenticated users receive sign-in recovery. |
| `/order-confirmation/{order:public_id}` | `OrderController::confirmation` | `storefront.order-confirmation` | Prefer customer-protected lookup with intended-login recovery. |
| `/mockup-generate` | `MockupController::create` | `storefront.mockup-generate` | Keep canvas TypeScript and protected upload/mockup services. |
| `/how-it-works` | `CatalogController::howItWorks` | `storefront.how-it-works` | Static Blade content. |
| `/policies/{slug}` | `CatalogController::policy` | `storefront.policy` | Restrict slug to shipping, returns, privacy, and terms. |
| `/robots.txt` | `SeoDocumentController::robots` | text response | Replace `[document].ts`; preserve account/cart/checkout exclusions. |
| `/sitemap.xml` | `SeoDocumentController::sitemap` | XML response | Use catalog contract directly and retain cache headers. |

Add named routes for every URL and use `route()` in Blade rather than hard-coded internal paths.

## 7. Data and Controller Refactoring Rules

### Reuse without duplication

1. Inject `PublicCatalogContract` into catalog controllers for categories/products.
2. Inject `CustomizationOptionContract` into product and mockup controllers.
3. Inject `SettingsService` through a small storefront settings presenter that returns the same business, checkout, and SEO shape currently emitted by `PublicCatalogController::storefront()`.
4. Use `CartService`, `CartResponsePresenter`, and validation services for all cart views and mutations.
5. Use `CheckoutValidationService` and `CheckoutPendingOrderService` for checkout; the web controller only translates the result into redirects, validation errors, or Blade data.
6. Extract customer profile/address/order formatting and queries from `CustomerApiController` into `CustomerPortalPresenter` (or a query service). Both the API controller and Blade controller must consume the shared presenter.
7. Keep signed/protected file URLs generated by `FileUploadService`; never expose storage paths.
8. Keep payment webhooks and external API endpoints unchanged.

### Validation

- Extract address validation currently duplicated in `CustomerApiController::storeAddress()` and `updateAddress()` into Form Request classes.
- Reuse `StoreCartItemRequest`, `UpdateCartItemRequest`, and `CheckoutValidationRequest` where request semantics match.
- Preserve old input and field-level errors on failed web forms.
- Keep server-side checks authoritative even when JavaScript pre-validates a selection.

### Money and formatting

- Never calculate totals in Blade or JavaScript.
- Keep all monetary values in integer minor units until display.
- Add a single presentation helper/value formatter for INR/currency and date output; do not scatter formatting logic across views.

## 8. Interaction Migration Strategy

| Flow | Initial rendering | Mutation model | JavaScript retained |
|---|---|---|---|
| Header/account/cart count | Blade from current auth/cart | None | Design 05 drawer/dock, search submission, announcement, analytics, and focus return only. |
| Catalog/category/search | Blade from catalog contract and query parameters | GET query with optional local enhancement | Design 05 filter chips, sort, instant result count, saved-product feedback, and empty-result announcement. |
| Product customizer | Blade embeds safe product/options JSON | CSRF-protected upload, delivery check, quote handoff, and add-to-cart actions | Gallery switching, SKU matching, quantity tiers, exact size allocation, file preview, availability, pricing refresh, and inline status. |
| Cart | Blade from `CartResponsePresenter` | HTML forms with PUT/DELETE method spoofing; optional fetch enhancement | Quantity/remove/restore enhancement and cart-count/status announcement. |
| Checkout | Blade from cart/customer/settings data | CSRF-protected POST; controller redirects to Cashfree, bulk handoff, or confirmation | Payment choice, same-address toggle, optional inline address creation, preserved errors, and busy state. |
| Account | Blade from customer presenter | CSRF-protected address and account forms | Accessible rail/tabs/disclosures only where they add value; Design 05 attention state remains server-rendered. |
| Order detail | Blade from customer-scoped order presenter | CSRF-protected reorder POST | Optional busy state/toast; no client-side page reconstruction. |
| Tracking/confirmation | Blade from customer-scoped query | CSRF-protected proof approval/change request and GET/POST lookup as appropriate | Timeline/proof feedback, optional lookup enhancement, and visible server fallback. |
| Mockup studio | Blade embeds safe product JSON | Existing protected upload/mockup/cart services through CSRF-protected routes | Canvas drawing, pointer placement, nudge controls, image processing. |

Avoid rebuilding large HTML fragments with `innerHTML` when Blade can render the same state. This reduces escaping risk, loading flashes, duplicate formatting, and browser-side failure modes.

## 9. Session, CSRF, CORS, and Authentication

### During parallel development

- Leave existing Astro API routes working so `http://127.0.0.1:4321` remains the behavioral/SEO reference and rollback target. Design 05, not Astro, is the visual reference.
- Build Blade routes on the Laravel origin (`http://127.0.0.1:8000`) and compare both versions side by side.
- Do not remove CORS or the current API CSRF exceptions until the Blade flow has passed its acceptance gates.

### At final cutover

1. Make Laravel the public domain root and set `APP_URL` to that HTTPS origin.
2. Change `/account` from an external redirect to a protected Blade view.
3. Update login, registration, password reset, and logout redirects to named same-origin routes.
4. Ensure cart merge behavior across guest login still passes existing tests.
5. Send CSRF tokens on all same-origin browser mutations.
6. Remove `withoutMiddleware(ValidateCsrfToken::class)` from session-coupled mutation routes after no browser client depends on the exception, or retire those mutating API routes in favor of named web routes.
7. Remove the local `4321` origins from production CORS. Keep CORS only for explicitly approved external API clients.
8. Remove `PUBLIC_SITE_URL`, `FRONTEND_URL`, and `PUBLIC_API_BASE_URL` dependencies after the rollback window.

Do not combine the CSRF hardening with an unverified client cutover. The order above keeps Astro functioning until Blade mutations are ready.

## 10. Phased Implementation

### Phase 0 — Baseline and architecture record

- Freeze the approved Design 05 desktop/mobile screenshots and prototype files as the visual baseline; record any discrepancy between the screenshots and the current CSS.
- Capture the current Astro version of every public route and meaningful state as behavioral, SEO, and rollback evidence—not as the new visual target.
- Record current Astro HTML metadata, structured data, response codes, redirects, cookies, and browser console output.
- Run the existing catalog/cart/checkout/customer/design/security test groups and the Astro build.
- Add a new ADR that supersedes ADR-01 for hosting reasons. Do not rewrite the historical ADR-01.
- Confirm the production domain/document-root and release-artifact process for cPanel.
- Inventory every Design 05 asset and approve, replace, or reject it before copying it into Laravel.

**Gate:** Design 05 is frozen as the visual target, Astro compatibility evidence is stored, asset provenance is resolved, and the cutover/rollback mechanism is confirmed.

### Phase 1 — Storefront foundation

- Add the dedicated storefront Blade layout, Vite entries, semantic Design 05 tokens, global CSS, header, footer, SEO slots, structured-data slot, state panel, mobile drawer/dock, and route-level script loader.
- Copy only approved Design 05 brand/campaign assets and the required existing mockup assets into Laravel public assets; use real catalog media everywhere else.
- Port Design 05's category entry, product card, product visual, trust item, status badge, order summary, attention banner, success fact, and empty/error state patterns as Blade components.
- Add storefront settings and money/date presentation helpers.
- Implement skip link, landmarks, current navigation indication, menu dismissal, Escape behavior, focus return, cart badge, mobile dock selection, reduced-motion behavior, and analytics events.

**Gate:** A fixture page matches the frozen Design 05 shell at desktop and mobile sizes without loading admin CSS, while emitting the required Astro-era metadata and analytics hooks.

### Phase 2 — Low-risk discovery and SEO routes

- Migrate home, category index, category detail, search, how-it-works, policies, robots, and sitemap.
- Apply Design 05's home hero/trust/category/product/campaign/steps/review composition and its collection hero, toolbar, product grid, and empty-result treatment.
- Preserve 404 status codes, empty/unavailable states, canonical URLs, robots directives, Open Graph/Twitter metadata, Organization/WebSite/Product/Collection/Breadcrumb JSON-LD, and sitemap cache headers.
- Retain search and category filter/sort behavior as lightweight JavaScript.

**Gate:** Public route tests, SEO assertions, Design 05 visual comparison, keyboard navigation, and mobile overflow checks pass.

### Phase 3 — Product customization and mockup studio

- Migrate product detail, media gallery, SKU selection, pricing display, print-position/method compatibility, artwork preview/upload, and add-to-cart flow.
- Implement the Design 05 gallery/configurator layout, quantity tiers, exact size-allocation feedback, artwork instructions/upload, proof notice, pricing summary, delivery check, bulk handoff callout, and sticky mobile action using real product rules.
- Port `mockup-studio.ts` through Laravel Vite without rewriting its canvas logic.
- Move its mockup assets and preserve pointer capture, nudge buttons, reset/remove actions, file constraints, live status, sign-in recovery, protected preview generation, and cart handoff.
- Add CSRF protection to new same-origin mutation routes while retaining server-side authorization and validation.

**Gate:** Available/unavailable/quote-required combinations, exact size totals, required artwork, invalid file, unauthorized upload, successful protected preview, add-to-cart, 25+ bulk handoff, keyboard controls, touch placement, reduced-motion behavior, and Design 05 visual comparison pass.

### Phase 4 — Cart, authentication, and checkout

- Server-render cart contents, validation, pricing, and Design 05 configured-item, summary, remove/restore, empty, and error states.
- Add CSRF-protected update/remove forms with optional inline enhancement.
- Server-render the focused Design 05 checkout shell, customer addresses, order review, payment availability, proof-before-print notice, retained-input message, and validation failures.
- Post checkout through existing services and redirect to the gateway or order confirmation.
- Render the Design 05 bulk quotation confirmation treatment only after the server has created a durable, customer-owned request; until then, keep bulk handoff as a safe enquiry state without a fabricated quotation ID.
- Integrate login/register/password-reset pages with the storefront asset/layout system where appropriate without weakening rate limits or generic account-enumeration messages.
- Preserve intended destination through sign-in.

**Gate:** Guest cart, authenticated cart, cart merge, quantity errors, stock/price changes, remove/restore, missing addresses, bulk handoff, disabled payments, duplicate checkout prevention, gateway failure, successful redirect/confirmation, Design 05 visual comparison, and preserved cart/form data all pass.

### Phase 5 — Customer account, orders, tracking, and proofs

- Extract the shared customer portal presenter/query service.
- Server-render profile, addresses, order history, order detail, timeline, shipping, payments, refunds, design issues, proofs, and empty states.
- Apply Design 05's account rail, attention banner, order/status cards, payment recovery, success fact grid, tracking timeline, and proof action card to those real states.
- Convert address CRUD/default and reorder to CSRF-protected web actions.
- Preserve customer ownership scoping and signed temporary proof URLs.
- Replace visual-only tabs with a defined tab/disclosure model: selected state, keyboard behavior, focus behavior, deep-link/query behavior, and a no-JavaScript fallback.

**Gate:** Unauthorized access, cross-customer address/order access, address validation, delete/default behavior, empty order history, proof preview/download/approval/change-request, partial reorder, tracking, Design 05 desktop/mobile comparison, keyboard flow, and error recovery pass.

### Phase 6 — Cutover, hardening, and decommissioning

- Point the public site at Laravel and run the complete production smoke matrix.
- Remove cross-origin browser dependencies and harden CSRF/CORS as described above.
- Build Laravel assets locally or in CI and upload the complete `public/build` artifact; exclude `public/hot`.
- Update all source-of-truth documentation and environment examples.
- Keep `apps/frontend` and the last working Astro deployment available for one stabilization release.
- After the stabilization gate, remove the Astro app, obsolete environment variables, obsolete CORS allowances, Astro-specific guidance fields/tests, and Astro deployment steps.

**Gate:** No storefront browser request targets port 4321 or a separate API origin; production health, checkout, webhooks, queues, cron, private files, and rollback evidence are confirmed.

## 11. Test and Acceptance Matrix

### Automated PHP tests to add

- `StorefrontPageTest`: status, view, public visibility, 404, and page data for every route.
- `StorefrontSeoTest`: title, description, canonical, robots, social metadata, JSON-LD, robots.txt, and sitemap.xml.
- `StorefrontCartWebTest`: guest cart, auth merge, update, remove, validation, CSRF, and preserved errors.
- `StorefrontCheckoutWebTest`: auth, address ownership, validation, bulk handoff, payment-disabled state, idempotency, gateway redirect, and confirmation.
- `StorefrontCustomerPortalTest`: account protection, address CRUD/defaults, order ownership, reorder, timeline, proofs, payments, and refunds.
- `StorefrontCustomizationWebTest`: product/options rendering, upload authorization/validation, mockup generation, and add-to-cart.
- `StorefrontSecurityTest`: CSRF rejection, escaped customer/catalog text, private file boundaries, signed links, redirect safety, and CORS policy.

Keep the existing API tests passing until API retirement is separately approved.

### Browser acceptance tasks

1. Compare `/`, catalogue, product, cart, checkout, confirmation, account, and tracking at the approved Design 05 desktop and mobile viewport sizes; separately verify Astro URL/SEO compatibility.
2. Browse home -> category -> product through the Design 05 header and mobile dock; filter/sort; change gallery image, quantity tier, sizes, colour, and product options by keyboard and touch.
3. Submit a product with a size-total mismatch and without required artwork, preserve all choices, add a valid file, then add to cart.
4. Select 25+ items and verify the real bulk handoff appears without payment or a fabricated quotation; verify the direct-checkout path remains available below the threshold.
5. Change quantity, simulate a validation failure, recover without losing the cart, remove an item, and use the Design 05 restore path.
6. Start checkout signed out, sign in, return to checkout, add an address, choose a payment method, and complete the gateway handoff.
7. Simulate a gateway/server error and verify the cart, address selection, entered delivery details, and retry path remain available.
8. Create/edit/delete/default an address; inspect an order; recover a pending payment; open/download a proof; partially reorder an old order.
9. Track a valid order, try another customer's order, approve a proof, request a proof change, and recover from not-found/unavailable states.
10. Generate a mockup with pointer placement and with keyboard nudge controls.
11. Repeat critical flows at desktop and narrow mobile widths, at 200% zoom, with keyboard only, and with reduced motion enabled.

### Build and quality gates

Run at minimum:

```powershell
cd apps/backend
npm run build
php artisan view:cache
php artisan route:list
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

Also verify:

- no browser console errors or failed requests;
- no runtime font dependency fails when external font/CDN access is unavailable;
- no page-level horizontal overflow;
- visible focus and correct focus return for transient menus/panels;
- dynamic statuses are visible and announced appropriately;
- no unsafe raw HTML output for catalog/customer data;
- Laravel route cache and production asset manifest work without a Vite dev server;
- initial HTML contains meaningful content for catalog, cart, checkout, account, and order pages.

## 12. Deployment on Shared cPanel

The target runtime becomes PHP/Laravel plus MySQL, cron, and the required Laravel queue process. Node/NPM is needed only in local development or CI to compile Laravel Vite assets.

Recommended release artifact contents include:

- Laravel application source;
- production Composer dependencies, or a server-side Composer install step;
- compiled `apps/backend/public/build` including `manifest.json`;
- copied storefront public assets under `apps/backend/public/brand` and `apps/backend/public/mockups`;
- no `.env`, `node_modules`, frontend `dist`, or `public/hot`.

The cPanel document root must target `apps/backend/public`, not the repository root.

The Astro-to-Blade migration does **not** remove Laravel's existing queue requirement. If shared hosting cannot maintain a queue worker, configure a documented cron-based queue strategy only after verifying that it satisfies payment, notification, Sheets, and retry behavior.

## 13. Rollback Strategy

Design the migration without database schema changes so code rollback remains safe.

Before cutover:

- keep the Astro source and last successful build/deployment intact;
- record the last known-good commit and deployment configuration;
- keep API compatibility while Blade is being validated;
- take a normal application/database backup even though no data migration is planned.

If a critical storefront issue appears after cutover:

1. enable Laravel maintenance mode if checkout or data integrity is affected;
2. restore the previous route/application release, or point the public site back to the retained Astro deployment;
3. restore the matching `public/build` artifact;
4. run `php artisan optimize:clear`, rebuild caches, and restart queue workers;
5. verify login, cart, checkout, Cashfree callback, uploads, and order access before reopening;
6. do not restore the database unless an unrelated data-changing release requires it.

Do not delete Astro or remove compatibility routes until the stabilization gate is explicitly passed.

## 14. Documentation Updates Required

Update these source-of-truth documents during cutover:

- `README.md`;
- `docs/01_Project_Overview.md`;
- `docs/03_System_Architecture.md`;
- `docs/04_Architecture_Decisions.md` by adding a superseding ADR;
- `docs/05_Technology_Stack.md`;
- `docs/10_Authentication.md`;
- `docs/11_Deployment_Guide.md`;
- `docs/DEPLOYMENT-CHECKLIST.md`;
- `docs/ROLLBACK-PROCEDURE.md`;
- `docs/OKINA_STOREFRONT_FRONTEND_GUIDELINES.md`.

Treat `Build docs/` and `UI Build docs/` as historical planning evidence. Do not rewrite historical completion records; add an archive/supersession note only where readers could mistake them for current deployment instructions.

## 15. Definition of Done

The migration is complete only when all of the following are true:

- every current public/customer URL is served by Laravel with the correct status and named route;
- storefront visuals and responsive behavior match the approved Design 05 baseline, while metadata, structured data, URLs, and meaningful states meet the Astro compatibility gates;
- catalog, customization, cart, checkout, customer account, tracking, proof, payment, and file rules remain owned by Laravel services;
- browser mutations are same-origin and CSRF-protected;
- login/logout/password-reset redirects and guest-cart merge work on one origin;
- no storefront runtime depends on Node, Astro, port 4321, `PUBLIC_API_BASE_URL`, or cross-origin session cookies;
- `public/build` can be deployed as a release artifact and the app runs without `public/hot`;
- existing backend/API tests and new Blade storefront tests pass;
- full desktop/mobile/keyboard/error-path browser validation passes;
- production health checks, queue, cron, webhooks, private uploads, and rollback are verified;
- documentation describes the Laravel-only storefront architecture;
- Astro is removed only after the stabilization release is accepted.

## 16. Recommended First Implementation Slice

Start with the Design 05 storefront foundation plus `/`, `/categories`, and `/categories/{slug}`. This slice proves the new header/footer/mobile dock, semantic tokens, typography delivery, hero, trust row, category entry points, reusable product cards, approved assets, shared catalog data, SEO, 404/empty states, filter/sort JavaScript, and responsive behavior without touching cart, authentication, uploads, or payments. Once it passes Design 05 visual review and Astro compatibility checks, reuse the same foundation for search and product detail.
