# OKINA STOREFRONT COMPETITOR REFERENCE NOTES

## Purpose

These notes record useful storefront and customization patterns observed in the user-provided Destiny Clothing homepage PDF and product-page image.

The reference is design evidence, not a template. Okina should preserve its own brand, content, interaction model, accessibility standard, and Laravel-owned business rules.

---

## COMPETITOR POSITIONING

The competitor presents itself as a custom-apparel supplier for teams, companies, institutions, events, and bulk buyers rather than as a general fashion store.

The main commercial messages are:

- Put a logo or design on apparel
- Buy in packs or bulk quantities
- Request design help when artwork is not ready
- Receive a design for approval after ordering
- Choose from several apparel types, colors, and sizes
- Use one supplier for printing, production, packing, and delivery

### Okina implication

Okina should make its primary job equally clear in the first viewport:

> Choose a product, configure quantities, provide artwork or request design help, approve the proof, and place a reliable custom order.

---

## HOMEPAGE OBSERVATIONS

### Useful patterns to adopt

1. **Product-specific hero**
   - The hero immediately shows customizable apparel rather than generic lifestyle photography.
   - The headline connects merchandise to customer identity.
   - A direct shopping action is visible.

2. **Early commercial qualifiers**
   - Bulk ordering, product quality, and delivery reach appear close to the hero.
   - These messages help visitors decide whether the supplier fits their job.

3. **Category-led discovery**
   - Visitors can enter through hoodies, polos, T-shirts, varsity jackets, oversized fits, and similar product families.

4. **Recognizable customer proof**
   - A client-logo band reduces perceived risk for corporate and institutional buyers.
   - Okina should use logos only with permission and provide accessible text alternatives.

5. **Product and use-case merchandising**
   - Products are grouped by apparel type and event/customer need.
   - This supports both customers who know the product and customers who only know the occasion or requirement.

6. **Production and service reassurance**
   - Quality, comfort, bulk support, delivery, and manufacturing-related proof appear throughout the page.

7. **Educational content**
   - Articles support search discovery and help customers understand garments, printing, and product selection.

### Patterns to adapt carefully

- Replace generic `Your Design Here` mockups with realistic Okina examples showing different print methods, placements, fabrics, and order types.
- Balance product-category browsing with entry points such as corporate uniforms, events, college merchandise, teams, staff apparel, and creator merchandise.
- Treat client logos as evidence, not decoration. Link them to a short outcome, product, quantity, or case study when possible.
- Give each product card one clear primary action and show starting price, minimum quantity, available colors, and customization capability consistently.

### Patterns to avoid

- Large amounts of image-embedded text that cannot be searched, translated, resized, or read by assistive technology.
- Horizontal product regions that resemble broken browser scrollbars or hide navigation controls.
- Repeating nearly identical product cards without helping customers compare fabric, fit, print method, minimum quantity, or delivery time.
- Generic or unverified claims such as `premium quality` without evidence.
- Too many unrelated accent colors and visual treatments competing for attention.
- Blog and promotional sections that are present only to make the page longer.

---

## CUSTOMIZABLE PRODUCT PAGE OBSERVATIONS

### Useful patterns to adopt

1. **Front and back product proof**
   - The primary media shows how artwork can appear on both sides of the garment.

2. **Separate artwork inputs**
   - Front-logo and back-logo uploads make placement intent explicit.

3. **Assisted-design fallback**
   - Customers without finished artwork can supply text and request design help.

4. **Proof-approval reassurance**
   - The page explains that a design will be shared for approval after the order is confirmed.

5. **Pack and bulk pricing**
   - Quantity tiers are visible before add to cart.

6. **Color and size visibility**
   - Color choices, available sizes, and a size guide are close to the ordering controls.

7. **Visible order process**
   - A later section explains the path from order placement to production, packing, and dispatch.

8. **Customer evidence**
   - Reviews include product images, which are especially useful for customized products.

### Required Okina adaptation

Okina should convert these ideas into a clearer configuration flow:

```text
Choose garment and variants
  -> Choose customization method and placement
  -> Upload artwork, enter text, or request design support
  -> Validate files and show upload progress
  -> Show preview or explain when the proof will be prepared
  -> Select size-by-quantity distribution
  -> Recalculate backend-authoritative price
  -> Review configuration and proof terms
  -> Add to cart
```

### Artwork input requirements

- Offer explicit choices: `Upload artwork`, `Enter text`, and `Request design help`.
- Allow front, back, sleeve, or other backend-supported placements without hard-coding unsupported options.
- State accepted file types, maximum size, resolution guidance, transparency requirements, and privacy handling before upload.
- Show uploading, processing, success, unsupported-file, too-large, network-failure, remove, replace, and retry states.
- Preserve uploaded files and entered text when another validation error occurs.
- Do not imply that an automated mockup is a production-approved proof.

### Pricing and quantity requirements

- Display `price per unit`, `quantity`, `order total`, and `savings` separately.
- Make it clear whether a pack price is a total or a per-unit amount.
- Support size-by-quantity distribution for team and bulk orders.
- Explain whether different colors, sizes, placements, or print methods affect the price.
- Revalidate price, stock, thresholds, and delivery estimates through Laravel before cart and checkout confirmation.

### Proof and approval requirements

- Explain when the first proof will be provided.
- State how many revisions are included and what changes may affect price or delivery.
- Provide explicit actions such as `Approve proof` and `Request changes`.
- Record proof version, approval status, timestamp, and customer decision.
- Show what happens if the customer does not approve the proof on time.

### Cart summary requirements

Each customized cart item should retain and display:

- Product and selected color
- Size-by-quantity breakdown
- Print method and placements
- Artwork filenames or thumbnails
- Entered customization text
- Design-help request
- Proof status
- Unit price and order total
- Edit-configuration action

### Patterns to avoid

- Ambiguous pack prices.
- Tiny controls and low-contrast labels.
- Color choices communicated only through unlabelled thumbnails.
- File inputs with no validation or progress information.
- A single long product title carrying information that should be structured attributes.
- A large empty gap between product details and supporting content.
- Floating support controls that cover important actions on small screens.
- Treating post-purchase manual messaging as the only record of artwork approval.

---

## OKINA DIFFERENTIATION OPPORTUNITIES

Okina can improve on the reference by providing:

- A guided custom-order builder instead of a dense set of unrelated controls
- Transparent per-unit and total pricing
- A size-and-quantity matrix designed for teams and organizations
- Clear artwork validation and upload recovery
- A versioned digital proof and approval record
- Reliable cart editing without losing uploaded work
- Delivery estimates that respond to quantity, proof approval, and production method
- Accessible labels, keyboard operation, status announcements, and mobile replacements
- Real production examples tied to material, print method, quantity, and outcome

---

## REFERENCE DECISION

Use the competitor as inspiration for commercial clarity, assisted customization, quantity tiers, proof reassurance, production transparency, and customer evidence.

Do not copy its layout, image-embedded text, visual identity, ambiguous pricing presentation, spacing problems, or accessibility weaknesses.

