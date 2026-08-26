# Storefront UI Test Lab

This folder is a separate playground for trying different storefront designs.
Nothing in this folder is part of the production Astro storefront.

## Workflow

1. Create each design inside `concepts/` using a separate folder, such as
   `concepts/minimal-storefront` or `concepts/editorial-storefront`.
2. Test and compare the designs here without changing `apps/frontend`.
3. Put reusable images, icons, and fonts in `shared-assets/`.
4. Keep screenshots and visual inspiration in `references/`.
5. After choosing a design, recreate the approved version in the Astro
   storefront under `apps/frontend`.

## Rules

- Do not import production code from `apps/frontend` into a UI concept.
- Keep every concept self-contained so it can be changed or removed safely.
- Give concepts descriptive names rather than overwriting an earlier idea.
- Treat this folder as experimental; only the chosen design moves to Astro.

## Suggested concept structure

```text
concepts/
  concept-name/
    index.html
    styles.css
    script.js
    assets/
```

