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
            'mockupUploadUrl' => route('admin.sales_orders.upload_mockup'),
            'quickCustomerStoreUrl' => route('admin.customers.quick_store'),
            'orderDetailBaseUrl' => url('/admin/orders'),
            'csrfToken' => csrf_token(),
            'initialCustomers' => $customers->map(fn ($c) => [
                'id' => (string) $c->id,
                'display_name' => $c->display_name ?: $c->name,
            ])->values()->all(),
            'initialSkus' => $skus->map(function ($sku) {
                $prodName = $sku->product?->name ?? '';
                if ($sku->name_suffix) {
                    $prodName = $prodName ? "{$prodName} ({$sku->name_suffix})" : $sku->name_suffix;
                }
                return [
                    'id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'product_name' => $prodName,
                    'price_minor' => $sku->price_minor,
                    'price_formatted' => $sku->price_minor !== null ? '₹' . number_format($sku->price_minor / 100, 2) : null,
                    'stock_quantity' => $sku->stock_quantity,
                    'label' => $sku->sku_code . ($prodName ? ' · ' . $prodName : ''),
                ];
            })->values()->all(),
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
                    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-100 pb-4">
                        <div class="flex items-start gap-3">
                            <span class="rounded-xl bg-[color:var(--color-brand-50)] p-2.5 text-[color:var(--color-brand-700)]" aria-hidden="true">
                                <x-icons.lucide name="lucide-user-round" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 id="customer-section-title" class="text-sm font-bold text-[color:var(--color-text-heading)]">Customer</h2>
                                <p class="mt-0.5 text-xs text-[color:var(--color-text-caption)]">Choose who this sales order belongs to.</p>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            @click="openNewCustomerModal()"
                            class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-2 text-xs font-bold text-neutral-800 transition-colors hover:border-neutral-300 hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] cursor-pointer shrink-0"
                        >
                            <x-icons.lucide name="lucide-user-plus" class="h-4 w-4 text-[color:var(--color-brand-600)]" />
                            <span>+ New Customer</span>
                        </button>
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
                        <template x-for="c in customers" :key="c.id">
                            <option :value="String(c.id)" x-text="c.display_name"></option>
                        </template>
                    </select>
                    <p id="customer-id-help" class="mt-1.5 text-[11px] text-[color:var(--color-text-caption)]">
                        Instagram, WhatsApp, or walk-in client? Click <strong class="text-neutral-700">+ New Customer</strong> to register them without leaving this page.
                    </p>
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
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <label for="sku-filter" class="block text-xs font-semibold text-[color:var(--color-text-body)]">Find a product or SKU</label>
                            <button 
                                type="button" 
                                x-cloak 
                                x-show="skuQuery.trim().length > 0" 
                                @click="clearSkuSearch()" 
                                class="text-[11px] font-semibold text-neutral-500 hover:text-neutral-800 transition-colors cursor-pointer"
                            >
                                Clear search
                            </button>
                        </div>
                        <div class="relative">
                            <x-icons.lucide name="lucide-search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
                            <input
                                id="sku-filter"
                                type="search"
                                x-model="skuQuery"
                                @input="queueSkuSearch"
                                @keydown.escape="clearSkuSearch()"
                                aria-describedby="sku-search-status"
                                placeholder="Search by SKU code or product name (e.g. STU, Box, T-Shirt)"
                                class="min-h-11 w-full rounded-xl border border-neutral-300 bg-white py-2.5 pl-10 pr-10 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >
                            <span x-cloak x-show="searching" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400" aria-hidden="true">
                                <x-icons.lucide name="lucide-loader-circle" class="h-4 w-4 animate-spin" />
                            </span>
                            <button
                                type="button"
                                x-cloak
                                x-show="skuQuery.trim().length > 0 && !searching"
                                @click="clearSkuSearch()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-neutral-400 hover:text-neutral-600 rounded-lg cursor-pointer"
                                title="Clear search"
                            >
                                <x-icons.lucide name="lucide-x" class="h-4 w-4" />
                            </button>
                        </div>
                        <p id="sku-search-status" role="status" aria-live="polite" class="mt-1.5 min-h-4 text-[11px] text-[color:var(--color-text-caption)]" x-text="skuStatus"></p>

                        <!-- Matching SKUs Result List -->
                        <div 
                            x-cloak 
                            x-show="skuQuery.trim().length > 0 && skuOptions.length > 0"
                            class="mt-3.5 rounded-2xl border border-neutral-200 bg-white p-3.5 shadow-sm"
                        >
                            <div class="mb-2 flex items-center justify-between border-b border-neutral-100 pb-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center rounded-md bg-neutral-900 px-2 py-0.5 text-[11px] font-bold text-white" x-text="skuOptions.length"></span>
                                    <span class="text-xs font-bold text-neutral-800">Matching Items Found</span>
                                </div>
                                <span class="text-[11px] text-neutral-400 hidden sm:inline">Click "+ Add to order" or click an item</span>
                            </div>

                            <div class="max-h-60 divide-y divide-neutral-100 overflow-y-auto pr-1">
                                <template x-for="sku in skuOptions" :key="sku.sku_code">
                                    <div class="group flex items-center justify-between gap-3 py-2 px-2 rounded-xl transition-colors hover:bg-neutral-50">
                                        <div class="min-w-0 flex-1 cursor-pointer" @click="addSkuToOrder(sku)">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="font-mono text-xs font-bold text-neutral-900 bg-neutral-100 px-1.5 py-0.5 rounded" x-text="sku.sku_code"></span>
                                                <span x-show="sku.price_formatted" class="font-semibold text-xs text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded" x-text="sku.price_formatted"></span>
                                                <span 
                                                    x-show="sku.stock_quantity !== null && sku.stock_quantity !== undefined" 
                                                    class="text-[11px]" 
                                                    :class="sku.stock_quantity > 0 ? 'text-neutral-500' : 'text-rose-600 font-medium'"
                                                    x-text="sku.stock_quantity > 0 ? (sku.stock_quantity + ' in stock') : 'Out of stock'"
                                                ></span>
                                            </div>
                                            <p class="mt-0.5 truncate text-xs text-neutral-600" x-text="sku.product_name || sku.label"></p>
                                        </div>

                                        <button
                                            type="button"
                                            @click="addSkuToOrder(sku)"
                                            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-neutral-200 bg-white px-3 py-1.5 text-xs font-bold text-neutral-800 shadow-2xs transition hover:border-neutral-900 hover:bg-neutral-900 hover:text-white active:scale-95 cursor-pointer"
                                            :aria-label="'Add ' + sku.sku_code + ' to order'"
                                        >
                                            <x-icons.lucide name="lucide-plus" class="h-3.5 w-3.5" />
                                            <span>Add to order</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- No Matching SKUs Message -->
                        <div 
                            x-cloak 
                            x-show="skuQuery.trim().length > 0 && !searching && skuOptions.length === 0"
                            class="mt-3.5 rounded-2xl border border-neutral-200 bg-white p-4 text-center"
                        >
                            <p class="text-xs font-medium text-neutral-600">
                                No SKUs or products match "<strong class="text-neutral-900" x-text="skuQuery"></strong>".
                            </p>
                            <button 
                                type="button" 
                                @click="clearSkuSearch()" 
                                class="mt-2 text-xs font-bold text-[color:var(--color-brand-600)] hover:underline cursor-pointer"
                            >
                                Clear search filter
                            </button>
                        </div>
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

                                <!-- Custom Print / Artwork Section -->
                                <div class="mt-4 border-t border-neutral-200 pt-3.5">
                                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                                        <input 
                                            type="checkbox" 
                                            x-model="item.has_customization" 
                                            class="h-4 w-4 rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--focus-ring-color)] cursor-pointer"
                                        >
                                        <span class="text-xs font-bold text-neutral-800">Custom Print / Artwork Required</span>
                                    </label>

                                    <!-- Visual Customization Container -->
                                    <div 
                                        x-cloak 
                                        x-show="item.has_customization" 
                                        class="mt-3 space-y-4 rounded-2xl border border-neutral-200 bg-white p-4 shadow-2xs"
                                    >
                                        <!-- 1. Mockup / Design Reference Box -->
                                        <div>
                                            <div class="mb-1.5 flex items-center justify-between">
                                                <label class="text-xs font-bold text-neutral-800">
                                                    Mockup / Design Reference <span class="text-rose-600">*</span>
                                                </label>
                                                <span class="text-[11px] text-neutral-500 hidden sm:inline">
                                                    Visual placement, colors & scale
                                                </span>
                                            </div>

                                            <!-- When mockup is uploaded -->
                                            <div 
                                                x-cloak 
                                                x-show="item.mockup" 
                                                class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 bg-neutral-50/70 p-3"
                                            >
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <!-- Thumbnail with enlargement click -->
                                                    <button 
                                                        type="button" 
                                                        @click="openLightbox(item.mockup_preview_url || item.mockup_local_preview, item.mockup?.original_filename)" 
                                                        class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-neutral-200 bg-white cursor-zoom-in"
                                                        title="Click to view enlarged mockup"
                                                    >
                                                        <img 
                                                            :src="item.mockup_preview_url || item.mockup_local_preview" 
                                                            :alt="item.mockup?.original_filename" 
                                                            class="h-full w-full object-cover transition group-hover:scale-105"
                                                        >
                                                        <span class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition text-white">
                                                            <x-icons.lucide name="lucide-maximize-2" class="h-4 w-4" />
                                                        </span>
                                                    </button>
                                                    <div class="min-w-0">
                                                        <p class="truncate font-medium text-xs text-neutral-900" x-text="item.mockup?.original_filename"></p>
                                                        <div class="mt-0.5 flex items-center gap-2 text-[11px] text-neutral-500">
                                                            <span x-text="formatFileSize(item.mockup?.size_bytes)"></span>
                                                            <span class="text-emerald-700 font-semibold flex items-center gap-1">
                                                                <x-icons.lucide name="lucide-check" class="h-3 w-3" />
                                                                Uploaded
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 shrink-0">
                                                    <label 
                                                        class="px-2.5 py-1.5 rounded-lg border border-neutral-300 text-xs font-semibold text-neutral-700 hover:bg-white cursor-pointer transition"
                                                    >
                                                        Change
                                                        <input 
                                                            type="file" 
                                                            accept="image/png,image/jpeg,image/webp" 
                                                            @change="handleMockupFileInput(item, $event)" 
                                                            class="hidden"
                                                        >
                                                    </label>
                                                    <button 
                                                        type="button" 
                                                        @click="removeMockup(item)" 
                                                        class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-700 hover:bg-rose-50 transition cursor-pointer"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Dropzone / Paste container when no mockup -->
                                            <div 
                                                x-show="!item.mockup"
                                                @paste="handleMockupPaste(item, $event)"
                                                @dragover.prevent="item.dragover = true"
                                                @dragleave.prevent="item.dragover = false"
                                                @drop.prevent="item.dragover = false; handleMockupDrop(item, $event)"
                                                class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-6 text-center transition-colors cursor-pointer"
                                                :class="item.dragover ? 'border-[color:var(--color-brand-500)] bg-[color:var(--color-brand-50)]' : 'border-neutral-300 bg-neutral-50/50 hover:border-neutral-400 hover:bg-neutral-50'"
                                                tabindex="0"
                                                @keydown.enter="$refs['fileInput_' + item.key].click()"
                                                @click="$refs['fileInput_' + item.key].click()"
                                            >
                                                <input 
                                                    :x-ref="'fileInput_' + item.key"
                                                    type="file" 
                                                    accept="image/png,image/jpeg,image/webp" 
                                                    @change="handleMockupFileInput(item, $event)" 
                                                    class="hidden"
                                                >

                                                <!-- Uploading state indicator -->
                                                <div x-cloak x-show="item.uploading_mockup" class="space-y-2">
                                                    <x-icons.lucide name="lucide-loader-circle" class="mx-auto h-7 w-7 animate-spin text-[color:var(--color-brand-600)]" />
                                                    <p class="text-xs font-semibold text-neutral-800">Uploading & processing mockup…</p>
                                                </div>

                                                <!-- Default dropzone prompt -->
                                                <div x-show="!item.uploading_mockup" class="space-y-1.5 pointer-events-none">
                                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-white text-neutral-700 shadow-2xs">
                                                        <x-icons.lucide name="lucide-upload" class="h-5 w-5" />
                                                    </div>
                                                    <p class="text-xs font-bold text-neutral-800">
                                                        Drop image here, click to browse, or press <kbd class="px-1.5 py-0.5 bg-white border border-neutral-300 rounded font-mono text-[10px] text-neutral-700">Ctrl+V</kbd> to paste
                                                    </p>
                                                    <p class="text-[11px] text-neutral-500">
                                                        PNG, JPG, WEBP • Max 10MB
                                                    </p>
                                                </div>
                                            </div>
                                            <p x-cloak x-show="hasError('items.' + index + '.mockup')" x-text="errorFor('items.' + index + '.mockup')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                                        </div>

                                        <!-- 2. Print Method Multi-Select -->
                                        <div>
                                            <div class="mb-1.5 flex items-center justify-between">
                                                <label class="text-xs font-bold text-neutral-800">
                                                    Print Method <span class="text-rose-600">*</span>
                                                </label>
                                                <span class="text-[11px] text-neutral-500">
                                                    Select all techniques required (e.g. Chest + Back)
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="method in availablePrintMethods" :key="method.id">
                                                    <button
                                                        type="button"
                                                        @click="togglePrintMethod(item, method.id)"
                                                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs transition active:scale-95 cursor-pointer"
                                                        :class="item.print_methods.includes(method.id) 
                                                            ? 'border-neutral-900 bg-neutral-900 font-bold text-white shadow-2xs' 
                                                            : 'border-neutral-300 bg-white font-medium text-neutral-700 hover:border-neutral-400 hover:bg-neutral-50'"
                                                    >
                                                        <span x-cloak x-show="item.print_methods.includes(method.id)" class="text-white font-bold">✓</span>
                                                        <span x-text="method.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                            <p x-cloak x-show="hasError('items.' + index + '.print_methods')" x-text="errorFor('items.' + index + '.print_methods')" class="mt-1.5 text-xs font-medium text-rose-700"></p>
                                        </div>

                                        <!-- 3. Production & Placement Notes -->
                                        <div>
                                            <label :for="'item-notes-' + item.key" class="mb-1.5 block text-xs font-bold text-neutral-800">
                                                Production & Placement Notes
                                            </label>
                                            <textarea
                                                :id="'item-notes-' + item.key"
                                                x-model="item.production_notes"
                                                rows="3"
                                                placeholder="e.g. Right chest embroidery. Back DTF approx. 10-inch width. Customer approved on WhatsApp."
                                                class="w-full rounded-xl border border-neutral-300 bg-white px-3.5 py-2.5 text-xs text-neutral-900 placeholder-neutral-400 transition focus:border-[color:var(--color-brand-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                                            ></textarea>
                                            <p class="mt-1 text-[11px] text-neutral-500">Explain specific placements, colors, or sizing for production operators.</p>
                                        </div>
                                    </div>
                                </div>
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
                    <button type="submit" :disabled="submitting || isAnyMockupUploading()" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[color:var(--color-brand-600)] px-6 py-2.5 text-xs font-bold text-white shadow-xs transition-colors hover:bg-[color:var(--color-brand-700)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        <x-icons.lucide name="lucide-loader-circle" x-cloak x-show="submitting || isAnyMockupUploading()" class="h-4 w-4 animate-spin" />
                        <x-icons.lucide name="lucide-check" x-show="!submitting && !isAnyMockupUploading()" class="h-4 w-4" />
                        <span x-text="submitting ? 'Creating order…' : (isAnyMockupUploading() ? 'Artwork upload in progress…' : 'Create sales order')">Create sales order</span>
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

        <!-- Quick Add Customer Modal -->
        <x-modal id="quick-customer-modal" title="New Customer / Brand Account" size="lg">
            <div class="space-y-4">
                <!-- Validation Error Summary -->
                <div x-cloak x-show="Object.keys(newCustomerErrors).length > 0" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                    <ul class="list-disc pl-4 space-y-0.5">
                        <template x-for="(errs, f) in newCustomerErrors" :key="f">
                            <template x-for="err in errs">
                                <li x-text="err"></li>
                            </template>
                        </template>
                    </ul>
                </div>

                <!-- 1. Name & Brand Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                            Full Name <span class="text-rose-600">*</span>
                        </label>
                        <input 
                            type="text" 
                            x-model="newCustomer.name" 
                            placeholder="e.g. Rohan Sharma"
                            class="w-full px-3.5 py-2.5 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-neutral-900"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                            Brand Name <span class="text-rose-600">*</span>
                        </label>
                        <input 
                            type="text" 
                            x-model="newCustomer.brand_name" 
                            placeholder="e.g. Apex Streetwear"
                            class="w-full px-3.5 py-2.5 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-neutral-900"
                        >
                    </div>
                </div>

                <!-- 2. Mobile Numbers & WhatsApp Toggle -->
                <div class="rounded-xl border border-neutral-200 bg-neutral-50/60 p-3.5 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-neutral-800">
                            Mobile Number(s) <span class="text-rose-600">*</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-neutral-600 select-none">
                            <input 
                                type="checkbox" 
                                x-model="newCustomer.same_as_whatsapp" 
                                class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)]"
                            >
                            <span>Same as WhatsApp</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(phone, pIdx) in newCustomer.phones" :key="pIdx">
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-neutral-400 font-mono">+91</span>
                                    <input 
                                        type="tel" 
                                        x-model="newCustomer.phones[pIdx]" 
                                        placeholder="98765 43210"
                                        class="w-full pl-12 pr-3.5 py-2 text-sm bg-white border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] font-mono text-neutral-900"
                                    >
                                </div>
                                <button 
                                    type="button" 
                                    x-show="newCustomer.phones.length > 1" 
                                    @click="removePhoneField(pIdx)"
                                    class="p-2 text-neutral-400 hover:text-rose-600 rounded-lg hover:bg-neutral-200 transition-colors"
                                    title="Remove number"
                                >
                                    <x-icons.lucide name="lucide-trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <button 
                        type="button" 
                        @click="addPhoneField()" 
                        class="text-xs font-bold text-[color:var(--color-brand-600)] hover:underline flex items-center gap-1 cursor-pointer pt-0.5"
                    >
                        <x-icons.lucide name="lucide-plus" class="w-3.5 h-3.5" />
                        <span>Add another number</span>
                    </button>

                    <!-- Separate WhatsApp Number if toggle is OFF -->
                    <div x-cloak x-show="!newCustomer.same_as_whatsapp" class="pt-2 border-t border-neutral-200">
                        <label class="block text-[11px] font-semibold text-neutral-700 mb-1">
                            Dedicated WhatsApp Number
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-neutral-400 font-mono">+91</span>
                            <input 
                                type="tel" 
                                x-model="newCustomer.whatsapp_phone" 
                                placeholder="WhatsApp contact number"
                                class="w-full pl-12 pr-3.5 py-2 text-sm bg-white border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] font-mono text-neutral-900"
                            >
                        </div>
                    </div>
                </div>

                <!-- 3. Lead Source & Optional Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                            Lead Source <span class="text-rose-600">*</span>
                        </label>
                        <select 
                            x-model="newCustomer.lead_source"
                            class="w-full px-3.5 py-2.5 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] bg-white text-neutral-900"
                        >
                            <option value="instagram">Instagram DM</option>
                            <option value="whatsapp">WhatsApp Inquiry</option>
                            <option value="walk_in">Walk-in / In-store</option>
                            <option value="phone">Phone / Direct Call</option>
                            <option value="referral">Referral / Word of Mouth</option>
                            <option value="other">Other Medium</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                            Email Address <span class="text-neutral-400 font-normal">(optional)</span>
                        </label>
                        <input 
                            type="email" 
                            x-model="newCustomer.email" 
                            placeholder="customer@brand.com"
                            class="w-full px-3.5 py-2.5 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-neutral-900"
                        >
                    </div>
                </div>

                <!-- 4. Shipping Address -->
                <div class="rounded-xl border border-neutral-200 p-3.5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider">Shipping Address</h4>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-neutral-600 select-none">
                            <input 
                                type="checkbox" 
                                x-model="newCustomer.same_as_billing" 
                                class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)]"
                            >
                            <span>Same as Billing Address</span>
                        </label>
                    </div>

                    <div class="space-y-2.5">
                        <input 
                            type="text" 
                            x-model="newCustomer.shipping_address.line_1" 
                            placeholder="Address Line 1 (Flat, Building, Street)"
                            class="w-full px-3.5 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                        >
                        <input 
                            type="text" 
                            x-model="newCustomer.shipping_address.line_2" 
                            placeholder="Address Line 2 (Area, Landmark - Optional)"
                            class="w-full px-3.5 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                        >
                        <div class="grid grid-cols-3 gap-2.5">
                            <input 
                                type="text" 
                                x-model="newCustomer.shipping_address.city" 
                                placeholder="City"
                                class="w-full px-3 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >
                            <input 
                                type="text" 
                                x-model="newCustomer.shipping_address.state" 
                                placeholder="State"
                                class="w-full px-3 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >
                            <input 
                                type="text" 
                                x-model="newCustomer.shipping_address.postal_code" 
                                placeholder="Pincode"
                                class="w-full px-3 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] font-mono"
                            >
                        </div>
                    </div>

                    <!-- Separate Billing Address if toggle is OFF -->
                    <div x-cloak x-show="!newCustomer.same_as_billing" class="pt-3 border-t border-neutral-200 space-y-2.5">
                        <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider">Billing Address</h4>
                        <input 
                            type="text" 
                            x-model="newCustomer.billing_address.line_1" 
                            placeholder="Billing Address Line 1"
                            class="w-full px-3.5 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                        >
                        <div class="grid grid-cols-3 gap-2.5">
                            <input 
                                type="text" 
                                x-model="newCustomer.billing_address.city" 
                                placeholder="City"
                                class="w-full px-3 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >
                            <input 
                                type="text" 
                                x-model="newCustomer.billing_address.state" 
                                placeholder="State"
                                class="w-full px-3 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"
                            >
                            <input 
                                type="text" 
                                x-model="newCustomer.billing_address.postal_code" 
                                placeholder="Pincode"
                                class="w-full px-3 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] font-mono"
                            >
                        </div>
                        <input 
                            type="text" 
                            x-model="newCustomer.billing_address.gstin" 
                            placeholder="GSTIN (Optional for business invoicing)"
                            class="w-full px-3.5 py-2 text-sm border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] font-mono"
                        >
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <button 
                    type="button" 
                    @click="closeNewCustomerModal()" 
                    class="px-4 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 cursor-pointer"
                >
                    Cancel
                </button>
                <button 
                    type="button" 
                    @click="saveNewCustomer()" 
                    :disabled="savingCustomer"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-[color:var(--color-brand-600)] text-white hover:bg-[color:var(--color-brand-700)] rounded-xl text-xs font-bold transition-colors cursor-pointer disabled:opacity-60"
                >
                    <x-icons.lucide name="lucide-loader-circle" x-cloak x-show="savingCustomer" class="w-3.5 h-3.5 animate-spin" />
                    <span x-text="savingCustomer ? 'Saving & Selecting…' : 'Save & Select Customer'">Save & Select Customer</span>
                </button>
            </x-slot:footer>
        </x-modal>
        
        <!-- Mockup Enlargement Lightbox Modal -->
        <div 
            x-cloak 
            x-show="lightboxImage" 
            @keydown.escape.window="closeLightbox()"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
        >
            <div @click.away="closeLightbox()" class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl bg-neutral-900 p-2 shadow-2xl">
                <div class="flex items-center justify-between px-3 py-2 text-white">
                    <p class="truncate text-xs font-bold text-neutral-200" x-text="lightboxTitle || 'Mockup Preview'"></p>
                    <button type="button" @click="closeLightbox()" class="p-1 rounded-lg text-neutral-400 hover:text-white hover:bg-neutral-800 cursor-pointer">
                        <x-icons.lucide name="lucide-x" class="h-5 w-5" />
                    </button>
                </div>
                <div class="flex items-center justify-center p-2 bg-neutral-950/50 rounded-xl max-h-[80vh] overflow-auto">
                    <img :src="lightboxImage" :alt="lightboxTitle" class="max-h-[75vh] w-auto max-w-full object-contain rounded-lg">
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
