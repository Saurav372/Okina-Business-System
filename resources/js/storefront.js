// Storefront Drawer & Header Controller (UI-Skills compliant: keyboard trap, aria, micro-interactions)
const cartDrawer = document.querySelector('[data-cart-drawer]');
const cartTriggers = document.querySelectorAll('[data-cart-trigger]');
const drawerCloses = document.querySelectorAll('[data-drawer-close]');
let cartReturnFocus = null;
let cartPreviousOverflow = '';
const drawerBackground = new Map();

const openCartDrawer = () => {
    if (!(cartDrawer instanceof HTMLElement)) return;
    if (cartDrawer.classList.contains('sf-drawer-open')) return;
    cartReturnFocus = document.activeElement;
    cartPreviousOverflow = document.body.style.overflow;
    if (mobileMenu instanceof HTMLElement && !mobileMenu.hidden) closeMenu({ returnFocus: false });
    for (const sibling of document.body.children) {
        if (sibling instanceof HTMLElement && sibling !== cartDrawer && !sibling.contains(cartDrawer)) {
            drawerBackground.set(sibling, sibling.inert);
            sibling.inert = true;
        }
    }
    cartDrawer.classList.add('sf-drawer-open');
    cartDrawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    // Focus close button for accessible keyboard trap
    const closeBtn = cartDrawer.querySelector('[data-drawer-close]');
    if (closeBtn instanceof HTMLElement) closeBtn.focus();
};

const closeCartDrawer = () => {
    if (!(cartDrawer instanceof HTMLElement)) return;
    if (!cartDrawer.classList.contains('sf-drawer-open')) return;
    for (const [element, wasInert] of drawerBackground) element.inert = wasInert;
    drawerBackground.clear();
    if (cartReturnFocus instanceof HTMLElement && cartReturnFocus.isConnected) cartReturnFocus.focus();
    cartDrawer.classList.remove('sf-drawer-open');
    cartDrawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = cartPreviousOverflow;
};

cartDrawer?.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || !cartDrawer.classList.contains('sf-drawer-open')) return;
    const focusable = Array.from(cartDrawer.querySelectorAll('a[href], button, input, select, textarea, [tabindex]'))
        .filter((element) => element instanceof HTMLElement && !element.matches(':disabled') && element.tabIndex >= 0 && element.getClientRects().length > 0);
    const first = focusable[0];
    const last = focusable.at(-1);
    if (!first) {
        event.preventDefault();
        return;
    }
    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
});

cartTriggers.forEach((trigger) => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        openCartDrawer();
    });
});

drawerCloses.forEach((btn) => {
    btn.addEventListener('click', (e) => {
        if (!(btn instanceof HTMLAnchorElement)) e.preventDefault();
        closeCartDrawer();
    });
});

// Click outside drawer panel closes it
cartDrawer?.addEventListener('pointerdown', (e) => {
    if (e.target === cartDrawer) {
        closeCartDrawer();
    }
});

// Quantity Stepper Handler in Drawer
document.querySelectorAll('.sf-drawer-qty-stepper').forEach((form) => {
    const input = form.querySelector('.sf-qty-input');
    form.querySelectorAll('.sf-qty-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!(input instanceof HTMLInputElement)) return;
            const delta = parseInt(btn.dataset.step || '0', 10);
            const current = parseInt(input.value || '1', 10);
            const next = Math.max(1, current + delta);
            if (next !== current) {
                input.value = String(next);
                form.submit();
            }
        });
    });
});

// Mobile Navigation Menu Controller
const menuTrigger = document.querySelector('[data-menu-trigger]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
const menuClose = document.querySelector('[data-menu-close]');

const closeMenu = ({ returnFocus = true } = {}) => {
    if (!(menuTrigger instanceof HTMLButtonElement) || !(mobileMenu instanceof HTMLElement)) return;
    mobileMenu.hidden = true;
    menuTrigger.setAttribute('aria-expanded', 'false');
    if (returnFocus) menuTrigger.focus();
};

const openMenu = () => {
    if (!(menuTrigger instanceof HTMLButtonElement) || !(mobileMenu instanceof HTMLElement)) return;
    mobileMenu.hidden = false;
    menuTrigger.setAttribute('aria-expanded', 'true');
    const firstLink = mobileMenu.querySelector('a');
    if (firstLink instanceof HTMLElement) firstLink.focus();
};

menuTrigger?.addEventListener('click', () => {
    if (mobileMenu?.hidden) openMenu();
    else closeMenu();
});

menuClose?.addEventListener('click', () => closeMenu());
mobileMenu?.addEventListener('click', (event) => {
    if (event.target instanceof Element && event.target.closest('a[href]')) {
        closeMenu({ returnFocus: false });
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (cartDrawer instanceof HTMLElement && cartDrawer.classList.contains('sf-drawer-open')) {
            closeCartDrawer();
        }
        if (mobileMenu instanceof HTMLElement && !mobileMenu.hidden) {
            closeMenu();
        }
    }
});

document.addEventListener('pointerdown', (event) => {
    if (!(mobileMenu instanceof HTMLElement) || mobileMenu.hidden) return;
    if (mobileMenu.contains(event.target) || menuTrigger?.contains(event.target)) return;
    closeMenu({ returnFocus: false });
});

// Catalog Filtering & Sorting
const productGrid = document.querySelector('[data-product-grid]');
const productFilter = document.querySelector('[data-product-filter]');
const productSort = document.querySelector('[data-product-sort]');
const resultCount = document.querySelector('[data-result-count]');
const filterEmpty = document.querySelector('[data-filter-empty]');
const filterStatus = document.querySelector('[data-filter-status]');

const updateProductGrid = () => {
    if (!(productGrid instanceof HTMLElement)) return;

    const query = productFilter instanceof HTMLInputElement ? productFilter.value.trim().toLocaleLowerCase() : '';
    const mode = productSort instanceof HTMLSelectElement ? productSort.value : 'featured';
    const cards = Array.from(productGrid.querySelectorAll('[data-product-card]'));

    for (const card of cards) {
        const searchText = `${card.dataset.name ?? ''} ${card.dataset.category ?? ''}`;
        card.hidden = query !== '' && !searchText.includes(query);
    }

    const visibleCards = cards.filter((card) => !card.hidden);
    const sorter = (left, right) => {
        if (mode === 'price-low') return Number(left.dataset.price) - Number(right.dataset.price);
        if (mode === 'price-high') return Number(right.dataset.price) - Number(left.dataset.price);
        if (mode === 'name') return String(left.dataset.name).localeCompare(String(right.dataset.name));
        return Number(left.dataset.order) - Number(right.dataset.order);
    };

    visibleCards.sort(sorter).forEach((card) => productGrid.append(card));
    if (resultCount instanceof HTMLElement) resultCount.textContent = String(visibleCards.length);
    if (filterEmpty instanceof HTMLElement) filterEmpty.hidden = visibleCards.length !== 0;
    if (filterStatus instanceof HTMLElement) {
        filterStatus.textContent = `${visibleCards.length} ${visibleCards.length === 1 ? 'style' : 'styles'} shown.`;
    }
};

productFilter?.addEventListener('input', updateProductGrid);
productSort?.addEventListener('change', updateProductGrid);

// Product Customizer Option Matrix Handler
const customizerRoot = document.querySelector('[data-product-customizer]');
const customizerForm = document.querySelector('[data-customizer-form]');
const customizerDataNode = document.querySelector('#product-customizer-data');

if (customizerRoot instanceof HTMLElement && customizerForm instanceof HTMLFormElement && customizerDataNode instanceof HTMLScriptElement) {
    const data = JSON.parse(customizerDataNode.textContent || '{}');
    const skus = Array.isArray(data.skus) ? data.skus : [];
    const compatibility = data.compatibility && typeof data.compatibility === 'object' ? data.compatibility : {};
    const skuInput = customizerForm.querySelector('[data-sku-code]');
    const price = customizerRoot.querySelector('[data-customizer-price]');
    const status = customizerRoot.querySelector('[data-customizer-status]');
    const addButton = customizerRoot.querySelector('[data-add-button]');
    const addLabel = customizerRoot.querySelector('[data-add-label]');
    const methodSelect = customizerRoot.querySelector('[data-print-method]');
    const artworkMark = customizerRoot.querySelector('[data-artwork-mark]');

    const selectedOptions = () => Object.fromEntries(
        Array.from(customizerForm.querySelectorAll('input[name^="selected_options["]:checked'))
            .map((input) => [input.name.slice(17, -1), input.value]),
    );

    const variantKey = () => {
        const options = selectedOptions();
        const keys = Object.keys(options).sort();
        return keys.length === 0 ? 'default' : keys.map((key) => `${key}:${options[key]}`).join('|');
    };

    const updateMethodAvailability = () => {
        if (!(methodSelect instanceof HTMLSelectElement)) return;
        const position = customizerForm.querySelector('input[name="print_position"]:checked')?.value;
        const allowed = Array.isArray(compatibility[position]) ? compatibility[position] : [];
        let selectedAllowed = false;

        for (const option of methodSelect.options) {
            option.disabled = !allowed.includes(option.value);
            if (option.selected && !option.disabled) selectedAllowed = true;
        }

        if (!selectedAllowed) {
            const firstAllowed = Array.from(methodSelect.options).find((option) => !option.disabled);
            if (firstAllowed) methodSelect.value = firstAllowed.value;
        }

        if (artworkMark instanceof HTMLElement) artworkMark.dataset.position = position ?? '';
    };

    const updateSku = () => {
        updateMethodAvailability();
        const sku = skus.find((item) => item.variant_key === variantKey());
        const available = Boolean(sku?.availability?.available_for_checkout);
        if (skuInput instanceof HTMLInputElement) skuInput.value = sku?.sku_code ?? '';
        if (price instanceof HTMLElement && sku?.display_price) price.textContent = sku.display_price;
        if (addButton instanceof HTMLButtonElement) addButton.disabled = !available;
        if (addLabel instanceof HTMLElement) addLabel.textContent = available ? 'Add to bag' : (sku?.availability?.requires_quote ? 'Quote required' : 'Combination unavailable');
        if (status instanceof HTMLElement) status.textContent = available
            ? 'Your current combination is ready to add.'
            : (sku?.availability?.requires_quote ? 'This combination needs a tailored quote.' : 'Choose another option combination.');
    };

    customizerForm.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLSelectElement) updateSku();
    });

    const quantityInput = customizerForm.querySelector('[data-quantity-input]');
    customizerForm.querySelectorAll('[data-quantity]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!(quantityInput instanceof HTMLInputElement)) return;
            quantityInput.value = button.dataset.quantity ?? '1';
            quantityInput.focus();
        });
    });

    customizerForm.addEventListener('submit', () => {
        if (!(addButton instanceof HTMLButtonElement) || addButton.disabled) return;
        addButton.setAttribute('aria-busy', 'true');
        addButton.disabled = true;
        if (addLabel instanceof HTMLElement) addLabel.textContent = 'Adding…';
    });

    customizerRoot.querySelectorAll('[data-product-thumbnail]').forEach((button) => {
        button.addEventListener('click', () => {
            const stageImage = customizerRoot.querySelector('[data-product-stage] > img');
            if (!(stageImage instanceof HTMLImageElement)) return;
            stageImage.src = button.dataset.src ?? stageImage.src;
            stageImage.alt = button.dataset.alt ?? stageImage.alt;
            customizerRoot.querySelectorAll('[data-product-thumbnail]').forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
        });
    });

    updateSku();
}

const errorSummary = document.querySelector('[data-error-summary]');
if (errorSummary instanceof HTMLElement) errorSummary.focus();

if (document.body.dataset.page === 'mockup') {
    import('./storefront/mockup-studio');
}
