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

Alpine.data('toastContainer', () => ({
    toasts: [],
    lastMessage: '',
    lastType: '',
    lastMessageTime: 0,
    maxVisible: 5,
    animationFrameId: null,
    lastTime: 0,

    init() {
        // Event listeners are bound on window inside index.blade.php
    },

    add(payload) {
        // Scoped duplicate suppression check
        const type = payload.type || 'info';
        if (payload.message === this.lastMessage && type === this.lastType && (Date.now() - this.lastMessageTime) < 500) {
            return;
        }
        this.lastMessage = payload.message;
        this.lastType = type;
        this.lastMessageTime = Date.now();

        // Unique ID generation with timestamp fallback
        const id = payload.id || (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function' 
            ? crypto.randomUUID() 
            : 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9));

        const duration = payload.duration !== undefined ? payload.duration : 5000;
        const item = {
            id,
            message: payload.message,
            type,
            duration,
            remaining: duration,
            progress: 100,
            paused: false,
            dismissing: false
        };

        // Maintain visibility limit checking non-dismissing toasts
        const activeToasts = this.toasts.filter(t => !t.dismissing);
        if (activeToasts.length >= this.maxVisible) {
            const oldest = activeToasts[activeToasts.length - 1];
            if (oldest) this.dismiss(oldest.id);
        }

        this.toasts.unshift(item);

        // Start animation frame progress loop if active
        if (duration > 0 && !this.animationFrameId) {
            this.lastTime = performance.now();
            this.startAnimationLoop();
        }
    },

    dismiss(id) {
        const toast = this.toasts.find(t => t.id === id);
        if (toast && !toast.dismissing) {
            toast.dismissing = true;
            setTimeout(() => {
                this.remove(id);
            }, 150); // duration-150 matching leave transition timing
        }
    },

    // Renamed from destroy(id) to avoid conflict with Alpine's reserved destroy() lifecycle hook
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);

        // Cancel frame loop if no timed, active toasts remain
        const activeTimed = this.toasts.filter(t => t.duration > 0 && !t.dismissing);
        if (activeTimed.length === 0 && this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
    },

    clear() {
        this.toasts.forEach(t => this.dismiss(t.id));
    },

    pause(id) {
        const toast = this.toasts.find(t => t.id === id);
        if (toast) toast.paused = true;
    },

    resume(id) {
        const toast = this.toasts.find(t => t.id === id);
        if (toast) toast.paused = false;
    },

    startAnimationLoop() {
        const loop = (now) => {
            const activeTimed = this.toasts.filter(t => t.duration > 0 && !t.dismissing);
            if (activeTimed.length === 0) {
                this.animationFrameId = null;
                return;
            }

            const delta = now - this.lastTime;
            this.lastTime = now;

            for (const toast of this.toasts) {
                if (toast.paused || toast.duration <= 0 || toast.dismissing) {
                    continue;
                }
                toast.remaining = Math.max(0, toast.remaining - delta);
                toast.progress = Math.max(0, Math.min(100, (toast.remaining / toast.duration) * 100));

                if (toast.remaining <= 0) {
                    this.dismiss(toast.id);
                }
            }

            this.animationFrameId = requestAnimationFrame(loop);
        };

        this.animationFrameId = requestAnimationFrame(loop);
    },

    destroy() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
    }
}));

Alpine.data('pageNavigator', () => ({
    loading: false,
    init() {
        // Intercept same-origin GET navigation link clicks inside this container for exit fade-out transitions
        this.$el.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;

            // Boundary & Exclusion checks
            if (e.defaultPrevented) return;
            if (this.loading) return; // Prevent double click navigation race conditions
            if (link.origin !== window.location.origin) return;
            if (link.hasAttribute('download')) return;
            if (link.getAttribute('target') === '_blank') return;
            if (link.getAttribute('rel') === 'external') return;

            // Skip same-page navigations and history-only absolute URL hash changes
            if (link.href.split('#')[0] === window.location.href.split('#')[0]) return;

            const href = link.getAttribute('href') || '';
            if (href === '' || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) {
                return;
            }
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            // If native View Transition API is supported, let browser handle cross-document transitions natively
            if ('startViewTransition' in window || 'startViewTransition' in document) {
                return;
            }

            // Otherwise, execute fade-out exit transition before redirecting
            e.preventDefault();
            this.loading = true;

            const main = this.$el.querySelector('.layout-main');
            if (main) {
                // Guarantee browser has completed layout before transition class is applied
                requestAnimationFrame(() => {
                    main.classList.add('ui-transition-fade-out');

                    let transitionTriggered = false;
                    const navigateAction = () => {
                        if (transitionTriggered) return;
                        transitionTriggered = true;
                        window.location.href = link.href;
                    };

                    // Synchronize transition end cleanly with JS redirects
                    const timeout = setTimeout(navigateAction, 250);
                    main.addEventListener('transitionend', () => {
                        clearTimeout(timeout);
                        navigateAction();
                    }, { once: true });
                });
            } else {
                // Fallback: If layout-main is absent, navigate immediately
                window.location.href = link.href;
            }
        });
    }
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

/**
 * Programmatic toast helper functions.
 * Accepts string messages or payload configuration objects.
 */
window.toast = payload => {
    const raw = typeof payload === 'string' ? { message: payload } : payload;
    const id = raw.id || (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function' 
        ? crypto.randomUUID() 
        : 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9));
    window.dispatchEvent(new CustomEvent('add-toast', { detail: { ...raw, id } }));
    return id;
};
window.toast.dismiss = id => window.dispatchEvent(new CustomEvent('dismiss-toast', { detail: id }));
window.toast.clear = () => window.dispatchEvent(new CustomEvent('clear-toasts'));

