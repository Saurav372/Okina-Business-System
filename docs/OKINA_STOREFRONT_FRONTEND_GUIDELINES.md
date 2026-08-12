# OKINA STOREFRONT FRONTEND GUIDELINE

## Purpose

This guideline defines the recommended design, implementation, review, and release workflow for the Okina customer storefront frontend.

### Okina storefront architecture

- **Frontend:** Astro + Tailwind CSS
- **Backend/API:** Laravel 13
- **Database:** MySQL
- Backend remains the source of truth for pricing, inventory, discounts, bulk-order rules, order validation, payments, permissions, and other business rules.

---

## Core Workflow

```text
Audit
  -> UX Evidence and Reference Decisions
  -> Design Foundation
  -> Component Foundation
  -> Storefront Shell
  -> Discovery
  -> Product
  -> Customization
  -> Cart
  -> Checkout
  -> Customer Portal
  -> Full Quality Gate
```

For every substantial page or feature:

```text
reference evidence and UX decision brief
  -> frontend-design
  -> frontend-ui-engineering
  -> functional and API-state testing
  -> better-interface
  -> better-accessibility
  -> better-colors
  -> better-ui
  -> interaction-design
  -> web-quality-audit
```

---

## PHASE 0 — AUDIT BEFORE REDESIGN

Before applying UI skills, inspect the existing storefront.

### Check

- Current Astro structure
- Existing Tailwind configuration
- Existing components
- Existing design tokens
- API integration
- Product/category data structures
- Cart implementation
- Authentication/customer portal
- Responsive behavior
- Current accessibility problems
- Current Lighthouse/performance state
- Duplicate components
- Hard-coded styling
- Existing loading/error/empty states

### Goal

Determine what should be kept, improved, refactored, or replaced.

**Do not redesign blindly.**

---

## PHASE 0.5 — UX EVIDENCE AND REFERENCE DECISIONS

Before designing a substantial customer journey, gather evidence from Mobbin, direct competitors, current storefront behavior, customer questions, support issues, and backend constraints.

Use [OKINA_STOREFRONT_COMPETITOR_NOTES.md](./OKINA_STOREFRONT_COMPETITOR_NOTES.md) as the first recorded competitor review.

### Required UX decision brief

For each journey, record:

- Customer and commercial goal
- Target customer type
- Mobbin and competitor references
- Observed patterns
- Patterns Okina will adopt
- Patterns Okina will adapt
- Patterns Okina will reject
- Primary happy path
- Loading, empty, validation, failure, retry, and recovery states
- Desktop composition
- Mobile responsive replacement
- Laravel/API ownership and required data
- Analytics events
- Acceptance scenario and evidence

### Priority benchmark journeys

- Homepage to category discovery
- Category filtering and sorting
- Search and no-result recovery
- Product variant selection
- Custom artwork upload and proof approval
- Bulk size-by-quantity ordering
- Add to cart and edit customization
- Checkout and payment recovery
- Order confirmation and tracking

### Recorded reference set

The initial reference set has already established the following decisions:

- [Adidas - Adding to cart](https://mobbin.com/flows/6abe9522-cfac-4e16-98a4-625c4273dd4b)
  - Use availability-aware color and size controls, selected states, contextual size guidance, and a product gallery that remains connected to the selected variant.
- [Urban Outfitters - Browse a category](https://mobbin.com/flows/482029cb-5bf2-4472-8f78-d50c61d603b8)
  - Keep product count, filters, sorting, merchandising, and the product grid visually coordinated rather than treating them as unrelated sections.
- [Shopify - Adding to cart](https://mobbin.com/flows/48a8009c-b81e-44ec-86b0-0945db02cbda)
  - Confirm add to cart immediately and preserve the selected configuration in both the cart drawer and cart page.
- [Lululemon - Purchasing a product](https://mobbin.com/flows/0a9a45a0-7b9c-4e1c-b838-faa75bd1e3ed)
  - Use a clear checkout progression, editable completed sections, and a persistent order summary.
- [Etsy - Checking out an order](https://mobbin.com/flows/6ce72f42-9962-4bae-9b87-f534bdfc8762)
  - Include a final review, explicit order-submission state, and useful confirmation actions.
- Destiny Clothing competitor homepage and customizable-product page
  - Adapt its commercial clarity, category discovery, client proof, separate artwork inputs, design-help option, quantity tiers, proof reassurance, production steps, and customer evidence.
  - Reject its image-embedded text, ambiguous pack pricing, tiny controls, long empty regions, unlabelled swatches, and awkward horizontal product regions.

Mobbin did not provide a close reference for Okina's complete artwork-upload, placement, bulk sizing, live pricing, and proof-approval journey. Treat the Okina customizer as a product-specific workflow that requires an early prototype and direct usability evidence.

### Rule

References are evidence, not templates. Do not copy another storefront's layout or visual identity. Extract the customer problem, interaction pattern, state coverage, and trust mechanism, then design the Okina version.

---

## PHASE 1 — STOREFRONT DESIGN SYSTEM

**Primary skill:** `frontend-design`

### Goal

Define what an Okina customer-facing experience should look and feel like before building individual pages.

### Customer and product job

Before selecting a visual direction, validate the priority customer groups and jobs, including:

- Individuals or creators ordering a small customized batch
- Teams, events, colleges, and community groups managing several sizes
- Businesses and institutions requiring repeatable bulk orders
- Customers with finished artwork
- Customers who need Okina to prepare or repair artwork

The primary product job is:

> Help a customer choose the right garment, configure quantities, provide artwork or request design help, understand the full price, approve the production proof, and track a reliable custom order.

### Required design decision brief

Record before implementation:

- Surface type and primary customer action
- First-viewport focal path
- Typography posture and concrete font plan
- Palette ratio, material language, and repeated Okina motif
- Product photography and proof-asset strategy
- Trust evidence and claims that require verification
- Required states and recovery behavior
- Mobile replacement for every dense desktop region
- Motion level, owner, and reduced-motion behavior
- Mobbin/competitor decisions applied or rejected
- Acceptance screenshots and interaction scenarios

### 1. Brand Direction

- Store personality
- Premium vs affordable positioning
- Visual density
- Photography style
- Product presentation
- Promotional treatment
- Trust presentation
- B2B/custom-order positioning

### 2. Typography System

Define:

- Display
- H1
- H2
- H3
- H4
- Body Large
- Body
- Body Small
- Label
- Caption
- Price
- Sale Price

Specify:

- Font family
- Font weight
- Font size
- Line height
- Letter spacing
- Responsive scaling

### 3. Spacing System

Use a consistent scale, for example:

```text
4
8
12
16
20
24
32
40
48
64
80
96
```

Avoid random spacing values across components.

### 4. Core Layout System

Define:

- Page max-width
- Desktop gutters
- Tablet gutters
- Mobile gutters
- Section spacing
- Grid spacing
- Card spacing
- Form spacing

### 5. Product Visual Language

Define one consistent system for:

- ProductCard
- ProductImage
- ProductTitle
- Price
- Compare-at Price
- Discount
- Stock
- Rating
- Variant preview
- Wishlist
- Quick add
- Promotion

Product cards for customizable goods should also define:

- Starting price or price range
- Minimum quantity or pack size
- Available color count
- Customization capability
- Material, fit, or print-compatibility summary
- Availability and disabled reason

### 6. Visual restraints

- Do not copy the competitor's layout, typography, color palette, or image treatments.
- Do not place essential headings, prices, options, or actions only inside product imagery.
- Do not use generic `Your Design Here` imagery as the main proof strategy.
- Do not show client logos without permission, accessible names, and a clear evidence purpose.
- Do not use generic `premium quality` claims without material, process, review, or delivery evidence.

---

## PHASE 2 — CORE COMPONENT ARCHITECTURE

**Primary skill:** `frontend-ui-engineering`

Translate the approved design direction into reusable Astro/Tailwind components.

### Recommended structure

```text
components/
|
|-- layout/
|   |-- Header
|   |-- Navigation
|   |-- MobileNavigation
|   |-- Footer
|   |-- Container
|   `-- Section
|
|-- product/
|   |-- ProductCard
|   |-- ProductGrid
|   |-- ProductGallery
|   |-- ProductPrice
|   |-- ProductBadge
|   |-- ProductVariants
|   |-- QuantitySelector
|   `-- AddToCartButton
|
|-- customization/
|   |-- CustomizationMethod
|   |-- PlacementSelector
|   |-- ArtworkUpload
|   |-- TextCustomization
|   |-- DesignHelpRequest
|   |-- ProofPreview
|   |-- ProofStatus
|   |-- SizeQuantityMatrix
|   |-- TierPricing
|   `-- CustomizationSummary
|
|-- category/
|   |-- CategoryCard
|   |-- CategoryGrid
|   `-- CategoryNavigation
|
|-- search/
|   |-- SearchBar
|   |-- SearchSuggestions
|   `-- SearchResults
|
|-- filters/
|   |-- FilterPanel
|   |-- FilterDrawer
|   |-- FilterGroup
|   `-- SortSelect
|
|-- cart/
|   |-- CartDrawer
|   |-- CartItem
|   |-- CartSummary
|   `-- CouponInput
|
|-- checkout/
|   |-- CheckoutForm
|   |-- AddressForm
|   |-- ShippingMethod
|   |-- PaymentMethod
|   `-- OrderSummary
|
|-- feedback/
|   |-- Toast
|   |-- Alert
|   |-- Skeleton
|   |-- EmptyState
|   `-- ErrorState
|
`-- ui/
    |-- Button
    |-- Input
    |-- Select
    |-- Checkbox
    |-- Radio
    |-- Modal
    |-- Drawer
    |-- Accordion
    |-- Badge
    `-- Spinner
```

### Astro interaction boundaries

- Render useful product, category, pricing context, and SEO content as HTML by default.
- Add hydrated islands only where customer interaction requires them, such as search suggestions, variant-dependent availability, the customizer, cart updates, and checkout controls.
- Give every client-side island one documented owner, API contract, loading strategy, error boundary, and no-JavaScript fallback where the task permits it.
- Do not hydrate static marketing, trust, policy, or descriptive content merely for animation.
- Normalize Laravel API errors into consistent field, form, availability, pricing, authorization, and retry states.
- Never treat a cached or client-calculated amount as final checkout authority.

### Interactive component contract

For every interactive component, document:

- Inputs and backend data dependencies
- Default, loading, empty, selected, disabled, invalid, stale, success, and failure states
- Keyboard behavior and accessible name
- Status announcement behavior
- Mobile replacement or containment
- Persistence behavior across navigation, refresh, authentication, and retry
- Analytics events

### Responsibility split

```text
frontend-design
  -> What should this look and feel like?

frontend-ui-engineering
  -> How should this be implemented?
```

---

## PHASE 3 — STOREFRONT SHELL

Build the global shell before individual homepage sections.

### Build

- Header
- Announcement bar
- Desktop navigation
- Mobile navigation
- Search entry point
- Account
- Wishlist
- Cart button
- Cart count
- Breadcrumb system
- Footer
- Global container
- Global notifications

### Test at

- 360px
- 390px
- 430px
- 768px
- 1024px
- 1280px
- 1440px+

### Completion gate

- Header works across breakpoints
- Mobile navigation works
- Search entry works
- Keyboard navigation works
- Cart state is visible
- Layout containers are stable
- Footer is responsive
- Desktop navigation has a defined mobile replacement
- Search has a defined small-screen presentation
- Cart drawer becomes a contained sheet or page without covering essential controls
- No announcement bar, navigation, carousel, table, or floating support action causes page-level horizontal overflow
- Long labels, 200% zoom, and translated-length text do not clip controls

---

## PHASE 4 — HOMEPAGE

Use the complete workflow:

```text
frontend-design
  -> frontend-ui-engineering
  -> better-interface
  -> better-accessibility
  -> better-colors
  -> better-ui
  -> interaction-design
  -> web-quality-audit
```

### Recommended homepage information order

1. Product-specific hero with the primary customer job and one dominant action
2. Commercial qualifiers such as minimum quantity, design support, delivery reach, and proof process
3. Shop by product category
4. Shop by requirement or use case
5. How custom ordering works
6. Featured or best-selling customizable products
7. Approved customer proof or short case studies
8. Printing methods, fabric, production, and quality evidence
9. Bulk/custom-order assistance
10. Delivery, support, policy, and payment reassurance
11. Useful buying or artwork guidance
12. Footer

The order may change after evidence review, but the page should move from product clarity to discovery, process understanding, proof, and action.

### Required first-viewport answers

The homepage should quickly answer:

- What can the customer customize?
- Is Okina suitable for an individual, team, event, company, or bulk order?
- What is the minimum or starting quantity?
- Can Okina help when artwork is not ready?
- What happens after the customer submits a design?
- What is the primary action: shop products, start a custom order, or request help?

### Trust and merchandising guidance

- Use real customizable products as the main proof asset.
- Support both product-led discovery and requirement/use-case discovery.
- Use approved customer logos only when permission and accessible labels are available.
- Prefer specific evidence such as product, quantity, print method, delivery outcome, or customer result over generic quality claims.
- Do not embed important headings, prices, or actions only inside images.
- Show customer examples with useful context such as garment, print method, quantity, use case, or delivery outcome.
- If a client-logo band is used, do not let it replace case-study or review evidence.
- Use product cards that help comparison instead of repeating nearly identical promotional artwork.

### Homepage responsive behavior

- Replace wide category or product rows with a bounded, labelled carousel or a priority grid; never expose a browser-like page scrollbar.
- Keep hero copy, product proof, and the primary action visible without shrinking text into the artwork.
- Preserve the commercial qualifiers near the first viewport on small screens.
- Make floating support actions yield to drawers, cookie controls, and primary purchase actions.

### Rule

Do not add sections just because other e-commerce sites use them. Every section should have a clear commercial purpose.

---

## PHASE 5 — CATEGORY / COLLECTION / PRODUCT LISTING

### Implement

- Breadcrumb
- Category heading
- Category description
- Subcategories
- Product count
- Filters
- Sorting
- Active filters
- Clear filters
- Product grid
- Pagination/load more
- Empty state
- Loading state
- Error state

### PLP behavior derived from reference review

- Keep category context, product count, sorting, filters, active-filter summary, and the product grid in one understandable composition.
- Preserve filter and sort state when the customer opens a product and returns.
- Update the product count and results with a clear loading or status announcement.
- Show unavailable filter values as disabled only when the reason is understandable; otherwise omit unsupported values.
- On mobile, replace the desktop filter region with a contained drawer or sheet and provide a visible active-filter count.
- Keep sorting available without forcing the customer to reopen the filter interface.
- Product cards should show enough information to compare starting price, minimum quantity, colors, fit/material, and customization support.
- Use stable pagination or load-more behavior with focus and scroll restoration.
- Do not use page-level horizontal product scrolling as the primary listing experience.

### Potential filters

- Category
- Gender/use
- Product type
- Size
- Color
- Material
- Fit
- Price
- Availability
- Printing compatibility

Only expose filters supported by backend/catalog data.

---

## PHASE 6 — SEARCH

Treat search as its own feature.

### Build

```text
Search entry
  -> Search suggestions
  -> Recent searches
  -> Search results
  -> Filters
  -> Sorting
  -> No-result recovery
```

### Test

- Keyboard navigation
- Mobile search
- Long queries
- Typos
- No results
- Loading
- API failure
- Search suggestion selection and dismissal
- Focus restoration after closing search
- Results containing unavailable or non-customizable products
- Corrected-query and related-category recovery

### No-result recovery

Provide useful next actions such as:

- Corrected spelling or alternate search
- Related categories or product types
- Clear filters
- Browse customizable products
- Request product or custom-order assistance

---

## PHASE 7 — PRODUCT DETAIL PAGE

The Okina PDP requires more work than a simple e-commerce product page because it supports product customization.

### PDP A — Product Information

- Gallery with front, back, detail, material, fit, and real customization examples where available
- Product title
- Price
- Discount
- Description
- Availability
- Delivery information
- Product specifications
- Size guide
- Delivery estimate inputs and assumptions
- Printing compatibility and production constraints
- Review and customer-image evidence

### PDP B — Variants

- Color
- Size
- Variant availability
- Variant image
- Quantity
- Accessible color names and visual swatches
- Selected, unavailable, low-stock, and disabled-reason states
- Contextual size guidance without losing the current configuration
- Variant-linked gallery, price, availability, and delivery updates

### PDP composition

- On wide screens, keep product proof and ordering decisions connected in a clear media-and-configuration composition.
- On small screens, stack the media, essential product facts, configuration steps, price summary, and purchase action in task order.
- A sticky summary or action may be used only when it does not cover form errors, file controls, support controls, or system UI.
- Keep the selected product, variant, quantity, customization, and price visible or quickly reviewable before add to cart.
- Do not repeat a long marketing title where structured product attributes would be easier to scan.

### PDP C — Customization

- Print method
- Placement
- Customization method: upload artwork, enter text, or request design help
- Separate backend-supported placement inputs such as front, back, or sleeve
- Design file requirements and privacy guidance
- Upload progress, processing, success, remove, replace, retry, and validation states
- Customization options
- Preview or clear explanation of when a production proof will be prepared
- Proof version, approval status, approval action, revision path, and deadline
- Notes
- Size-by-quantity distribution
- Backend-authoritative price recalculation
- Unit price, total price, and savings presentation
- Editable customization summary for cart

### Customization flow

```text
Choose garment and variants
  -> Choose print method and placement
  -> Upload artwork, enter text, or request design help
  -> Validate files and show progress
  -> Preview or prepare proof
  -> Select size-by-quantity distribution
  -> Recalculate price through Laravel
  -> Review configuration and proof terms
  -> Add to cart
```

### Customization rules

- Do not imply that an automated mockup is the approved production proof.
- Preserve uploads and entered information through recoverable errors.
- State accepted file types, maximum size, resolution guidance, and transparency requirements before upload.
- Clearly distinguish per-unit price, quantity, total, and savings.
- Do not use color-only or image-only controls without accessible names and selected/disabled states.
- Cart editing must not silently discard artwork, quantities, or proof state.
- Front, back, sleeve, and other placement inputs must only appear when supported for the selected product and print method.
- Changing a product, color, size distribution, placement, or print method must explain any invalidated artwork, price, delivery, or proof state before applying the change.
- Design-help requests must collect enough structured information to continue the order without relying only on chat messages.
- Proof timing, included revisions, approval deadline, and consequences for delivery must be visible before purchase.

### Customization prototype gate

Before implementing the full storefront, prove one vertical slice:

```text
Configurable product
  -> artwork or text input
  -> size-by-quantity selection
  -> Laravel price response
  -> configuration review
  -> cart persistence
  -> edit and recover
```

Test it with a small-batch customer, a multi-size team order, a customer without artwork, an invalid upload, a price change, and a mobile viewport.

### PDP D — Bulk Quantity Logic

Frontend should clearly communicate backend-defined changes when a bulk threshold is reached.

> **Important:** Do not make the Astro frontend the authoritative owner of bulk thresholds or business rules. Laravel remains authoritative.

---

## PHASE 8 — CART

Build after PDP/customization structures are finalized.

### Components

- CartDrawer
- CartPage
- CartItem
- Variant summary
- Customization summary
- Quantity editor
- Remove item
- Price breakdown
- Coupon
- Shipping information
- Checkout CTA

### Cart behavior derived from reference review

- Confirm add to cart immediately without losing the PDP configuration context.
- The cart drawer should summarize the selected product, color, size distribution, quantity, customization method, placements, artwork, proof status, unit price, and total.
- Provide `View cart`, `Checkout`, `Continue shopping`, and `Edit customization` according to the current state.
- Preserve the complete configuration when moving from cart drawer to cart page.
- Explain backend-detected stock, price, threshold, delivery, or customization changes before checkout.
- Do not silently remove an artwork file or reset a size distribution when quantity changes.
- Prevent unrelated recommendations from competing with an unresolved cart error or the primary checkout action.

### Test

- Empty cart
- One item
- Many items
- Out-of-stock changes
- Price changes
- Quantity changes
- Customized items
- Large orders
- API failures
- Add-to-cart pending, success, duplicate-click, and failure states
- Edit configuration and return to cart
- Stale price or availability recovery
- Artwork or proof state that requires customer action
- Mobile cart sheet containment and focus restoration

---

## PHASE 9 — CHECKOUT

### Recommended logical flow

```text
Contact
  -> Address
  -> Delivery
  -> Payment
  -> Final Review
  -> Processing
  -> Confirmation
```

This may be implemented as one page or a step flow depending on final UX requirements.

### Checkout interaction model

- Show the current step and remaining progression without implying completion too early.
- Allow completed contact, address, delivery, and payment sections to be reviewed and edited.
- Keep an order summary available throughout; on small screens it may become a clearly labelled expandable summary.
- Preserve entered information across validation and recoverable payment failures.
- Revalidate inventory, price, discounts, customization state, delivery method, and total before final submission.
- Put the final `Place order` action only after the customer can review delivery, payment method, customization summary, proof terms, and complete total.
- During submission, prevent duplicate actions and show that the order is being verified without suggesting success before Laravel confirms it.

### Implement

- Contact details
- Billing/shipping address
- Saved addresses
- Shipping method
- Order summary
- Discount
- Tax
- Total
- Payment
- Validation
- Retry state
- Payment processing state
- Payment failure
- Payment success
- Final review containing delivery, payment method, customization summary, proof terms, and complete total
- Duplicate-submission prevention
- Preserved customer input after recoverable payment failure
- Editable completed sections
- Persistent or expandable order summary
- Terms, proof implications, and final order consequence near the submission action
- Cashfree redirect, return, pending, cancelled, failed, timed-out, retry, and already-confirmed states

> **Important:** Payment confirmation and transaction verification must remain backend-controlled.

---

## PHASE 10 — ORDER CONFIRMATION

### Show

- Order number
- Payment status
- Order status
- Items
- Delivery information
- Customer details
- Expected next step
- Track order
- Continue shopping
- Support
- Customization and artwork summary
- Proof status and expected proof timeline
- Payment pending or verification guidance when confirmation is not immediate
- Reorder or buy-again action when the product remains available

### Confirmation rules

- Do not show a successful order state until Laravel verifies the order/payment result.
- If payment is pending, explain what is pending, whether the customer should retry, and how duplicate payment is prevented.
- Give the customer a durable reference for support, proof approval, tracking, and later account recovery.

---

## PHASE 11 — CUSTOMER ACCOUNT

### Build

- Login
- Register
- Forgot password
- Account overview
- Profile
- Addresses
- Order history
- Order detail
- Delivery tracking
- Saved artwork and reusable customization details where permitted
- Proof review, approve, request-change, and history states
- Reorder with current price, availability, and production-rule revalidation
- Support history or reference linked to the order

---

## SKILL EXECUTION MODEL

Do not run every skill once across the whole website or treat skill names as substitutes for evidence. Use four gates for every substantial feature.

### Gate 1 - Evidence and UX definition

- Audit the existing implementation and backend contract.
- Review relevant Mobbin flows and direct-competitor evidence.
- Record adopt, adapt, and reject decisions.
- Define the customer goal, happy path, state matrix, responsive replacement, analytics, and acceptance scenario.

### Gate 2 - Design and implementation

- Use `frontend-design` for visual identity, hierarchy, composition, product proof, and responsive intent.
- Use `frontend-ui-engineering` for Astro/Tailwind components, hydration ownership, API integration, and maintainable responsive behavior.
- Integrate real Laravel data before judging the feature complete.

### Gate 3 - Interaction and inclusive QA

- Run functional, API, slow-network, stale-data, and recovery testing.
- Use `better-interface` for usability, hierarchy, UX writing, spacing, layout, and flow.
- Use `better-accessibility` and `better-colors` for keyboard, focus, names, announcements, reflow, target size, contrast, and non-color communication.
- Use `better-ui` only after structural problems are resolved.
- Use `interaction-design` for purposeful feedback and motion with a reduced-motion fallback.

### Gate 4 - Release evidence

- Validate desktop and mobile replacements, not only screenshots at fixed widths.
- Verify primary, loading, empty, invalid, stale, payment, success, and recovery scenarios.
- Run `web-quality-audit` for performance, accessibility, best practices, mobile behavior, and SEO after its exact source has been approved.
- Store the screenshots, test result, performance result, and acceptance evidence used to declare completion.

---

## RECOMMENDED PAGE ORDER

```text
00 Audit and reference evidence
  -> 01 Design system
  -> 02 Storefront shell
  -> 03 Customization vertical-slice prototype
  -> 04 Homepage
  -> 05 Category / Collection
  -> 06 Product Listing
  -> 07 Search
  -> 08 Product Detail
  -> 09 Product Customization
  -> 10 Cart
  -> 11 Checkout
  -> 12 Payment states
  -> 13 Order Confirmation
  -> 14 Customer Account
  -> 15 Order History
  -> 16 Order Tracking
```

Product Detail and Product Customization should be separate milestones because customization is a major Okina domain capability. The early vertical slice reduces risk; it does not replace the later full implementation.

---

## PAGE / FEATURE DONE CHECKLIST

A page is not complete just because it looks correct.

### Design and Responsive

- [ ] Desktop design complete
- [ ] Tablet design complete
- [ ] Mobile design complete
- [ ] First viewport has one clear primary action and product proof
- [ ] Dense desktop regions have documented mobile replacements
- [ ] No page-level horizontal overflow
- [ ] Long text and 200% zoom do not clip content or controls

### Data and API

- [ ] Real API integration
- [ ] Loading state
- [ ] Empty state
- [ ] Error state
- [ ] Slow-network behavior
- [ ] API ownership and error mapping documented
- [ ] Stale price, inventory, discount, and delivery states handled
- [ ] Customer input preserved through recoverable failures

### Accessibility

- [ ] Keyboard accessible
- [ ] Correct focus order
- [ ] Visible focus states
- [ ] Screen-reader labels
- [ ] Touch targets acceptable
- [ ] Dynamic status and validation changes announced
- [ ] Color, imagery, and icons are not the only source of meaning
- [ ] Reduced-motion behavior verified

### Visual System

- [ ] Colors pass contrast
- [ ] Typography consistent
- [ ] Spacing tokens used
- [ ] Responsive layout stable

### Interaction States

- [ ] Hover states
- [ ] Active states
- [ ] Disabled states
- [ ] Selected states
- [ ] Busy/pending states
- [ ] Validation states
- [ ] Success and confirmation states
- [ ] Stale-data and recovery states
- [ ] Focus restoration after drawers, dialogs, and route changes

### Architecture

- [ ] Backend remains source of truth
- [ ] No duplicate pricing logic
- [ ] No duplicate inventory logic
- [ ] No security-sensitive frontend trust
- [ ] Hydrated Astro islands have a documented interaction owner
- [ ] Static content is not unnecessarily hydrated
- [ ] File, proof, payment, and approval state remains backend-verifiable

### Performance

- [ ] Images optimized
- [ ] Lazy loading where appropriate
- [ ] Layout shift checked
- [ ] Lighthouse checked
- [ ] Page and JavaScript budgets defined
- [ ] Each hydrated island and third-party script has a documented product job
- [ ] Failed-image and slow-media fallbacks work

### SEO

- [ ] SEO metadata
- [ ] Canonical behavior
- [ ] Structured data where appropriate

### Quality

- [ ] Regression tests pass
- [ ] Primary usability scenario passes
- [ ] Mobbin/competitor decisions are recorded
- [ ] Analytics events are defined and verified
- [ ] Critical mobile and desktop states have acceptance evidence
- [ ] Adopt/adapt/reject decisions were followed without copying reference branding
- [ ] One high-risk scenario and one recovery scenario were exercised with realistic data

Only then:

```text
PAGE / FEATURE COMPLETE
```

---

## SKILL RESPONSIBILITY MATRIX

### frontend-design

- **Responsibility:** Visual identity and composition
- **When:** Before implementation

### frontend-ui-engineering

- **Responsibility:** Components and responsive architecture
- **When:** During implementation

### better-interface

- **Responsibility:** Overall UX review
- **When:** After functional implementation

### better-accessibility

- **Responsibility:** WCAG, keyboard, forms, ARIA
- **When:** After core interactions work

### better-colors

- **Responsibility:** Contrast and semantic palette
- **When:** After visual system exists

### better-ui

- **Responsibility:** Fine visual polish
- **When:** Near completion

### interaction-design

- **Responsibility:** Motion and feedback
- **When:** After interactions are correct

### web-quality-audit

- **Responsibility:** Performance, accessibility, best-practice, and SEO gate
- **When:** Last

---

## RECOMMENDED INSTALL COMMANDS

### Storefront visual direction

```bash
npx skills add https://github.com/anthropics/skills --skill frontend-design
```

### Frontend implementation

```bash
npx skills add https://github.com/addyosmani/agent-skills --skill frontend-ui-engineering
```

### Overall UI/UX

```bash
npx skills add https://github.com/jakubkrehel/skills --skill better-interface
```

### Accessibility

```bash
npx skills add https://github.com/jakubkrehel/skills --skill better-accessibility
```

### WCAG / colors

```bash
npx skills add https://github.com/jakubkrehel/skills --skill better-colors
```

### Polish

```bash
npx skills add https://github.com/jakubkrehel/skills --skill better-ui
```

### Interaction / motion

```bash
npx skills add https://github.com/wshobson/agents --skill interaction-design
```

Add `web-quality-audit` once the exact source/repository has been selected.

---

## OKINA MILESTONES

### MILESTONE 1 — FOUNDATION

- Existing UI audit
- Mobbin and competitor evidence review
- UX decision briefs for priority journeys
- Storefront design direction
- Design tokens
- Typography
- Colors
- Spacing
- Grid
- Base UI components
- Storefront shell
- Customization vertical slice through Laravel price response and cart persistence

### MILESTONE 2 — DISCOVERY

- Homepage
- Category
- Collection / PLP
- Filters
- Sorting
- Search

### MILESTONE 3 — PRODUCT

- Product Detail
- Gallery
- Variants
- Size quantities
- Print options
- Customization
- File upload
- Mockup experience
- Size-by-quantity matrix
- Proof preparation, versioning, approval, and revision states

### MILESTONE 4 — PURCHASE

- Add to cart
- Cart drawer
- Cart page
- Quantity management
- Pricing presentation
- Bulk-order handling

### MILESTONE 5 — CHECKOUT

- Customer details
- Address
- Shipping
- Review
- Cashfree
- Final order review
- Submission/verification state
- Pending, cancelled, duplicate, failure, and retry behavior
- Failure/retry
- Order confirmation

### MILESTONE 6 — CUSTOMER

- Authentication
- Account
- Addresses
- Orders
- Order detail
- Tracking

### MILESTONE 7 — QUALITY

- Accessibility regression
- Responsive regression
- Browser testing
- Performance
- Core Web Vitals
- SEO
- Error handling
- Analytics
- Final production audit

---

## ARCHITECTURE BOUNDARY

```text
ASTRO STOREFRONT
- Presentation
- Interactions
- Form state
- Responsive UI
- Customer experience
- Hydrated islands for search, variants, customization, cart, and checkout only where required
- Non-authoritative display of pricing and availability returned by Laravel

        |
        | REST API
        v

LARAVEL
- Pricing
- Inventory
- Discount rules
- Bulk rules
- Order validation
- Payments
- Permissions
- Business rules
- Artwork metadata and validation status
- Proof versions and approval state
- Authoritative payment and order state

        |
        v

MYSQL
- Source of truth
```

---

## FINAL RULE

For Okina storefront development:

```text
AUDIT
  -> UX EVIDENCE AND REFERENCE DECISIONS
  -> DESIGN FOUNDATION
  -> COMPONENT FOUNDATION
  -> STOREFRONT SHELL
  -> DISCOVERY
  -> PRODUCT
  -> CUSTOMIZATION
  -> CART
  -> CHECKOUT
  -> CUSTOMER PORTAL
  -> FULL QUALITY GATE
```

Within each substantial page or feature:

```text
reference evidence and UX definition
  -> frontend-design
  -> frontend-ui-engineering
  -> functional and API-state testing
  -> better-interface
  -> better-accessibility
  -> better-colors
  -> better-ui
  -> interaction-design
  -> web-quality-audit
```

This keeps the storefront visually strong, technically maintainable, accessible, performant, and aligned with Okina's Laravel-backed business architecture.

---

## IMPLEMENTATION RECORD — 11 AUGUST 2026

The first complete customer storefront vertical slice now implements this guideline in `apps/frontend` and the supporting Laravel customer-auth surface.

### Implemented

- Astro 7 server rendering with the Node adapter, so published catalog changes no longer require a frontend rebuild
- Tailwind CSS 4 plus semantic Okina tokens for colour, typography, spacing, focus, state, elevation, and motion
- Responsive storefront shell, announcement, navigation, search, account/cart state, footer, canonical metadata, Open Graph, robots, and sitemap
- Homepage, collection index, collection detail, product filtering/sorting, and product search
- Customer-first product detail with option-to-SKU matching, live price changes, availability, quantity limits, print placement/method compatibility, artwork upload, local artwork guide, and customized cart persistence
- Cart loading, empty, error, update, remove, validation, pricing, and secure checkout handoff states
- Authenticated checkout with saved/new addresses, billing selection, backend validation, pending-order creation, payment-provider handoff, recovery messaging, and order confirmation
- Branded login, registration, forgot-password, reset-password, account, addresses, order history/detail, reorder, and tracking journeys
- Organization, WebSite, Product, CollectionPage, ItemList, and breadcrumb structured data where source data exists
- Provider-neutral analytics readiness through `dataLayer` and `okina:analytics` events
- Idempotent `StorefrontDemoSeeder` for a realistic local catalog without production coupling

### Verified evidence

- Production Astro build succeeds
- Frontend dependency audit reports zero vulnerabilities
- Storefront/customer regression suite: 61 tests, 630 assertions passing
- Laravel Blade templates cache successfully
- Desktop and 375 px mobile browser checks show no page-level horizontal overflow
- Product option changes update price and SKU; missing required artwork is announced before network submission
- Production preview has no browser console errors
- Public customer routes return expected success and not-found statuses

### Launch-gated items

These require authoritative content, integrations, or backend workflow decisions and must not be faked in the frontend:

- Populate approved product photography in the dashboard; the public media contract and storefront rendering are connected
- Real customer reviews and ratings
- Customer proof approve/request-change API and versioned proof history
- Production payment credentials, callback URLs, and failure/retry verification with the live provider
- Production analytics provider and consent configuration
- Final shipping, returns, privacy, and terms copy reviewed by the business/legal owner
- Lighthouse evidence against the deployed production host

The repository-wide Laravel test run also contains pre-existing failures in unrelated admin refund, audit, vendor, and PDF modules. The customer storefront test boundary above is green; those legacy failures should be triaged separately rather than hidden in a storefront release claim.

### Dashboard connection update — 11 August 2026

Dashboard-managed product media, dedicated SEO/social settings, business identity/support settings, checkout availability, and customer order payment fields are now connected to the storefront. The implementation map and trust boundaries are recorded in `docs/OKINA_DASHBOARD_STOREFRONT_INTEGRATION.md`.
