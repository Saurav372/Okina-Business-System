const menuTrigger = document.querySelector('[data-menu-trigger]');
const mobileMenu = document.querySelector('[data-mobile-menu]');
const menuClose = document.querySelector('[data-menu-close]');

const closeMenu = ({ returnFocus = true } = {}) => {
    if (!(menuTrigger instanceof HTMLButtonElement) || !(mobileMenu instanceof HTMLElement)) return;
    mobileMenu.hidden = true;
    menuTrigger.setAttribute('aria-expanded', 'false');
    menuTrigger.querySelector('.sf-sr-only').textContent = 'Open menu';
    if (returnFocus) menuTrigger.focus();
};

const openMenu = () => {
    if (!(menuTrigger instanceof HTMLButtonElement) || !(mobileMenu instanceof HTMLElement)) return;
    mobileMenu.hidden = false;
    menuTrigger.setAttribute('aria-expanded', 'true');
    menuTrigger.querySelector('.sf-sr-only').textContent = 'Close menu';
    const firstLink = mobileMenu.querySelector('a');
    if (firstLink instanceof HTMLElement) firstLink.focus();
};

menuTrigger?.addEventListener('click', () => {
    if (mobileMenu?.hidden) openMenu();
    else closeMenu();
});

menuClose?.addEventListener('click', () => closeMenu());

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && mobileMenu instanceof HTMLElement && !mobileMenu.hidden) closeMenu();
});

document.addEventListener('pointerdown', (event) => {
    if (!(mobileMenu instanceof HTMLElement) || mobileMenu.hidden) return;
    if (mobileMenu.contains(event.target) || menuTrigger?.contains(event.target)) return;
    closeMenu({ returnFocus: false });
});

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
