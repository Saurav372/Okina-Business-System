<x-layouts.admin title="Create Purchase Order">
    <div class="space-y-6 max-w-2xl mx-auto">

        <!-- Page Header -->
        <div class="flex items-center gap-3 border-b border-neutral-200 pb-5">
            <a href="{{ route('admin.purchases.index') }}" class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors">
                <x-icons.lucide name="lucide-arrow-left" class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Create Purchase Order</h1>
                <p class="text-xs text-neutral-500 mt-1">Initiate a new procurement order with a supplier or vendor.</p>
            </div>
        </div>

        <form action="{{ route('admin.purchase_orders.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Form Card -->
            <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
                <!-- Vendor Selection -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Select Vendor</label>
                    <select name="vendor_id" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="">-- Select Vendor --</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}">{{ $v->name }} ({{ $v->vendor_code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dates Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Order Date -->
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Order Date</label>
                        <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                    </div>

                    <!-- Expected Delivery Date -->
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                    </div>
                </div>

                <!-- Payment Terms -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Payment Terms</label>
                    <input type="text" name="payment_terms" placeholder="e.g. Net 30, COD, 50% Advance" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Internal Notes &amp; Logistics Instructions</label>
                    <textarea name="notes" rows="3" placeholder="Additional details or instructions for this purchase order..." class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors"></textarea>
                </div>
            </div>

            <!-- Submit Bar -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                <a href="{{ route('admin.purchases.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">Create Purchase Order</button>
            </div>
        </form>

    </div>
</x-layouts.admin>
