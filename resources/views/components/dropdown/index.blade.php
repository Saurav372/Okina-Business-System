@props([
    'side' => 'bottom',
    'align' => 'start',
    'offset' => 8,
    'closeOnClick' => true
])

<div
    x-data="{
        open: false,
        side: '{{ $side }}',
        align: '{{ $align }}',
        offset: {{ (int) $offset }},
        closeOnClick: {{ $closeOnClick ? 'true' : 'false' }},
        activeId: -1,
        triggerEl: null,
        contentEl: null,

        init() {
            this.triggerEl = this.$el.querySelector('[data-dropdown-trigger]');
            this.contentEl = this.$el.querySelector('[data-dropdown-menu]');

            this.$watch('open', value => {
                if (value) {
                    this.activeId = -1;
                    this.$nextTick(() => {
                        this.position();
                        this.focusFirst();
                    });
                } else {
                    if (this.triggerEl) {
                        const innerBtn = this.triggerEl.querySelector('button, a, input, select') || this.triggerEl;
                        innerBtn.focus();
                    }
                }
            });

            if (typeof ResizeObserver !== 'undefined') {
                const ro = new ResizeObserver(() => {
                    if (this.open) this.position();
                });
                ro.observe(this.$el);
            }
        },

        toggle() {
            this.open = !this.open;
        },

        focusFirst() {
            const items = this.getEnabledItems();
            if (items.length > 0) {
                this.activeId = 0;
                items[0].focus();
            }
        },

        focusNext() {
            const items = this.getEnabledItems();
            if (items.length === 0) return;
            this.activeId = (this.activeId + 1) % items.length;
            items[this.activeId].focus();
        },

        focusPrev() {
            const items = this.getEnabledItems();
            if (items.length === 0) return;
            this.activeId = (this.activeId - 1 + items.length) % items.length;
            items[this.activeId].focus();
        },

        focusLast() {
            const items = this.getEnabledItems();
            if (items.length > 0) {
                this.activeId = items.length - 1;
                items[this.activeId].focus();
            }
        },

        focusChar(char) {
            const items = this.getEnabledItems();
            const lowerChar = char.toLowerCase();
            for (let i = 1; i <= items.length; i++) {
                const idx = (this.activeId + i) % items.length;
                const label = items[idx].querySelector('.bc-label, span, div') || items[idx];
                const text = label.textContent || '';
                if (text.trim().toLowerCase().startsWith(lowerChar)) {
                    this.activeId = idx;
                    items[idx].focus();
                    break;
                }
            }
        },

        getEnabledItems() {
            if (!this.contentEl) return [];
            return Array.from(this.contentEl.querySelectorAll('[data-dropdown-item]'))
                .filter(el => !el.disabled && el.getAttribute('aria-disabled') !== 'true');
        },

        position() {
            if (!this.contentEl || !this.triggerEl) return;

            // Reset styles for measurement
            this.contentEl.style.top = '';
            this.contentEl.style.left = '';
            this.contentEl.style.right = '';
            this.contentEl.style.bottom = '';
            this.contentEl.style.maxHeight = '';
            this.contentEl.style.width = '';

            const triggerRect = this.triggerEl.getBoundingClientRect();
            const isFit = this.contentEl.getAttribute('data-width-fit') === 'true';
            
            if (isFit) {
                this.contentEl.style.width = `${triggerRect.width}px`;
            }

            const contentRect = this.contentEl.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const isRtl = document.documentElement.dir === 'rtl';

            // ── Priority 1: Side collision flipping ──
            let currentSide = this.side;
            if (currentSide === 'bottom' && triggerRect.bottom + contentRect.height + this.offset > viewportHeight) {
                if (triggerRect.top - contentRect.height - this.offset > 0) {
                    currentSide = 'top';
                }
            } else if (currentSide === 'top' && triggerRect.top - contentRect.height - this.offset < 0) {
                if (triggerRect.bottom + contentRect.height + this.offset < viewportHeight) {
                    currentSide = 'bottom';
                }
            }

            // ── Priority 2: Alignment flipping ──
            let currentAlign = this.align;
            if (currentSide === 'bottom' || currentSide === 'top') {
                if (currentAlign === 'start') {
                    const spaceRight = viewportWidth - (isRtl ? triggerRect.right : triggerRect.left);
                    if (spaceRight < contentRect.width && (isRtl ? triggerRect.right : triggerRect.left) > contentRect.width) {
                        currentAlign = 'end';
                    }
                } else if (currentAlign === 'end') {
                    const spaceLeft = isRtl ? triggerRect.right : triggerRect.left;
                    if (spaceLeft < contentRect.width && (viewportWidth - (isRtl ? triggerRect.right : triggerRect.left)) > contentRect.width) {
                        currentAlign = 'start';
                    }
                }
            }

            // Compute positions
            let top = 0;
            let left = 0;

            if (currentSide === 'bottom') {
                top = triggerRect.height + this.offset;
            } else if (currentSide === 'top') {
                top = -contentRect.height - this.offset;
            } else if (currentSide === 'left') {
                left = -contentRect.width - this.offset;
                if (currentAlign === 'start') top = 0;
                else if (currentAlign === 'end') top = triggerRect.height - contentRect.height;
                else top = (triggerRect.height - contentRect.height) / 2;
            } else if (currentSide === 'right') {
                left = triggerRect.width + this.offset;
                if (currentAlign === 'start') top = 0;
                else if (currentAlign === 'end') top = triggerRect.height - contentRect.height;
                else top = (triggerRect.height - contentRect.height) / 2;
            }

            if (currentSide === 'bottom' || currentSide === 'top') {
                if (currentAlign === 'start') {
                    left = isRtl ? (triggerRect.width - contentRect.width) : 0;
                } else if (currentAlign === 'end') {
                    left = isRtl ? 0 : (triggerRect.width - contentRect.width);
                } else {
                    left = (triggerRect.width - contentRect.width) / 2;
                }
            }

            this.contentEl.style.top = `${top}px`;
            this.contentEl.style.left = `${left}px`;

            // ── Priority 3 & 4: Reduce max height & Enable scrolling ──
            const finalContentRect = this.contentEl.getBoundingClientRect();
            if (finalContentRect.bottom > viewportHeight) {
                const fitHeight = viewportHeight - finalContentRect.top - 16; // 16px safety margin
                if (fitHeight > 100) {
                    this.contentEl.style.maxHeight = `${fitHeight}px`;
                    this.contentEl.style.overflowY = 'auto';
                }
            } else if (finalContentRect.top < 0) {
                const fitHeight = finalContentRect.bottom - 16;
                if (fitHeight > 100) {
                    this.contentEl.style.top = `${top - finalContentRect.top + 16}px`;
                    this.contentEl.style.maxHeight = `${fitHeight}px`;
                    this.contentEl.style.overflowY = 'auto';
                }
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative inline-block text-left"
    {{ $attributes }}
>
    {{ $slot }}
</div>
