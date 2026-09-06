export default function registerSalesOrderForm(Alpine) {
    Alpine.data('salesOrderForm', () => ({
        config: {}, customerId: '',
        items: [{ key: 0, sku_code: '', quantity: 1, customization_snapshot: '' }],
        nextItemKey: 1, skuOptions: [], initialSkus: [], skuQuery: '', skuStatus: '',
        searching: false, searchTimer: null, submitting: false, fieldErrors: {},
        discountAmount: '', shippingAmount: '', taxAmount: '', advanceAmount: '', advanceDueDate: '',

        init() {
            const configNode = this.$root.querySelector('[data-sales-order-config]');
            try { this.config = JSON.parse(configNode?.textContent || '{}'); } catch { this.config = {}; }
            this.initialSkus = Array.isArray(this.config.initialSkus) ? this.config.initialSkus : [];
            this.skuOptions = this.initialSkus;
            this.skuStatus = this.skuOptions.length === 1 ? '1 SKU available.' : `${this.skuOptions.length} SKUs available.`;
        },

        get errorMessages() { return Object.values(this.fieldErrors).flat().filter(Boolean); },
        hasError(field) { return Array.isArray(this.fieldErrors[field]) && this.fieldErrors[field].length > 0; },
        errorFor(field) { return this.hasError(field) ? this.fieldErrors[field][0] : ''; },

        addItem() {
            this.fieldErrors = {};
            const key = this.nextItemKey++;
            this.items.push({ key, sku_code: '', quantity: 1, customization_snapshot: '' });
            this.$nextTick(() => document.getElementById(`item-sku-${key}`)?.focus());
        },

        removeItem(index) {
            if (this.items.length <= 1) return;
            this.items.splice(index, 1);
            this.fieldErrors = {};
            this.$nextTick(() => {
                const nextIndex = Math.min(index, this.items.length - 1);
                document.getElementById(`item-sku-${this.items[nextIndex].key}`)?.focus();
            });
        },

        skuOptionsFor(selectedCode) {
            if (!selectedCode || this.skuOptions.some((sku) => sku.sku_code === selectedCode)) return this.skuOptions;
            const selected = this.initialSkus.find((sku) => sku.sku_code === selectedCode);
            return selected ? [selected, ...this.skuOptions] : this.skuOptions;
        },

        queueSkuSearch() {
            window.clearTimeout(this.searchTimer);
            this.searchTimer = window.setTimeout(() => this.fetchSkus(), 300);
        },

        async fetchSkus() {
            if (!this.config.skuSearchUrl) return;
            this.searching = true;
            this.skuStatus = 'Searching SKUs…';
            try {
                const url = new URL(this.config.skuSearchUrl, window.location.origin);
                url.searchParams.set('q', this.skuQuery.trim());
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('SKU search is temporarily unavailable.');
                const results = await response.json();
                this.skuOptions = Array.isArray(results) ? results : [];
                this.skuStatus = this.skuOptions.length === 1 ? '1 matching SKU.' : `${this.skuOptions.length} matching SKUs.`;
            } catch (error) {
                this.skuOptions = this.initialSkus;
                this.skuStatus = error instanceof Error ? error.message : 'SKU search is temporarily unavailable.';
            } finally { this.searching = false; }
        },

        validatePayload() {
            const errors = {}, parsedItems = [];
            if (!this.customerId) errors.customer_id = ['Choose a customer account.'];
            this.items.forEach((item, index) => {
                if (!String(item.sku_code || '').trim()) errors[`items.${index}.sku_code`] = [`Choose a SKU for line item ${index + 1}.`];
                const quantity = Number(item.quantity);
                if (!Number.isInteger(quantity) || quantity < 1) errors[`items.${index}.quantity`] = [`Enter a whole-number quantity of at least 1 for line item ${index + 1}.`];
                let customization = [];
                const text = String(item.customization_snapshot || '').trim();
                if (text !== '') {
                    try {
                        customization = JSON.parse(text);
                        if (customization === null || typeof customization !== 'object') throw new Error('Use a JSON object or array.');
                    } catch (error) {
                        errors[`items.${index}.customization_snapshot`] = [`Customization for line item ${index + 1} is invalid: ${error instanceof Error ? error.message : 'Invalid JSON.'}`];
                    }
                }
                parsedItems.push({ sku_code: String(item.sku_code || '').trim(), quantity, customization_snapshot: customization });
            });
            if (this.advanceAmount !== '' && this.advanceDueDate === '') errors['advance_payment.due_date'] = ['Choose a due date for the advance payment.'];
            if (this.advanceDueDate !== '' && this.advanceAmount === '') errors['advance_payment.amount_minor'] = ['Enter an amount for the advance payment.'];
            [
                ['discount_amount_minor', this.discountAmount, 'discount'],
                ['shipping_amount_minor', this.shippingAmount, 'shipping amount'],
                ['tax_amount_minor', this.taxAmount, 'tax amount'],
                ['advance_payment.amount_minor', this.advanceAmount, 'advance payment amount'],
            ].forEach(([field, value, label]) => {
                const amount = Number(value);
                if (value !== '' && (!Number.isInteger(amount) || amount < 0)) {
                    errors[field] = [`Enter the ${label} as a whole number of paise, zero or greater.`];
                }
            });
            this.fieldErrors = errors;
            return { valid: Object.keys(errors).length === 0, parsedItems };
        },

        optionalInteger(value) { return value === '' ? null : Number.parseInt(value, 10); },

        buildPayload(parsedItems) {
            const payload = { customer_id: Number.parseInt(this.customerId, 10), items: parsedItems };
            const amounts = {
                discount_amount_minor: this.optionalInteger(this.discountAmount),
                shipping_amount_minor: this.optionalInteger(this.shippingAmount),
                tax_amount_minor: this.optionalInteger(this.taxAmount),
            };
            Object.entries(amounts).forEach(([key, value]) => { if (value !== null) payload[key] = value; });
            if (this.advanceAmount !== '' || this.advanceDueDate !== '') {
                payload.advance_payment = { amount_minor: this.optionalInteger(this.advanceAmount), due_date: this.advanceDueDate };
            }
            return payload;
        },

        focusFirstError() {
            this.$nextTick(() => {
                const firstField = Object.keys(this.fieldErrors)[0];
                const parts = firstField ? firstField.split('.') : [];
                const fieldName = parts.length > 0 ? parts.shift() + parts.map((part) => `[${part}]`).join('') : null;
                const control = fieldName ? Array.from(this.$root.querySelectorAll('[name]')).find((element) => element.name === fieldName) : null;
                if (control instanceof HTMLElement) {
                    control.closest('details')?.setAttribute('open', '');
                    control.focus();
                } else this.$refs.errorSummary?.focus();
            });
        },

        async submitOrder() {
            if (this.submitting) return;
            const validation = this.validatePayload();
            if (!validation.valid) { this.focusFirstError(); return; }
            this.submitting = true;
            this.fieldErrors = {};
            try {
                const response = await fetch(this.config.storeUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.config.csrfToken },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.buildPayload(validation.parsedItems)),
                });
                const data = await response.json().catch(() => ({}));
                if (response.status === 201 && data.public_id) {
                    window.location.assign(`${this.config.orderDetailBaseUrl}/${encodeURIComponent(data.public_id)}/detail`);
                    return;
                }
                if (response.status === 422 && data.errors) {
                    this.fieldErrors = data.errors;
                    this.focusFirstError();
                    return;
                }
                throw new Error(data.message || 'Unexpected error creating the order.');
            } catch (error) {
                this.fieldErrors = { order: [error instanceof Error ? error.message : 'Unexpected error creating the order.'] };
                this.$nextTick(() => this.$refs.errorSummary?.focus());
            } finally { this.submitting = false; }
        },
    }));
}
