<x-layouts.admin title="Initiate Warehouse Transfer">
    <div class="space-y-6 max-w-2xl mx-auto">

        <!-- Page Header -->
        <div class="flex items-center gap-3 border-b border-neutral-200 pb-5">
            <a href="{{ route('admin.inventory.transfers.index') }}" class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors">
                <x-icons.lucide name="lucide-arrow-left" class="w-5 h-5" />
            </a>
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Initiate Warehouse Transfer</h1>
                <p class="text-xs text-neutral-500 mt-1">Create a new stock transfer request between warehouse locations.</p>
            </div>
        </div>

        <form action="{{ route('admin.inventory.transfers.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Form Card -->
            <div class="p-6 rounded-2xl bg-white border border-neutral-200 shadow-xs space-y-4">
                <!-- SKU Selection -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Select Product SKU</label>
                    <select name="product_sku_id" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="">-- Select SKU --</option>
                        @foreach ($skus as $sku)
                            <option value="{{ $sku->id }}">{{ $sku->sku_code }} - {{ $sku->product?->name }} (Stock: {{ $sku->stock_quantity }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Locations Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Source Location -->
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Source Location</label>
                        <select name="source_location" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->value }}" {{ $loc->value === 'main_warehouse' ? 'selected' : '' }}>{{ $loc->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Destination Location -->
                    <div>
                        <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Destination Location</label>
                        <select name="destination_location" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->value }}" {{ $loc->value === 'store' ? 'selected' : '' }}>{{ $loc->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Transfer Stock Quantity</label>
                    <input type="number" name="quantity" value="10" min="1" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-semibold text-neutral-700 mb-1.5">Transfer Instructions &amp; Notes</label>
                    <textarea name="notes" rows="3" placeholder="Reason for transfer or logistics instructions..." class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors"></textarea>
                </div>
            </div>

            <!-- Submit Bar -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                <a href="{{ route('admin.inventory.transfers.index') }}" class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">Create Draft Transfer</button>
            </div>
        </form>

    </div>
</x-layouts.admin>
