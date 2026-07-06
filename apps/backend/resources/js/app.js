import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Shared overlay state factory function to avoid code duplication
 * between modals, drawers, sheets, and popovers.
 */
const overlayBase = (config = {}) => ({
    open: false,
    lastActiveElement: null,
    id: config.id || '',
    persistent: config.persistent || false,
    busy: config.busy || false,
    initialFocus: config.initialFocus || '',

    init() {
        window.activeOverlays = window.activeOverlays || 0;
        window.overlayStack = window.overlayStack || [];
    },

    openOverlay() {
        this.open = true;
        this.lastActiveElement = document.activeElement;

        // Prevent overlayStack duplicates
        if (!window.overlayStack.includes(this.id)) {
            window.overlayStack.push(this.id);
        }

        // Lock body scroll with scrollbar padding compensation ONLY when opening the first overlay
        if ((window.activeOverlays || 0) === 0) {
            const originalPadding = window.getComputedStyle(document.body).paddingRight;
            const parsedPadding = parseFloat(originalPadding) || 0;
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;

            document.body.dataset.originalPadding = originalPadding;
            document.body.dataset.originalOverflow = document.documentElement.style.overflow;
            document.body.style.paddingRight = (parsedPadding + scrollbarWidth) + 'px';
            document.documentElement.style.overflow = 'hidden';
        }

        window.activeOverlays = (window.activeOverlays || 0) + 1;

        // Run focus loop fallback sequence on next tick
        this.$nextTick(() => {
            let target = null;
            if (this.initialFocus) {
                target = document.getElementById(this.initialFocus);
            }
            if (!target) {
                const panel = document.querySelector('[data-overlay-id="' + this.id + '"]');
                if (panel) {
                    const focusables = [...panel.querySelectorAll(
                        'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]'
                    )];
                    // 1. Autofocus elements
                    target = focusables.find(el => el.hasAttribute('autofocus'));
                    
                    // 2. First input/select/textarea
                    if (!target) {
                        target = focusables.find(el => ['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName));
                    }
                    
                    // 3. First generic focusable
                    if (!target) {
                        target = focusables[0];
                    }
                    
                    // 4. Panel container fallback
                    if (!target) {
                        target = panel;
                    }
                }
            }
            if (target) {
                if (target.hasAttribute('role') && !target.hasAttribute('tabindex')) {
                    target.setAttribute('tabindex', '-1');
                }
                target.focus();
            }
        });
    },

    closeOverlay() {
        if (this.busy) return;
        this.open = false;

        // Remove from stack
        window.overlayStack = (window.overlayStack || []).filter(x => x !== this.id);

        // Decrement counter defensively
        window.activeOverlays = Math.max(0, (window.activeOverlays || 1) - 1);

        // Restore original body styles ONLY when no active overlays remain
        if (window.activeOverlays === 0) {
            document.body.style.paddingRight = document.body.dataset.originalPadding || '';
            document.documentElement.style.overflow = document.body.dataset.originalOverflow || '';
            delete document.body.dataset.originalPadding;
            delete document.body.dataset.originalOverflow;
        }

        // Return focus safely
        this.$nextTick(() => {
            if (this.lastActiveElement && document.body.contains(this.lastActiveElement) && typeof this.lastActiveElement.focus === 'function') {
                this.lastActiveElement.focus();
            } else {
                document.body.focus();
            }
        });
    },

    destroy() {
        if (this.open) {
            this.closeOverlay();
        }
    },

    isTopmost() {
        return window.overlayStack.length > 0 && window.overlayStack.at(-1) === this.id;
    },

    getOverlayId(detail) {
        return typeof detail === 'string' ? detail : detail?.id || '';
    },

    focusTrap(event) {
        const panel = document.querySelector('[data-overlay-id="' + this.id + '"]');
        if (!panel) return;
        const focusables = [...panel.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]'
        )];
        if (focusables.length === 0) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            last.focus();
            event.preventDefault();
        } else if (!event.shiftKey && document.activeElement === last) {
            first.focus();
            event.preventDefault();
        }
    },
});

// Register Alpine named data components
Alpine.data('modal', (config = {}) => ({
    ...overlayBase(config),
    openModal() { this.openOverlay(); },
    closeModal() { this.closeOverlay(); }
}));

Alpine.data('drawer', (config = {}) => ({
    ...overlayBase(config),
    openDrawer() { this.openOverlay(); },
    closeDrawer() { this.closeOverlay(); }
}));

Alpine.start();

/**
 * Centralized global helpers mapping to generic open/close overlay events.
 * Accepts both string IDs or objects containing { id, type }.
 */
window.openModal = id => window.dispatchEvent(new CustomEvent('open-overlay', { detail: { id, type: 'modal' } }));
window.closeModal = id => window.dispatchEvent(new CustomEvent('close-overlay', { detail: { id, type: 'modal' } }));
window.openDrawer = id => window.dispatchEvent(new CustomEvent('open-overlay', { detail: { id, type: 'drawer' } }));
window.closeDrawer = id => window.dispatchEvent(new CustomEvent('close-overlay', { detail: { id, type: 'drawer' } }));

