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

## Foundation Validation

### U0.1.1 CSS Color Tokens
**Status:** Pending

**Implementation Validation**
- [ ] Semantic CSS color variables defined
- [ ] No hardcoded colors used

**Process Validation**
- [ ] Documentation updated
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

### U0.1.2 Typography Scale
**Status:** Pending

**Implementation Validation**
- [ ] H1-H6 scale defined
- [ ] Body text defined
- [ ] Caption defined
- [ ] Font weights documented
- [ ] Responsive scaling verified

**Process Validation**
- [ ] Documentation updated
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

### U0.1.3 Spacing Scale
**Status:** Pending

**Implementation Validation**
- [ ] Gap scale defined
- [ ] Padding scale defined
- [ ] Margin scale defined
- [ ] Used consistently

**Process Validation**
- [ ] Documentation updated
- [ ] Git restore point created

**Review Sign-off**
Reviewer: __________
Date: __________
Result: ☐ Pass ☐ Fail
Notes: __________________

---

### U0.1.4 Breakpoints
**Status:** Pending

**Implementation Validation**
- [ ] Mobile breakpoint defined
- [ ] Tablet breakpoint defined
- [ ] Desktop breakpoint defined
- [ ] Wide Desktop breakpoint defined

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
- [ ] Consumes theme tokens
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
- [ ] Uses theme tokens (no hardcoded CSS)
- [ ] No duplicated markup

**UI / Accessibility Validation**
- [ ] Responsive across all breakpoints
- [ ] Accessible (ARIA, contrast)
- [ ] Keyboard support (tabbing, enter/space activation)
- [ ] Dark mode support

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

## Admin Page Validation Template (Tier 2 Modules: U3.x - U7.x)
*(Apply this checklist to every Orders, Products, CRM, Inventory, Finance, and Settings screen)*

**Status:** Pending

**Implementation Validation**
- [ ] Uses existing shared components (no custom one-offs)
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

**Browser Validation**
- [ ] Verified in Chrome
- [ ] Verified in Firefox
- [ ] Verified in Edge
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
