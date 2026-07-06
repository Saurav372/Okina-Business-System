@props(['separator' => null])

@php
    $defaultSeparator = '<svg style="width:1rem;height:1rem;flex-shrink:0;color:var(--color-text-muted,#9ca3af)" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>';
    $sep = (string) ($separator ?? $defaultSeparator);
    $content = str_replace('<!-- BREADCRUMB_SEPARATOR -->', $sep, (string) $slot);
@endphp

<style>
    .ui-bc-nav {
        display: block;
        width: 100%;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .ui-bc-nav::-webkit-scrollbar { display: none; }

    /* ol must NOT have width:100% — let it grow naturally */
    .ui-bc-list {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    /* Hide separator after last visible li */
    .ui-bc-list > li:last-child > .bc-sep { display: none; }

    /* Active item: never shrink, never truncate */
    .bc-item-active { flex-shrink: 0; }
    .bc-item-active .bc-label { white-space: nowrap; }

    /* Inactive items: can truncate */
    .bc-item-inactive { flex-shrink: 1; min-width: 0; max-width: 140px; }
    .bc-item-inactive .bc-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: block;
    }
    @media (max-width: 640px) {
        .bc-item-inactive { max-width: 80px; }
    }
</style>

<nav
    x-data="{
        open: false,
        collapsed: [],

        init() {
            /* Wait for full layout before measuring */
            setTimeout(() => {
                this.setup();
                new ResizeObserver(() => this.recalc()).observe(this.$el);
            }, 50);
        },

        setup() {
            /* Move ellipsis <li> to sit after the first crumb item */
            const ol    = this.$refs.ol;
            const ell   = this.$refs.ell;
            const first = ol.querySelector('li.bc-item');
            if (first) first.after(ell);
            this.recalc();
        },

        recalc() {
            const nav   = this.$el;
            const ol    = this.$refs.ol;
            const ell   = this.$refs.ell;
            const items = [...ol.querySelectorAll('li.bc-item')];

            /* ── 1. Full reset ── */
            items.forEach(li => li.style.display = '');     /* show all */
            ell.style.display = 'none';                     /* hide ellipsis */
            this.collapsed    = [];

            if (items.length < 3) return;

            /*
             * ── 2. Detect overflow ──
             * nav has overflow-x:auto → it is the scroll container.
             * nav.scrollWidth = total content width (= ol natural width)
             * nav.clientWidth = visible container width
             * When scrollWidth > clientWidth, content overflows.
             */
            if (nav.scrollWidth <= nav.clientWidth) {
                nav.scrollLeft = 0;
                return;
            }

            /* ── 3. Show ellipsis first (it takes space, factor it in) ── */
            ell.style.display = 'inline-flex';

            /* ── 4. Collapse middle items left→right until it fits ── */
            const middle = items.slice(1, -1);     /* skip first & last */
            for (const li of middle) {
                if (nav.scrollWidth <= nav.clientWidth) break;

                /* Read label/href BEFORE hiding */
                const label = li.querySelector('.bc-label')?.textContent?.trim() ?? '';
                const href  = li.querySelector('a')?.getAttribute('href') ?? '#';

                /* Inline style → immediate effect (no CSS cascade delay) */
                li.style.display = 'none';
                this.collapsed.push({ label, href });
            }

            /* ── 5. If nothing was collapsed, remove ellipsis ── */
            if (this.collapsed.length === 0) {
                ell.style.display = 'none';
            }

            /* ── 6. Scroll to reveal active item ── */
            nav.scrollLeft = nav.scrollWidth;
        }
    }"
    @click.outside="open = false"
    aria-label="Breadcrumb"
    class="ui-bc-nav"
    {{ $attributes }}
>
    <ol x-ref="ol" class="ui-bc-list">

        {{-- ── Ellipsis (hidden by default, moved by setup()) ── --}}
        <li x-ref="ell" style="display:none;align-items:center;gap:0.5rem;flex-shrink:0">
            <div style="position:relative">
                {{-- Button --}}
                <button
                    type="button"
                    @click.stop="open = !open"
                    :aria-expanded="String(open)"
                    aria-label="Show hidden pages"
                    style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:1.5rem;border-radius:4px;font-size:0.7rem;font-weight:700;cursor:pointer;border:1px solid var(--color-border,#e5e7eb);background:var(--color-surface-secondary,#f3f4f6);color:var(--color-text-muted,#6b7280);transition:all 120ms"
                    onmouseenter="this.style.background='var(--color-neutral-200,#e5e7eb)'"
                    onmouseleave="this.style.background='var(--color-surface-secondary,#f3f4f6)'"
                >···</button>

                {{-- Dropdown --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    style="display:none;position:absolute;top:calc(100% + 6px);left:0;z-index:200;min-width:180px;background:var(--color-surface,#fff);border:1px solid var(--color-border,#e5e7eb);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);padding:4px 0"
                >
                    <template x-for="(item, i) in collapsed" :key="i">
                        <a
                            :href="item.href"
                            x-text="item.label"
                            style="display:block;padding:8px 16px;font-size:0.875rem;white-space:nowrap;text-decoration:none;color:var(--color-text-secondary,#4b5563);transition:background 80ms"
                            onmouseenter="this.style.background='var(--color-surface-secondary,#f9fafb)'"
                            onmouseleave="this.style.background=''"
                        ></a>
                    </template>
                </div>
            </div>

            {{-- Separator after ellipsis --}}
            <span class="bc-sep" aria-hidden="true" style="display:flex;align-items:center;color:var(--color-text-muted,#9ca3af);flex-shrink:0">
                {!! $sep !!}
            </span>
        </li>

        {!! $content !!}
    </ol>
</nav>
