# Component API Standards

As the Okina Design System component library grows, adherence to strict API standards ensures predictability, reusability, and minimal maintenance overhead. Every UI component (specifically forms, tables, and overlays) must adhere to these guidelines.

## 1. Props Naming & Data Flow
Component properties must be strictly defined in the `@props` array and stick to standard terminology. Avoid inventing custom prop names when standard HTML/system concepts already apply.

### Standard Form Props
- `id` (Required string)
- `name` (Optional string)
- `label` (Optional string)
- `hint` (Optional string)
- `error` (Optional string)
- `required` (Boolean, default `false`)
- `disabled` (Boolean, default `false`)
- `readonly` (Boolean, default `false`)
- `value` (Any, default `null`)

## 2. Attribute Forwarding Rules
Components must explicitly forward unhandled attributes down to the primary HTML element (usually the native `<input>`, `<select>`, `<svg>`, etc.) using `{{ $attributes }}` or `{{ $attributes->class([...]) }}`.

- **Do Not:** Introduce props like `debounce` or `live` for Livewire.
- **Do:** Allow developers to pass `wire:model.live.debounce.300ms="query"` natively, relying on `$attributes` to forward the directive to the inner element.

## 3. Named Slot Conventions
Do not use string props to inject rich content. Use named slots.

- `<x-slot:prefix>`: Used for prepending icons, badges, or context indicators (e.g. `$`).
- `<x-slot:suffix>`: Used for appending action buttons (e.g., eye icon for passwords), clearing triggers, or context (e.g., `%`).
- `<x-slot:actions>`: Used in layout components (cards, modals, headers) to inject interactive buttons.

*Note: Named slots automatically populate `$slotName` variables inside the component scope (e.g., `$prefix`). Check for their existence using `isset($prefix)`.*

## 4. Accessibility Requirements
Every interactive component must prioritize accessibility out-of-the-box without requiring the developer to wire it manually.

- **ARIA Attributes:** Automatically generate `aria-describedby` linking the input to its corresponding hint (`[id]-hint`) and error (`[id]-error`) elements.
- **Error States:** Automatically inject `aria-invalid="true"` when the `error` prop is present.
- **Focus States:** Every interactive element must have a visible focus ring using the standard token (e.g., `focus:ring-[color:var(--focus-ring-color)]`).
- **Required Markers:** If `required=true`, generate a visual indicator (e.g., `<span aria-hidden="true">*</span>`) and apply the native `required` HTML attribute.

## 5. Design Token Usage
Never hardcode colors, spacing, borders, or motion timings. Always map styling to CSS variables defined in `app.css`.

- **Colors:** `text-[color:var(--color-text-primary)]`, `bg-[color:var(--color-surface)]`, `border-[color:var(--color-danger)]`
- **Spacing:** `px-[var(--spacing-4)]`, `gap-[var(--spacing-2)]`
- **Typography:** `text-[length:var(--text-body)]`
- **Motion:** `transition-colors duration-[var(--motion-fast)] ease-[var(--motion-ease)]`

## 6. Composition Guidelines
Avoid duplicating logic and CSS definitions. Build complex components by composing simpler ones in a strict hierarchy.

**Example Hierarchy (Forms):**
```text
<x-form.wrapper> (Owns layout, labels, error text)
        │
        ▼
<x-form.input> (Owns styling, focus rings, base accessibility)
        │
        ├──────────────┐
        ▼              ▼
<x-form.search>   <x-form.password>
(Owns type="search", prefix icon)
```
- A specialized component (like Search) must *never* implement its own wrapper or duplicate styling strings. It must pass props down to the base control.

## 7. Icon System
- All icons must reside in `resources/views/components/icons/` as individual Blade files.
- Use **Lucide** as the standard SVG source.
- Do not hardcode dimensions or colors inside the SVG path.
- The `<svg>` tag must forward attributes explicitly using `{{ $attributes->class(['w-5 h-5']) }}` so sizes and colors can be overridden per instance.

## 8. Query String Preservation (Pagination & Filters)
When building components that navigate data states (such as Pagination):
- The component must **not** attempt to reconstruct or append query strings manually.
- The component strictly renders URLs provided to it (e.g., `$paginator->nextPageUrl()`).
- Controllers, Livewire components, or services hold the responsibility to configure query preservation (e.g., calling `withQueryString()` or `appends()`) before injecting the paginator into the view.
- This maintains clean separation of responsibilities between UI rendering and application routing logic.

## 9. Validation Checklist
Before any component is marked complete, verify:
1. Prop typings and defaults are strictly defined.
2. No duplicated styling logic exists (leverages composition).
3. Standard accessibility (`aria-describedby`, `aria-invalid`) functions automatically.
4. Livewire bindings pass seamlessly through `$attributes`.
5. No duplicate wrappers are generated when nested.
6. The component renders correctly across all states (empty, disabled, error, required) in the `component-showcase`.
