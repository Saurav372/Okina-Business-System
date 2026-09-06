<x-layouts.admin title="Create Purchase Order" description="Initiate a new procurement order with a supplier or vendor, select SKUs, and define procurement costs.">
    <x-slot:header>
        <a href="{{ route('admin.purchases.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl shadow-2xs transition-colors">
            <x-icons.lucide name="lucide-arrow-left" class="w-4 h-4" />
            <span>Back to Purchase Orders</span>
        </a>
    </x-slot:header>

    @php
        $config = [
            'todayDate' => date('Y-m-d'),
            'vendors' => $vendors->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'vendor_code' => $v->vendor_code,
                'payment_terms' => $v->payment_terms ?? '',
            ])->values()->all(),
            'skus' => $skus->map(fn($s) => [
                'id' => $s->id,
                'sku_code' => $s->sku_code,
                'product_name' => $s->product?->name ?? 'Product',
                'stock_quantity' => $s->stock_quantity ?? 0,
            ])->values()->all(),
        ];
    @endphp

    <div class="space-y-6" x-data="purchaseOrderForm(@js($config))">

        <!-- Session & Validation Messages -->
        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-medium space-y-1 shadow-xs">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <x-icons.lucide name="lucide-alert-circle" class="w-4 h-4 text-red-600" />
                    <span>Please correct the following errors:</span>
                </div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.purchase_orders.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf

            <!-- Left 2 Columns: Details & Line Items -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. Vendor & Logistics Logistics Card -->
                <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
                    <div class="flex items-center gap-2.5 pb-3 border-b border-neutral-100">
                        <div class="p-2 rounded-xl bg-neutral-100 text-neutral-700">
                            <x-icons.lucide name="lucide-building-2" class="w-4 h-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900">Vendor &amp; Delivery Logistics</h2>
                            <p class="text-[11px] text-neutral-500">Choose the supplier and set target delivery milestones.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Vendor Selection -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">
                                Select Vendor <span class="text-red-500 font-bold">*</span>
                            </label>
                            <select name="vendor_id" x-model="vendorId" @change="onVendorChange()" required class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                                <option value="">-- Choose an active vendor --</option>
                                <template x-for="v in vendors" :key="v.id">
                                    <option :value="v.id" x-text="`${v.name} (${v.vendor_code})`"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Order Date -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Order Date <span class="text-red-500 font-bold">*</span></label>
                            <input type="date" name="ordered_at" x-model="orderDate" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        </div>

                        <!-- Expected Delivery Date -->
                        <div>
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Expected Delivery Date</label>
                            <input type="date" name="expected_at" x-model="expectedDate" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        </div>

                        <!-- Payment Terms -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Payment Terms</label>
                            <input type="text" name="payment_terms" x-model="paymentTerms" placeholder="e.g. Net 30, COD, 50% Advance, Due on Receipt" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        </div>
                    </div>
                </div>

                <!-- 2. Purchase Order Line Items Card -->
                <div class="rounded-2xl bg-white border border-neutral-200 shadow-xs overflow-hidden">
                    <div class="p-6 border-b border-neutral-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <div class="p-2 rounded-xl bg-neutral-100 text-neutral-700">
                                <x-icons.lucide name="lucide-layers" class="w-4 h-4" />
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-neutral-900">Procurement Items &amp; Quantities</h2>
                                <p class="text-[11px] text-neutral-500">Add catalog SKUs, quantities, and agreed unit purchase costs.</p>
                            </div>
                        </div>
                        <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-neutral-900 hover:bg-neutral-800 text-white text-xs font-bold rounded-xl shadow-xs transition-colors self-start sm:self-auto cursor-pointer">
                            <x-icons.lucide name="lucide-plus" class="w-3.5 h-3.5" />
                            <span>Add Line Item</span>
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-neutral-800">
                            <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                                <tr>
                                    <th class="py-3 px-4 font-semibold" style="width: 40%;">Product SKU <span class="text-red-500">*</span></th>
                                    <th class="py-3 px-3 font-semibold text-center" style="width: 15%;">Quantity <span class="text-red-500">*</span></th>
                                    <th class="py-3 px-3 font-semibold text-right" style="width: 18%;">Unit Cost (₹) <span class="text-red-500">*</span></th>
                                    <th class="py-3 px-3 font-semibold text-right" style="width: 14%;">Tax (₹)</th>
                                    <th class="py-3 px-4 font-semibold text-right" style="width: 18%;">Line Total</th>
                                    <th class="py-3 px-2 text-center" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                <template x-for="(item, index) in items" :key="item.key">
                                    <tr class="hover:bg-neutral-50/60 transition-colors">
                                        <!-- SKU Selection -->
                                        <td class="py-3 px-4">
                                            <select :name="`items[${index}][product_sku_id]`"
                                                    x-model="item.product_sku_id"
                                                    required
                                                    class="w-full px-3 py-1.5 border border-neutral-300 rounded-lg text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                                <option value="">-- Select Product SKU --</option>
                                                <template x-for="s in skus" :key="s.id">
                                                    <option :value="s.id" x-text="`${s.sku_code} · ${s.product_name} (Current: ${s.stock_quantity})`"></option>
                                                </template>
                                            </select>
                                        </td>

                                        <!-- Quantity Ordered -->
                                        <td class="py-3 px-3 text-center">
                                            <input type="number"
                                                   :name="`items[${index}][quantity_ordered]`"
                                                   x-model.number="item.quantity_ordered"
                                                   min="1"
                                                   required
                                                   class="w-20 px-2.5 py-1.5 border border-neutral-300 rounded-lg text-xs text-neutral-900 font-mono text-center focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                        </td>

                                        <!-- Unit Cost -->
                                        <td class="py-3 px-3 text-right">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   :name="`items[${index}][unit_cost]`"
                                                   x-model.number="item.unit_cost"
                                                   required
                                                   placeholder="0.00"
                                                   class="w-24 px-2.5 py-1.5 border border-neutral-300 rounded-lg text-xs text-neutral-900 font-mono text-right focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                        </td>

                                        <!-- Tax Amount -->
                                        <td class="py-3 px-3 text-right">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   :name="`items[${index}][tax_amount]`"
                                                   x-model.number="item.tax_amount"
                                                   placeholder="0.00"
                                                   class="w-20 px-2.5 py-1.5 border border-neutral-300 rounded-lg text-xs text-neutral-900 font-mono text-right focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                        </td>

                                        <!-- Line Total -->
                                        <td class="py-3 px-4 text-right font-mono font-bold text-neutral-900">
                                            ₹<span x-text="formatCurrency(getLineTotal(item))"></span>
                                        </td>

                                        <!-- Remove Action -->
                                        <td class="py-3 px-2 text-center">
                                            <button type="button"
                                                    @click="removeItem(index)"
                                                    :disabled="items.length <= 1"
                                                    :class="items.length <= 1 ? 'opacity-30 cursor-not-allowed' : 'hover:text-red-600 hover:bg-red-50 text-neutral-400 cursor-pointer'"
                                                    class="p-1 rounded-lg transition-colors"
                                                    title="Remove item">
                                                <x-icons.lucide name="lucide-trash-2" class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-neutral-50/70 border-t border-neutral-100 flex items-center justify-between">
                        <span class="text-xs text-neutral-500 font-medium" x-text="`${items.length} line item${items.length === 1 ? '' : 's'} configured.`"></span>
                        <button type="button" @click="addItem()" class="text-xs font-bold text-[color:var(--color-brand-600)] hover:text-[color:var(--color-brand-700)] inline-flex items-center gap-1 cursor-pointer">
                            <x-icons.lucide name="lucide-plus-circle" class="w-4 h-4" />
                            <span>Add row</span>
                        </button>
                    </div>
                </div>

                <!-- 3. Internal Notes Card -->
                <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-3">
                    <div class="flex items-center gap-2.5 pb-2">
                        <div class="p-2 rounded-xl bg-neutral-100 text-neutral-700">
                            <x-icons.lucide name="lucide-file-text" class="w-4 h-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-neutral-900">Logistics Instructions &amp; Internal Notes</h2>
                            <p class="text-[11px] text-neutral-500">Visible to receiving staff when verifying shipments.</p>
                        </div>
                    </div>
                    <textarea name="notes" x-model="notes" rows="3" placeholder="Enter packaging instructions, delivery dock contact, freight carrier details, or inspection guidelines..." class="w-full px-3.5 py-2.5 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors"></textarea>
                </div>

            </div>

            <!-- Right 1 Column: Sticky Financial Summary & Status -->
            <div class="space-y-6">

                <!-- Summary Card -->
                <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-5 sticky top-6">
                    <h3 class="text-sm font-bold text-neutral-900 uppercase tracking-wider text-[11px] border-b border-neutral-100 pb-3">Purchase Order Summary</h3>

                    <!-- Subtotal -->
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-neutral-500">Items Subtotal</span>
                        <span class="font-mono font-bold text-neutral-900">₹<span x-text="formatCurrency(subtotal)"></span></span>
                    </div>

                    <!-- Tax -->
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-neutral-500">Estimated Taxes</span>
                        <span class="font-mono font-bold text-neutral-900">₹<span x-text="formatCurrency(totalTax)"></span></span>
                    </div>

                    <!-- Shipping Input -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-600 font-medium">Shipping &amp; Freight (₹)</span>
                        </div>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="shipping_amount"
                               x-model.number="shippingAmount"
                               placeholder="0.00"
                               class="w-full px-3 py-1.5 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono text-right focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                    </div>

                    <!-- Discount Input -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-neutral-600 font-medium">Vendor Discount (₹)</span>
                        </div>
                        <input type="number"
                               step="0.01"
                               min="0"
                               name="discount_amount"
                               x-model.number="discountAmount"
                               placeholder="0.00"
                               class="w-full px-3 py-1.5 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono text-right focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                    </div>

                    <div class="border-t-2 border-neutral-100 pt-3">
                        <div class="flex items-baseline justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-neutral-700">Total PO Value</span>
                            <span class="text-xl font-extrabold font-mono text-[color:var(--color-brand-600)]">
                                ₹<span x-text="formatCurrency(grandTotal)"></span>
                            </span>
                        </div>
                        <p class="text-[10px] text-neutral-400 text-right mt-0.5">Total procurement liability</p>
                    </div>

                    <!-- Initial Status Selector -->
                    <div class="pt-2 border-t border-neutral-100 space-y-2">
                        <label class="block text-xs font-semibold text-neutral-700">Initial Status</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-colors" :class="status === 'draft' ? 'border-[color:var(--color-brand-500)] bg-[color:var(--color-brand-50)]' : 'border-neutral-200 hover:bg-neutral-50'">
                                <input type="radio" name="status" value="draft" x-model="status" class="text-[color:var(--color-brand-600)] focus:ring-[color:var(--focus-ring-color)]">
                                <div>
                                    <div class="text-xs font-bold text-neutral-900">Save as Draft</div>
                                    <div class="text-[10px] text-neutral-500">Keep order open for editing before vendor confirmation.</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer transition-colors" :class="status === 'ordered' ? 'border-[color:var(--color-brand-500)] bg-[color:var(--color-brand-50)]' : 'border-neutral-200 hover:bg-neutral-50'">
                                <input type="radio" name="status" value="ordered" x-model="status" class="text-[color:var(--color-brand-600)] focus:ring-[color:var(--focus-ring-color)]">
                                <div>
                                    <div class="text-xs font-bold text-neutral-900">Order &amp; Issue PO</div>
                                    <div class="text-[10px] text-neutral-500">Lock order and mark as ordered (ready for stock-in receiving).</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 space-y-2">
                        <button type="submit" class="w-full py-2.5 px-4 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                            <x-icons.lucide name="lucide-check" class="w-4 h-4" />
                            <span>Create Purchase Order</span>
                        </button>

                        <a href="{{ route('admin.purchases.index') }}" class="w-full py-2 px-4 text-xs font-semibold text-neutral-600 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors text-center block">
                            Cancel
                        </a>
                    </div>

                </div>

            </div>
        </form>

    </div>

    <script>
        function purchaseOrderForm(config) {
            return {
                vendorId: '',
                vendors: config.vendors || [],
                skus: config.skus || [],
                paymentTerms: '',
                orderDate: config.todayDate,
                expectedDate: '',
                status: 'draft',
                shippingAmount: 0,
                discountAmount: 0,
                notes: '',
                items: [
                    { key: 1, product_sku_id: '', quantity_ordered: 1, unit_cost: 0, tax_amount: 0 }
                ],
                nextKey: 2,

                onVendorChange() {
                    const v = this.vendors.find(x => String(x.id) === String(this.vendorId));
                    if (v && v.payment_terms) {
                        this.paymentTerms = v.payment_terms;
                    }
                },

                addItem() {
                    this.items.push({
                        key: this.nextKey++,
                        product_sku_id: '',
                        quantity_ordered: 1,
                        unit_cost: 0,
                        tax_amount: 0
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },

                getLineTotal(item) {
                    const qty = parseFloat(item.quantity_ordered) || 0;
                    const cost = parseFloat(item.unit_cost) || 0;
                    const tax = parseFloat(item.tax_amount) || 0;
                    return (qty * cost) + tax;
                },

                get subtotal() {
                    return this.items.reduce((sum, item) => {
                        const qty = parseFloat(item.quantity_ordered) || 0;
                        const cost = parseFloat(item.unit_cost) || 0;
                        return sum + (qty * cost);
                    }, 0);
                },

                get totalTax() {
                    return this.items.reduce((sum, item) => {
                        return sum + (parseFloat(item.tax_amount) || 0);
                    }, 0);
                },

                get grandTotal() {
                    const sub = this.subtotal;
                    const tax = this.totalTax;
                    const ship = parseFloat(this.shippingAmount) || 0;
                    const disc = parseFloat(this.discountAmount) || 0;
                    return Math.max(0, sub + tax + ship - disc);
                },

                formatCurrency(amount) {
                    return (Number(amount) || 0).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            };
        }
    </script>
</x-layouts.admin>
