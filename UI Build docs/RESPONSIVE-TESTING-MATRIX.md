# Responsive Testing Matrix
 
This matrix lists the recommended viewport widths for QA testing. These match common real-world device sizes and ensure our design system is robust across all breakpoints.
 
| Viewport Width | Typical Device |
| --- | --- |
| 320px | Small older mobile (e.g. iPhone SE 1st gen) |
| 360px | Common Android mobile |
| 393px | iPhone 14/15 |
| 412px | Pixel / modern Android |
| 430px | iPhone 14/15 Pro Max |
| 480px | Mobile landscape / Small tablets |
| 640px | Large mobile landscape |
| 768px | iPad portrait / Tablets |
| 834px | iPad Pro 11" portrait |
| 1024px | iPad landscape / Small laptops |
| 1280px | Standard laptops / MacBooks |
| 1440px | Desktop monitors |
| 1728px | MacBook Pro 16" |
| 1920px | 1080p Desktop monitors |
| 2560px | 1440p / 2K Monitors |
| 3440px | 21:9 Ultrawide Monitors |
 
## Module-Specific Responsive Checkpoints
 
### 1. Admin Dashboard
- **Large Viewports (>=1024px)**: Standard three-column or two-column grid showcasing KPI widgets, charts, and chronological recent activity sidebar side-by-side.
- **Mobile Viewports (<=768px)**: Stacked linear flow. KPI widgets collapse to single columns, charts scale horizontally with responsive SVG ratios, and recent activity shifts below charts.
 
### 2. Sales Order Detail Page
- **Large Viewports (>=1024px)**: Two-column grid with tab contents on the left and side metadata cards (Customer Profile, Notes) on the right.
- **Mobile Viewports (<=768px)**: Stacked single-column view. Sidebar metadata blocks move to the bottom. Horizontal scrolling tab menu allows full navigation without line wrapping.
- **PDF Preview Overlay**: Iframe preview modal scales to 85% viewport height on desktop and shifts to clean fullscreen cover with touch-scrollable download buttons on mobile.
 
### 3. Business Settings Dashboard
- **Large Viewports (>=768px)**: Left-aligned sidebar with vertical category selectors and right-aligned form cards.
- **Mobile Viewports (<768px)**: Sidebar shifts to a horizontal nav list of categories at the top, stacking input forms below to preserve grid structure.
 
### 4. Ledger Tables
- **Mobile Viewports (<768px)**: Data tables wrapped in `.overflow-x-auto` to enable smooth swipe-scroll of monetary lists (debits, credits, balances) without distorting the global application shell width.
