export default function registerSalesOrderForm(Alpine) {
    Alpine.data('salesOrderForm', () => ({
        config: {},
        customerId: '',
        customers: [],
        items: [{
            key: 0,
            sku_code: '',
            quantity: 1,
            has_customization: false,
            print_methods: [],
            production_notes: '',
            mockup: null,
            mockup_preview_url: null,
            mockup_local_preview: null,
            uploading_mockup: false,
            dragover: false,
        }],
        nextItemKey: 1,
        availablePrintMethods: [
            { id: 'dtf', label: 'DTF' },
            { id: 'embroidery', label: 'Embroidery' },
            { id: 'screen_print', label: 'Screen Printing' },
            { id: 'sublimation', label: 'Sublimation' },
            { id: 'vinyl', label: 'Vinyl Heat Transfer' },
            { id: 'uv_print', label: 'UV Printing' },
            { id: 'other', label: 'Other' },
        ],
        lightboxImage: null,
        lightboxTitle: '',
        skuOptions: [],
        initialSkus: [],
        skuQuery: '',
        skuStatus: '',
        searching: false,
        searchTimer: null,
        submitting: false,
        fieldErrors: {},
        discountAmount: '',
        shippingAmount: '',
        taxAmount: '',
        advanceAmount: '',
        advanceDueDate: '',

        // Quick New Customer State
        newCustomer: {
            name: '',
            brand_name: '',
            phones: [''],
            same_as_whatsapp: true,
            whatsapp_phone: '',
            lead_source: 'instagram',
            email: '',
            same_as_billing: true,
            shipping_address: {
                line_1: '',
                line_2: '',
                city: '',
                state: '',
                postal_code: '',
            },
            billing_address: {
                line_1: '',
                line_2: '',
                city: '',
                state: '',
                postal_code: '',
                gstin: '',
            },
        },
        newCustomerErrors: {},
        savingCustomer: false,

        init() {
            const configNode = this.$root.querySelector('[data-sales-order-config]');
            try { this.config = JSON.parse(configNode?.textContent || '{}'); } catch { this.config = {}; }
            this.initialSkus = Array.isArray(this.config.initialSkus) ? this.config.initialSkus : [];
            this.skuOptions = this.initialSkus;
            this.skuStatus = this.skuOptions.length === 1 ? '1 SKU available.' : `${this.skuOptions.length} SKUs available.`;
            this.customers = Array.isArray(this.config.initialCustomers) ? this.config.initialCustomers : [];
        },

        get errorMessages() { return Object.values(this.fieldErrors).flat().filter(Boolean); },
        hasError(field) { return Array.isArray(this.fieldErrors[field]) && this.fieldErrors[field].length > 0; },
        errorFor(field) { return this.hasError(field) ? this.fieldErrors[field][0] : ''; },

        // Quick Customer Helpers
        addPhoneField() {
            this.newCustomer.phones.push('');
        },
        removePhoneField(index) {
            if (this.newCustomer.phones.length > 1) {
                this.newCustomer.phones.splice(index, 1);
            }
        },
        openNewCustomerModal() {
            this.newCustomerErrors = {};
            this.$dispatch('open-overlay', 'quick-customer-modal');
        },
        closeNewCustomerModal() {
            this.$dispatch('close-overlay', 'quick-customer-modal');
        },
        async saveNewCustomer() {
            if (this.savingCustomer) return;
            this.newCustomerErrors = {};

            const errors = {};
            if (!this.newCustomer.name.trim()) {
                errors.name = ['Full name is required.'];
            }
            if (!this.newCustomer.brand_name.trim()) {
                errors.brand_name = ['Brand name is required.'];
            }
            const validPhones = this.newCustomer.phones.map(p => p.trim()).filter(Boolean);
            if (validPhones.length === 0) {
                errors.phones = ['At least one mobile number is required.'];
            }

            if (Object.keys(errors).length > 0) {
                this.newCustomerErrors = errors;
                return;
            }

            this.savingCustomer = true;
            try {
                const response = await fetch(this.config.quickCustomerStoreUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        ...this.newCustomer,
                        phones: validPhones,
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (response.status === 201 && data.customer) {
                    this.customers.unshift(data.customer);
                    this.customerId = String(data.customer.id);
                    this.closeNewCustomerModal();

                    // Reset form
                    this.newCustomer = {
                        name: '',
                        brand_name: '',
                        phones: [''],
                        same_as_whatsapp: true,
                        whatsapp_phone: '',
                        lead_source: 'instagram',
                        email: '',
                        same_as_billing: true,
                        shipping_address: { line_1: '', line_2: '', city: '', state: '', postal_code: '' },
                        billing_address: { line_1: '', line_2: '', city: '', state: '', postal_code: '', gstin: '' },
                    };
                    return;
                }

                if (response.status === 422 && data.errors) {
                    this.newCustomerErrors = data.errors;
                    return;
                }

                throw new Error(data.message || 'Failed to save customer.');
            } catch (err) {
                this.newCustomerErrors = { general: [err instanceof Error ? err.message : 'Error creating customer.'] };
            } finally {
                this.savingCustomer = false;
            }
        },

        newItemObject(key) {
            return {
                key,
                sku_code: '',
                quantity: 1,
                has_customization: false,
                print_methods: [],
                production_notes: '',
                mockup: null,
                mockup_preview_url: null,
                mockup_local_preview: null,
                uploading_mockup: false,
                dragover: false,
            };
        },

        addItem() {
            this.fieldErrors = {};
            const key = this.nextItemKey++;
            this.items.push(this.newItemObject(key));
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

        // Mockup & Print Method Actions
        openLightbox(url, title) {
            if (!url) return;
            this.lightboxImage = url;
            this.lightboxTitle = title || 'Mockup Preview';
        },
        closeLightbox() {
            this.lightboxImage = null;
            this.lightboxTitle = '';
        },
        formatFileSize(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },
        isAnyMockupUploading() {
            return this.items.some((item) => item.uploading_mockup);
        },
        togglePrintMethod(item, methodId) {
            const idx = item.print_methods.indexOf(methodId);
            if (idx > -1) {
                item.print_methods.splice(idx, 1);
            } else {
                item.print_methods.push(methodId);
            }
            delete this.fieldErrors[`items.${this.items.indexOf(item)}.print_methods`];
        },
        handleMockupFileInput(item, event) {
            const file = event.target?.files?.[0];
            if (file) {
                this.uploadMockupFile(item, file);
            }
            if (event.target) event.target.value = '';
        },
        handleMockupDrop(item, event) {
            const file = event.dataTransfer?.files?.[0];
            if (file && file.type.startsWith('image/')) {
                this.uploadMockupFile(item, file);
            }
        },
        handleMockupPaste(item, event) {
            const clipboardItems = event.clipboardData?.items;
            if (!clipboardItems) return;
            for (let i = 0; i < clipboardItems.length; i++) {
                const cItem = clipboardItems[i];
                if (cItem.kind === 'file' && cItem.type.startsWith('image/')) {
                    const file = cItem.getAsFile();
                    if (file) {
                        event.preventDefault();
                        this.uploadMockupFile(item, file);
                        break;
                    }
                }
            }
        },
        async uploadMockupFile(item, file) {
            if (!file) return;

            const idx = this.items.indexOf(item);

            if (file.size > 10 * 1024 * 1024) {
                this.fieldErrors[`items.${idx}.mockup`] = ['Mockup image must not exceed 10 MB.'];
                return;
            }

            item.mockup_local_preview = URL.createObjectURL(file);
            item.uploading_mockup = true;
            delete this.fieldErrors[`items.${idx}.mockup`];

            const formData = new FormData();
            formData.append('mockup_file', file);

            try {
                const response = await fetch(this.config.mockupUploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': this.config.csrfToken,
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const data = await response.json().catch(() => ({}));
                if (response.status === 201 && data.success) {
                    item.mockup = {
                        stored_file_id: data.stored_file_id,
                        original_filename: data.filename,
                        size_bytes: data.size_bytes,
                    };
                    item.mockup_preview_url = data.preview_url;
                } else {
                    throw new Error(data.message || (data.errors?.mockup_file?.[0]) || 'Failed to upload mockup.');
                }
            } catch (err) {
                item.mockup = null;
                item.mockup_local_preview = null;
                this.fieldErrors[`items.${idx}.mockup`] = [
                    err instanceof Error ? err.message : 'Error uploading mockup.'
                ];
            } finally {
                item.uploading_mockup = false;
            }
        },
        removeMockup(item) {
            item.mockup = null;
            item.mockup_preview_url = null;
            item.mockup_local_preview = null;
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
            const query = (this.skuQuery || '').trim();
            if (!query) {
                this.skuOptions = this.initialSkus;
                this.skuStatus = this.skuOptions.length === 1 ? '1 SKU available.' : `${this.skuOptions.length} SKUs available.`;
                this.searching = false;
                return;
            }

            this.searching = true;
            this.skuStatus = 'Searching SKUs…';
            try {
                const url = new URL(this.config.skuSearchUrl, window.location.origin);
                url.searchParams.set('q', query);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('SKU search is temporarily unavailable.');
                const results = await response.json();
                this.skuOptions = Array.isArray(results) ? results : [];
                this.skuStatus = this.skuOptions.length === 1 ? '1 matching SKU found.' : `${this.skuOptions.length} matching SKUs found.`;
            } catch (error) {
                this.skuOptions = this.initialSkus;
                this.skuStatus = error instanceof Error ? error.message : 'SKU search is temporarily unavailable.';
            } finally { this.searching = false; }
        },

        addSkuToOrder(sku) {
            if (!sku || !sku.sku_code) return;

            const emptyItem = this.items.find((item) => !item.sku_code || String(item.sku_code).trim() === '');
            if (emptyItem) {
                emptyItem.sku_code = sku.sku_code;
                this.$nextTick(() => {
                    document.getElementById(`item-sku-${emptyItem.key}`)?.focus();
                });
            } else {
                const key = this.nextItemKey++;
                const newItem = this.newItemObject(key);
                newItem.sku_code = sku.sku_code;
                this.items.push(newItem);
                this.$nextTick(() => {
                    document.getElementById(`item-sku-${key}`)?.focus();
                });
            }

            this.skuStatus = `Added ${sku.sku_code} to line items.`;
        },

        clearSkuSearch() {
            this.skuQuery = '';
            this.skuOptions = this.initialSkus;
            this.skuStatus = this.skuOptions.length === 1 ? '1 SKU available.' : `${this.skuOptions.length} SKUs available.`;
        },

        validatePayload() {
            const errors = {}, parsedItems = [];
            if (!this.customerId) errors.customer_id = ['Choose a customer account.'];
            this.items.forEach((item, index) => {
                if (!String(item.sku_code || '').trim()) errors[`items.${index}.sku_code`] = [`Choose a SKU for line item ${index + 1}.`];
                const quantity = Number(item.quantity);
                if (!Number.isInteger(quantity) || quantity < 1) errors[`items.${index}.quantity`] = [`Enter a whole-number quantity of at least 1 for line item ${index + 1}.`];

                let customizationSnapshot = null;
                if (item.has_customization) {
                    if (!item.mockup?.stored_file_id) {
                        errors[`items.${index}.mockup`] = [`Upload or paste a mockup image for line item ${index + 1}.`];
                    }
                    if (!item.print_methods || item.print_methods.length === 0) {
                        errors[`items.${index}.print_methods`] = [`Select at least one print method for line item ${index + 1}.`];
                    }

                    customizationSnapshot = {
                        print_methods: item.print_methods || [],
                        production_notes: (item.production_notes || '').trim(),
                        mockup: item.mockup ? {
                            stored_file_id: item.mockup.stored_file_id,
                            original_filename: item.mockup.original_filename,
                        } : null,
                    };
                }

                parsedItems.push({
                    sku_code: String(item.sku_code || '').trim(),
                    quantity,
                    customization_snapshot: customizationSnapshot,
                });
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
