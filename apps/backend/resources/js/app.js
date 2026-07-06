import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Modal component - registered as a named Alpine component
 * to avoid inline x-data attribute escaping issues.
 */
Alpine.data('modal', (config = {}) => ({
    open: false,
    lastActiveElement: null,
    id: config.id || '',
    persistent: config.persistent || false,
    busy: config.busy || false,
    initialFocus: config.initialFocus || '',

    init() {
        // Ensure global stacks exist
        window.activeModals = window.activeModals || 0;
        window.modalStack = window.modalStack || [];
    },

    openModal() {
        this.open = true;
        this.lastActiveElement = document.activeElement;

        // Increment lock counter + lock body scroll
        window.activeModals = (window.activeModals || 0) + 1;
        document.documentElement.style.overflow = 'hidden';

        // Push to stack
        if (!window.modalStack.includes(this.id)) {
            window.modalStack.push(this.id);
        }

        // Focus the target element on next tick
        this.$nextTick(() => {
            let target = null;
            if (this.initialFocus) {
                target = document.getElementById(this.initialFocus);
            }
            if (!target) {
                // Find first focusable in the teleported modal panel
                const panel = document.querySelector('[data-modal-id="' + this.id + '"]');
                if (panel) {
                    const focusables = [...panel.querySelectorAll(
                        'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]'
                    )];
                    target = focusables[0] || null;
                }
            }
            if (target) target.focus();
        });
    },

    closeModal() {
        if (this.busy) return;
        this.open = false;

        // Decrement counter + unlock body scroll when no modals remain
        window.activeModals = Math.max(0, (window.activeModals || 1) - 1);
        if (window.activeModals === 0) {
            document.documentElement.style.overflow = '';
        }

        // Remove this modal from stack by ID (defensive)
        window.modalStack = (window.modalStack || []).filter(x => x !== this.id);

        // Return focus to the element that triggered this modal
        this.$nextTick(() => {
            if (this.lastActiveElement && typeof this.lastActiveElement.focus === 'function') {
                this.lastActiveElement.focus();
            }
        });
    },

    isTopmost() {
        const stack = window.modalStack || [];
        return stack.length === 0 || stack[stack.length - 1] === this.id;
    },

    focusTrap(event) {
        const panel = document.querySelector('[data-modal-id="' + this.id + '"]');
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
}));

Alpine.start();

/**
 * Global helpers so templates can call window.openModal('id') directly
 * as a fallback alongside Alpine's $dispatch event system.
 */
window.openModal = function (id) {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: id }));
};
window.closeModal = function (id) {
    window.dispatchEvent(new CustomEvent('close-modal', { detail: id }));
};
