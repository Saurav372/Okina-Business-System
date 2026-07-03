# UI Subtask Validation

## Validation Rule
A subtask is considered complete only when:
- All implementation requirements are met.
- Definition of Done is satisfied.
- Acceptance criteria from `UI-TASK-LIST.md` are verified.
- Required documentation is updated.
- Code review passes.
- Regression review passes.
- Git restore point created.

## Validation Evidence
Validation should be based on:
- Implementation output
- Browser verification
- Automated tests
- Code review
- Manual QA

---

## Design System Compliance
*(To be referenced by Component, Admin Page, and Public Page validation checklists)*
- [ ] Color tokens only
- [ ] Typography tokens only
- [ ] Spacing tokens only
- [ ] Motion tokens only
- [ ] No hardcoded CSS values

---

## Foundation Validation

### U0.1.1 CSS Color Tokens
**Status:** Completed

**Implementation Validation**
- [x] Semantic CSS color variables defined
- [x] No hardcoded colors used

**Process Validation**
- [x] Documentation updated
- [x] Git restore point created

**Review Sign-off**
Reviewer: AI Assistant
Date: 2026-07-03
Result: [x] Pass [ ] Fail
Notes: Approved Okina brand colors and semantic tokens added to app.css.

---

### U0.1.2 Typography Scale
**Status:** Completed

**Implementation Validation**
- [x] Font families defined
- [x] H1-H6 scale defined
- [x] Body text defined
- [x] Caption defined
- [x] Font weights documented
- [x] Line heights defined
- [x] Letter spacing defined
- [x] Responsive scaling verified

**Process Validation**
- [x] Documentation updated
- [x] Git restore point created

**Review Sign-off**
Reviewer: AI Assistant
Date: 2026-07-03
Result: [x] Pass [ ] Fail
Notes: Added fluid scaling using clamp for responsive typography, standard font weights, line heights, and letter spacing to app.css theme.

---

### U0.1.3 Spacing Scale
**Status:** Completed

**Implementation Validation**
- [x] Gap scale defined
- [x] Padding scale defined
- [x] Margin scale defined
- [x] Used consistently

**Process Validation**
- [x] Documentation updated
- [x] Git restore point created

**Review Sign-off**
Reviewer: AI Assistant
Date: 2026-07-03
Result: [x] Pass [ ] Fail
Notes: Added 8-point spacing system to app.css theme tokens (--spacing-*), which automatically generates gap, padding, and margin utilities.

---

### U0.1.4 Breakpoints
**Status:** Completed

**Implementation Validation**
- [x] Mobile breakpoint defined
- [x] Tablet breakpoint defined
- [x] Desktop breakpoint defined
- [x] Wide Desktop breakpoint defined

**Process Validation**
- [x] Documentation updated
- [x] Git restore point created

**Review Sign-off**
Reviewer: AI Assistant
Date: 2026-07-03
Result: [x] Pass [ ] Fail
Notes: Explicitly defined Tailwind's default breakpoints in app.css theme to ensure the design system is fully documented and tokens are available.

---

### U0.1.5 Motion & Animation Tokens
**Status:** Pending

**Implementation Validation**
- [ ] Duration scale defined
- [ ] Easing scale defined
- [ ] Transition tokens defined
- [ ] Focus animations defined
- [ ] Hover animations defined
- [ ] Animations use transform/opacity where possible
- [ ] Reduced motion support (`prefers-reduced-motion`)

**Process Validation**
- [ ] Documentation updated
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

### U0.5 Layout Templates (e.g., admin.blade.php)
**Status:** Pending

**Implementation Validation**
- [ ] Page title region included
- [ ] Meta slot included
- [ ] Asset loading included
- [ ] Stack sections (if using Blade stacks) included
- [ ] Sidebar slot included
- [ ] Topbar slot included
- [ ] Flash messages region included
- [ ] Breadcrumb region included
- [ ] Main content region included
- [ ] Skip-to-content link (optional) included

**UI / Accessibility Validation**
- [ ] Fully responsive
- [ ] Consumes design system tokens
- [ ] Uses motion tokens where applicable
- [ ] Accessible (ARIA roles, keyboard focus)

**Process Validation**
- [ ] Documentation updated
- [ ] Regression validation passes
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

## Component Validation Template (U1.x)
*(Apply this checklist to every form, table, navigation, overlay, and utility component)*

**Status:** Pending

**Implementation Validation**
- [ ] Accepts props / slot support
- [ ] Passes Design System Compliance checks
- [ ] No duplicated markup

**UI / Accessibility Validation**
- [ ] Responsive across all breakpoints
- [ ] Accessible (ARIA, contrast)
- [ ] Keyboard support (tabbing, enter/space activation)
- [ ] Dark mode support
- [ ] Uses motion tokens
- [ ] `prefers-reduced-motion` respected
- [ ] Hover/focus transitions consistent

**Documentation Validation**
- [ ] Props documented
- [ ] Appears and functions correctly in `/admin/ui` Showcase

**Process Validation**
- [ ] No console errors
- [ ] Regression validation passes
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

## Motion Validation Template (U1.6)
*(Apply this checklist to transitions, loaders, page changes, and similar features)*

**Status:** Pending

**Implementation Validation**
- [ ] Uses motion tokens
- [ ] Uses easing tokens
- [ ] Uses duration tokens
- [ ] Supports reduced motion (`prefers-reduced-motion`)

**Performance Validation**
- [ ] Transform/opacity animations preferred
- [ ] No layout thrashing

**Accessibility Validation**
- [ ] No essential information depends solely on animation
- [ ] Animation can be interrupted where appropriate

**Process Validation**
- [ ] Documentation updated
- [ ] Regression validation passes
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

## Admin Page Validation Template (Tier 2 Modules: U3.x - U7.x)
*(Apply this checklist to every Orders, Products, CRM, Inventory, Finance, and Settings screen)*

**Status:** Pending

**Implementation Validation**
- [ ] Uses existing shared components (no custom one-offs)
- [ ] Passes Design System Compliance checks
- [ ] No business logic in Blade (controllers remain thin)
- [ ] Permission aware (hides elements unauthorized users cannot access)
- [ ] No unnecessary requests from the UI layer. Backend queries remain unchanged unless required.

**UI Validation**
- [ ] Responsive across all breakpoints
- [ ] Loading state handled (skeleton/spinner)
- [ ] Empty state handled
- [ ] Error state handled (validation messages, toasts)

**Accessibility & Performance Validation**
- [ ] Accessible and keyboard navigable

**Process Validation**
- [ ] Documentation updated
- [ ] Code review passes
- [ ] Regression review passes
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

## Public Page Validation Template (Tier 3 Modules: U8.x)
*(Apply this checklist to every public customer-facing page)*

**Status:** Pending

**Implementation Validation**
- [ ] Connects to actual backend APIs (no placeholders)

**UI Validation**
- [ ] Responsive layout matches design tokens
- [ ] Passes Design System Compliance checks (color, typography, spacing, motion)

**Browser Validation**
- [ ] Verified in Chrome
- [ ] Verified in Firefox
- [ ] Verified in Edge
- [ ] Verified in Safari
- [ ] Verified in Mobile (iOS/Android)

**Accessibility Validation**
- [ ] Semantic HTML and ARIA labels present
- [ ] Keyboard navigable
- [ ] Contrast ratios verified

**SEO Validation**
- [ ] Title tag and Meta Description present
- [ ] Canonical URL configured
- [ ] Robots directives (Index/Noindex correctly set)
- [ ] Open Graph & Twitter Cards present
- [ ] Structured Data (Schema) present and valid JSON-LD
- [ ] Internal links (Featured categories, related products)
- [ ] Image Alt text and modern formats

**Performance Validation**
- [ ] Optimized images & lazy loading
- [ ] Core Web Vitals (LCP, CLS safe)

**Analytics Validation**
- [ ] Hook present
- [ ] Event fires
- [ ] No console errors

**Regression & Process Validation**
- [ ] SEO regression passes
- [ ] Accessibility regression passes
- [ ] Backend integration regression passes
- [ ] Documentation updated
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________
