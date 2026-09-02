<x-layouts.admin title="Create Sales Order">
    <x-slot:header>
        <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-[color:var(--color-border)] bg-white px-4 py-2 text-xs font-semibold text-[color:var(--color-text-body)] transition-colors hover:border-[color:var(--color-border-hover)] hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
            <x-icons.lucide name="lucide-arrow-left" class="h-4 w-4" />
            Back to orders
        </a>
    </x-slot:header>

    @php
        $salesOrderConfig = [
            'storeUrl' => route('admin.sales_orders.store'),
            'skuSearchUrl' => route('admin.skus.search'),
            'orderDetailBaseUrl' => url('/admin/orders'),
            'csrfToken' => csrf_token(),
            'initialSkus' => $skus->map(fn ($sku) => [
                'sku_code' => $sku->sku_code,
                'label' => $sku->sku_code.($sku->product ? ' · '.$sku->product->name : ''),
            ])->values()->all(),
        ];
    @endphp

    <div class="mx-auto max-w-6xl" x-data="salesOrderForm">
        <script type="application/json" data-sales-order-config>{!! json_encode($salesOrderConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

        <div
            x-cloak
            x-show="errorMessages.length > 0"
            x-ref="errorSummary"
            role="alert"
            tabindex="-1"
            class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-4 text-rose-900 shadow-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
        >
            <div class="flex items-start gap-3">
                <span class="mt-0.5 rounded-lg bg-rose-100 p-2 text-rose-700" aria-hidden="true">
                    <x-icons.lucide name="lucide-circle-alert" class="h-4 w-4" />
                </span>
                <div>
                    <h2 class="text-sm font-bold">The order could not be created</h2>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-rose-800">
                        <template x-for="(message, errorIndex) in errorMessages" :key="errorIndex">
                            <li x-text="message"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <form class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]" @submit.prevent="submitOrder" novalidate>
            <div class="space-y-5">
                <section class="rounded-2xl border border-[color:var(--color-border)] bg-white p-5 shadow-xs sm:p-6" aria-labelledby="customer-section-title">
                    <div class="mb-5 flex items-start gap-3 border-b border-neutral-100 pb-4">
                        <span class="rounded-xl bg-[color:var(--color-brand-50)] p-2.5 text-[color:var(--color-brand-700)]" aria-hidden="true">
                            <x-icons.lucide name="lucide-user-round" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="customer-section-title" class="text-sm font-bold text-[color:var(--color-text-heading)]">Customer</h2>
                            <p class="mt-0.5 text-xs text-[color:var(--color-text-caption)]">Choose who this sales order belongs to.</p>
                        </div>
                    </div>

                    <label for="customer_id" class="mb-1.5 block text-xs font-semibold text-[color:var(--color-text-body)]">Customer account <span class="text-rose-600" aria-hidden="true">*</span></label>
                    <select
                        id="customer_id"
                        name="customer_id"
                        x-model="customerId"
                        :aria-invalid="hasError('customer_id') ? 'true' : null"
                        :aria-describedby="hasError('customer_id') ? 'customer-id-error' : 'customer-id-help'"
                        required
                        class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 transition-colors focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                    >
                        <option value="">Select a customer</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->display_name ?: $customer->name }}</option>
                        @endforeach
                    </select>
                    <p id="customer-id-help" class="mt-1.5 text-[11px] text-[color:var(--color-text-caption)]">The customer’s saved details will be attached to the order.</p>
                    <p id="customer-id-error" x-cloak x-show="hasError('customer_id')" x-text="errorFor('customer_id')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                </section>

                <section class="rounded-2xl border border-[color:var(--color-border)] bg-white shadow-xs" aria-labelledby="items-section-title">
                    <div class="flex flex-col gap-4 border-b border-neutral-100 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div class="flex items-start gap-3">
                            <span class="rounded-xl bg-[color:var(--color-brand-50)] p-2.5 text-[color:var(--color-brand-700)]" aria-hidden="true">
                                <x-icons.lucide name="lucide-package-plus" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 id="items-section-title" class="text-sm font-bold text-[color:var(--color-text-heading)]">Order items</h2>
                                <p class="mt-0.5 text-xs text-[color:var(--color-text-caption)]"><span x-text="items.length"></span> <span x-text="items.length === 1 ? 'line item' : 'line items'"></span> in this order.</p>
                            </div>
                        </div>
                        <button type="button" @click="addItem" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-neutral-900 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2">
                            <x-icons.lucide name="lucide-plus" class="h-4 w-4" />
                            Add item
                        </button>
                    </div>

                    <div class="border-b border-neutral-100 bg-neutral-50/70 p-5 sm:px-6">
                        <label for="sku-filter" class="mb-1.5 block text-xs font-semibold text-[color:var(--color-text-body)]">Find a product or SKU</label>
                        <div class="relative">
                            <x-icons.lucide name="lucide-search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                            <input
                                id="sku-filter"
                                type="search"
                                x-model="skuQuery"
                                @input="queueSkuSearch"
                                aria-describedby="sku-search-status"
                                placeholder="Search by SKU code or product name"
                                class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white py-2.5 pl-10 pr-3.5 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >
                            <span x-cloak x-show="searching" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400" aria-hidden="true">
                                <x-icons.lucide name="lucide-loader-circle" class="h-4 w-4 animate-spin" />
                            </span>
                        </div>
                        <p id="sku-search-status" role="status" aria-live="polite" class="mt-1.5 min-h-4 text-[11px] text-[color:var(--color-text-caption)]" x-text="skuStatus"></p>
                    </div>

                    <div class="space-y-4 p-5 sm:p-6">
                        <template x-for="(item, index) in items" :key="item.key">
                            <article class="rounded-2xl border border-neutral-200 bg-neutral-50/50 p-4 sm:p-5" :aria-labelledby="'line-item-title-' + item.key">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <h3 class="flex items-center gap-2 text-xs font-bold text-neutral-800" :id="'line-item-title-' + item.key">
                                        <span class="inline-grid h-7 w-7 place-items-center rounded-lg bg-neutral-900 text-[10px] font-bold text-white" x-text="index + 1"></span>
                                        Line item
                                    </h3>
                                    <button
                                        x-cloak
                                        x-show="items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="inline-flex min-h-10 items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
                                        :aria-label="'Remove line item ' + (index + 1)"
                                    >
                                        <x-icons.lucide name="lucide-trash-2" class="h-4 w-4" />
                                        Remove
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-[minmax(0,1fr)_8rem]">
                                    <div>
                                        <label :for="'item-sku-' + item.key" class="mb-1.5 block text-xs font-semibold text-neutral-700">SKU <span class="text-rose-600" aria-hidden="true">*</span></label>
                                        <select
                                            :id="'item-sku-' + item.key"
                                            :name="'items[' + index + '][sku_code]'"
                                            x-model="item.sku_code"
                                            :aria-invalid="hasError('items.' + index + '.sku_code') ? 'true' : null"
                                            :aria-describedby="hasError('items.' + index + '.sku_code') ? 'item-sku-error-' + item.key : null"
                                            required
                                            class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 transition-colors focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        >
                                            <option value="">Select a SKU</option>
                                            <template x-for="sku in skuOptionsFor(item.sku_code)" :key="sku.sku_code">
                                                <option :value="sku.sku_code" x-text="sku.label"></option>
                                            </template>
                                        </select>
                                        <p x-cloak x-show="hasError('items.' + index + '.sku_code')" :id="'item-sku-error-' + item.key" x-text="errorFor('items.' + index + '.sku_code')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                                    </div>

                                    <div>
                                        <label :for="'item-quantity-' + item.key" class="mb-1.5 block text-xs font-semibold text-neutral-700">Quantity <span class="text-rose-600" aria-hidden="true">*</span></label>
                                        <input
                                            :id="'item-quantity-' + item.key"
                                            :name="'items[' + index + '][quantity]'"
                                            type="number"
                                            min="1"
                                            step="1"
                                            inputmode="numeric"
                                            x-model="item.quantity"
                                            :aria-invalid="hasError('items.' + index + '.quantity') ? 'true' : null"
                                            :aria-describedby="hasError('items.' + index + '.quantity') ? 'item-quantity-error-' + item.key : null"
                                            required
                                            class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 transition-colors focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        >
                                        <p x-cloak x-show="hasError('items.' + index + '.quantity')" :id="'item-quantity-error-' + item.key" x-text="errorFor('items.' + index + '.quantity')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                                    </div>
                                </div>

                                <details class="mt-4 border-t border-neutral-200 pt-3">
                                    <summary class="min-h-10 cursor-pointer rounded-lg py-2 text-xs font-semibold text-neutral-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">Customization data <span class="font-normal text-neutral-400">(optional JSON)</span></summary>
                                    <div class="pt-2">
                                        <label :for="'item-customization-' + item.key" class="mb-1.5 block text-xs font-semibold text-neutral-700">Customization snapshot</label>
                                        <textarea
                                            :id="'item-customization-' + item.key"
                                            :name="'items[' + index + '][customization_snapshot]'"
                                            rows="4"
                                            spellcheck="false"
                                            x-model="item.customization_snapshot"
                                            :aria-invalid="hasError('items.' + index + '.customization_snapshot') ? 'true' : null"
                                            :aria-describedby="'item-customization-help-' + item.key + (hasError('items.' + index + '.customization_snapshot') ? ' item-customization-error-' + item.key : '')"
                                            placeholder='Example: {"print_position":"front"}'
                                            class="w-full resize-y rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 font-mono text-xs text-neutral-900 placeholder-neutral-400 transition-colors focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                        ></textarea>
                                        <p :id="'item-customization-help-' + item.key" class="mt-1.5 text-[11px] text-neutral-500">Leave blank when the item has no customization.</p>
                                        <p x-cloak x-show="hasError('items.' + index + '.customization_snapshot')" :id="'item-customization-error-' + item.key" x-text="errorFor('items.' + index + '.customization_snapshot')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                                    </div>
                                </details>
                            </article>
                        </template>
                    </div>
                </section>

                <section class="rounded-2xl border border-[color:var(--color-border)] bg-white p-5 shadow-xs sm:p-6" aria-labelledby="charges-section-title">
                    <div class="mb-5 flex items-start gap-3 border-b border-neutral-100 pb-4">
                        <span class="rounded-xl bg-neutral-100 p-2.5 text-neutral-700" aria-hidden="true">
                            <x-icons.lucide name="lucide-receipt-indian-rupee" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="charges-section-title" class="text-sm font-bold text-[color:var(--color-text-heading)]">Charges and adjustments</h2>
                            <p class="mt-0.5 text-xs text-[color:var(--color-text-caption)]">Enter monetary values in paise. For example, 100 equals ₹1.00.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <label for="discount_amount_minor" class="mb-1.5 block text-xs font-semibold text-neutral-700">Discount <span class="font-normal text-neutral-400">(paise)</span></label>
                            <input id="discount_amount_minor" name="discount_amount_minor" type="number" min="0" step="1" inputmode="numeric" x-model="discountAmount" :aria-invalid="hasError('discount_amount_minor') ? 'true' : null" :aria-describedby="hasError('discount_amount_minor') ? 'discount-amount-error' : null" class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            <p id="discount-amount-error" x-cloak x-show="hasError('discount_amount_minor')" x-text="errorFor('discount_amount_minor')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                        </div>
                        <div>
                            <label for="shipping_amount_minor" class="mb-1.5 block text-xs font-semibold text-neutral-700">Shipping <span class="font-normal text-neutral-400">(paise)</span></label>
                            <input id="shipping_amount_minor" name="shipping_amount_minor" type="number" min="0" step="1" inputmode="numeric" x-model="shippingAmount" :aria-invalid="hasError('shipping_amount_minor') ? 'true' : null" :aria-describedby="hasError('shipping_amount_minor') ? 'shipping-amount-error' : null" class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            <p id="shipping-amount-error" x-cloak x-show="hasError('shipping_amount_minor')" x-text="errorFor('shipping_amount_minor')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                        </div>
                        <div>
                            <label for="tax_amount_minor" class="mb-1.5 block text-xs font-semibold text-neutral-700">Tax <span class="font-normal text-neutral-400">(paise)</span></label>
                            <input id="tax_amount_minor" name="tax_amount_minor" type="number" min="0" step="1" inputmode="numeric" x-model="taxAmount" :aria-invalid="hasError('tax_amount_minor') ? 'true' : null" :aria-describedby="hasError('tax_amount_minor') ? 'tax-amount-error' : null" class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            <p id="tax-amount-error" x-cloak x-show="hasError('tax_amount_minor')" x-text="errorFor('tax_amount_minor')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-[color:var(--color-border)] bg-white p-5 shadow-xs sm:p-6" aria-labelledby="payment-section-title">
                    <div class="mb-5 flex items-start gap-3 border-b border-neutral-100 pb-4">
                        <span class="rounded-xl bg-neutral-100 p-2.5 text-neutral-700" aria-hidden="true">
                            <x-icons.lucide name="lucide-calendar-clock" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="payment-section-title" class="text-sm font-bold text-[color:var(--color-text-heading)]">Advance payment <span class="font-normal text-neutral-400">(optional)</span></h2>
                            <p class="mt-0.5 text-xs text-[color:var(--color-text-caption)]">Add both an amount and due date to create a payment schedule.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="advance_amount_minor" class="mb-1.5 block text-xs font-semibold text-neutral-700">Amount <span class="font-normal text-neutral-400">(paise)</span></label>
                            <input id="advance_amount_minor" name="advance_payment[amount_minor]" type="number" min="0" step="1" inputmode="numeric" x-model="advanceAmount" :aria-invalid="hasError('advance_payment.amount_minor') ? 'true' : null" :aria-describedby="hasError('advance_payment.amount_minor') ? 'advance-amount-error' : null" class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            <p id="advance-amount-error" x-cloak x-show="hasError('advance_payment.amount_minor')" x-text="errorFor('advance_payment.amount_minor')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                        </div>
                        <div>
                            <label for="advance_due_date" class="mb-1.5 block text-xs font-semibold text-neutral-700">Due date</label>
                            <input id="advance_due_date" name="advance_payment[due_date]" type="date" x-model="advanceDueDate" :aria-invalid="hasError('advance_payment.due_date') ? 'true' : null" :aria-describedby="hasError('advance_payment.due_date') ? 'advance-date-error' : null" class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-sm text-neutral-900 focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                            <p id="advance-date-error" x-cloak x-show="hasError('advance_payment.due_date')" x-text="errorFor('advance_payment.due_date')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.orders.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-neutral-300 bg-white px-5 py-2.5 text-xs font-semibold text-neutral-700 transition-colors hover:bg-neutral-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">Cancel</a>
                    <button type="submit" :disabled="submitting" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[color:var(--color-brand-600)] px-6 py-2.5 text-xs font-bold text-white shadow-xs transition-colors hover:bg-[color:var(--color-brand-700)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        <x-icons.lucide name="lucide-loader-circle" x-cloak x-show="submitting" class="h-4 w-4 animate-spin" />
                        <x-icons.lucide name="lucide-check" x-show="!submitting" class="h-4 w-4" />
                        <span x-text="submitting ? 'Creating order…' : 'Create sales order'">Create sales order</span>
                    </button>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-0" aria-label="Order creation guidance">
                <div class="rounded-2xl border border-[color:var(--color-border)] bg-white p-5 shadow-xs">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[color:var(--color-brand-700)]">Order checklist</p>
                    <h2 class="mt-1 text-sm font-bold text-neutral-900">Ready to create?</h2>
                    <ul class="mt-4 space-y-3 text-xs text-neutral-600">
                        <li class="flex items-start gap-2.5"><x-icons.lucide name="lucide-circle-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Confirm the customer account.</li>
                        <li class="flex items-start gap-2.5"><x-icons.lucide name="lucide-circle-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Check every SKU and quantity.</li>
                        <li class="flex items-start gap-2.5"><x-icons.lucide name="lucide-circle-check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" /> Add payment terms only when agreed.</li>
                    </ul>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-950">
                    <div class="flex items-start gap-3">
                        <x-icons.lucide name="lucide-info" class="mt-0.5 h-4 w-4 shrink-0 text-amber-700" />
                        <div><h2 class="text-xs font-bold">Before submitting</h2><p class="mt-1 text-[11px] leading-5 text-amber-900/80">The order is immediately confirmed. Inventory and financial records may be created from these values.</p></div>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</x-layouts.admin>
