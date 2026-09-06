# Workspace Rules

## UI & UX Design
- Use Mobbin patterns and Mobbin MCP for reference when building UI and UX flows, layouts, component interactions, and storefront design systems.

## UI-Skills Standards (Design Engineering & Interface Quality)
Incorporate the core principles from **[ui-skills.com](http://ui-skills.com/)** (`baseline-ui`, `better-ui`, `better-typography`, `glassmorphism`, `micro-interaction`):

1. **Anti-Slop Spacing & Hierarchy (`baseline-ui`)**:
   - Establish strict visual hierarchy with deliberate optical weight, measure, and rhythm.
   - Avoid generic, bloated margins; use consistent 4px/8px scale tokens.
   - For all icon-only buttons (search, cart trigger, drawer close), `aria-label` is mandatory.

2. **Micro-Interactions & Transitions (`micro-interaction`, `transitions-polish`)**:
   - Hover and press feedback: use subtle scale transforms (`hover:scale-[1.02]`, `active:scale-[0.98]`) with smooth cubic-bezier easing (`ease-out`, 150-200ms).
   - Drawers & slide-outs: smoothly slide on `transform: translateX(...)` off-main-thread with a backdrop blur overlay (`backdrop-blur-md bg-black/40`), keyboard trap, and `Esc` key dismissal.

3. **Typography & Contrast (`better-typography`, `better-colors`)**:
   - Optical font sizing with tight letter-spacing on bold titles (`tracking-tight` / `-0.02em`) and generous tracking on uppercase eyebrow badges (`tracking-widest` / `+0.05em`).
   - High-contrast text: deep `#111111` for sumi-black text, muted `#666666` for metadata, and `#E83535` for purposeful flame accents. Never use washed-out low-contrast gray text on interactive links.

4. **Smooth Surface Elevations (`smooth-shadow-ring`)**:
   - Avoid harsh double borders. Use soft subtle ring highlights with layered ambient shadows for floating navbars, cards, and slide-out drawers (`shadow-sm ring-1 ring-black/5` or `shadow-2xl`).

5. **Accessibility & Responsive Polish (`better-accessibility`)**:
   - Touch targets must meet the minimum 44×44px standard on mobile.
   - Trap focus when the cart drawer is open; restore focus on close.
   - Full keyboard navigation support (Tab, Enter, Escape).
