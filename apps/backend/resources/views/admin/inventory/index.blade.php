<x-layouts.admin title="Stock Balances">
    <div class="space-y-6" x-data="{
        adjustModalOpen: {{ $errors->hasAny(['expected_on_hand', 'new_on_hand', 'new_reserved', 'reason_code', 'notes']) ? 'true' : 'false' }},
        activeItem: {{ $errors->hasAny(['expected_on_hand', 'new_on_hand', 'new_reserved', 'reason_code', 'notes']) && old('expected_on_hand') !== null ? json_encode([
            'sku_id' => session('adjustment_sku_id'),
            'on_hand_quantity' => (int) old('expected_on_hand', 0),
            'reserved_quantity' => (int) old('new_reserved', 0),
            'sku_code' => 'Adjustment SKU',
            'product_name' => 'Stock Item',
            'allow_negative_stock' => true,
            'adjust_url' => url()->current(),
        ]) : 'null' }},
        newOnHand: {{ old('new_on_hand') !== null ? (int) old('new_on_hand') : 0 }},
        newReserved: {{ old('new_reserved') !== null ? (int) old('new_reserved') : 0 }},
        reasonCode: '{{ old('reason_code', 'manual_adjustment') }}',
        notes: '{{ old('notes', '') }}',
        sensitiveReasons: ['damaged_goods', 'inventory_loss', 'theft', 'expired_stock'],

        openAdjustModal(item) {
            this.activeItem = item;
            this.newOnHand = item.on_hand_quantity;
            this.newReserved = item.reserved_quantity;
            this.reasonCode = 'manual_adjustment';
            this.notes = '';
            this.adjustModalOpen = true;
        },
        closeAdjustModal() {
            this.adjustModalOpen = false;
            this.activeItem = null;
        },
        get deltaOnHand() {
            if (!this.activeItem) return 0;
            return this.newOnHand - this.activeItem.on_hand_quantity;
        },
        get formattedDelta() {
            const delta = this.deltaOnHand;
            return delta >= 0 ? `+${delta}` : `${delta}`;
        },
        get isNotesRequired() {
            return this.sensitiveReasons.includes(this.reasonCode);
        }
    }" @keydown.escape.window="closeAdjustModal()">

        <!-- Scopes (Tabs Toolbar) - Horizontally Scrollable & Compact Spacing -->
        <div class="flex items-center gap-1.5 border-b border-neutral-200 pb-3 mb-4 overflow-x-auto flex-nowrap scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
            <a href="{{ route('admin.inventory.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)] shrink-0">
                Stock Balances
            </a>
            <a href="{{ route('admin.inventory.movements.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                Audit Trail Log
            </a>
            <a href="{{ route('admin.inventory.transfers.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                Warehouse Transfers
            </a>
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-medium space-y-1 shadow-xs">
                @foreach ($errors->all() as $error)
                    <div class="flex items-center gap-2">
                        <x-icons.lucide name="lucide-alert-circle" class="w-4 h-4 flex-shrink-0 text-red-600" />
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- KPI Metrics Summary Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total SKUs -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Tracked SKUs</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-box" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->totalSkus) }}</div>
                <div class="mt-1 text-xs text-neutral-500">Active catalog items</div>
            </div>

            <!-- In Stock -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">In Stock</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($metrics->inStockCount) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Healthy stock levels</div>
            </div>

            <!-- Low Stock -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Low Stock Warning</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-alert-triangle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-amber-600">{{ number_format($metrics->lowStockCount) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">At or below reorder threshold</div>
            </div>

            <!-- Out of Stock -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Out of Stock / Negative</span>
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600 border border-red-100">
                        <x-icons.lucide name="lucide-alert-octagon" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-red-600">{{ number_format($metrics->outOfStockCount) }}</div>
                <div class="mt-1 text-xs text-red-700/80">Immediate fulfillment risk</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search ?? '' }}" placeholder="Search product, SKU code, barcode, or ID..." class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Location Selector -->
                <div>
                    <select name="location" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->location?->value ?? '') === '' ? 'selected' : '' }}>All Warehouses</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->value }}" {{ ($filters->location?->value ?? '') === $loc->value ? 'selected' : '' }}>{{ $loc->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Selector -->
                <div>
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->status?->value ?? '') === '' ? 'selected' : '' }}>All Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ ($filters->status?->value ?? '') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <select name="sort_by" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="available_quantity" {{ $filters->sortBy === 'available_quantity' ? 'selected' : '' }}>Sort: Available</option>
                        <option value="on_hand_quantity" {{ $filters->sortBy === 'on_hand_quantity' ? 'selected' : '' }}>Sort: On-Hand</option>
                        <option value="product_name" {{ $filters->sortBy === 'product_name' ? 'selected' : '' }}>Sort: Product Name</option>
                        <option value="sku_code" {{ $filters->sortBy === 'sku_code' ? 'selected' : '' }}>Sort: SKU Code</option>
                    </select>
                </div>

                <!-- Sort Order & Filter Submit -->
                <div class="flex items-center gap-2">
                    <select name="sort_order" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="desc" {{ ($filters->sortOrder ?? 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
                        <option value="asc" {{ ($filters->sortOrder ?? 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
                    </select>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shrink-0">Filter</button>
                </div>
            </form>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Product &amp; SKU Info</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold text-right">On-Hand</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Reserved</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Available</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Threshold</th>
                            <th class="py-3.5 px-4 font-semibold">Last Movement</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($items as $item)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Product & SKU Info -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $item->sku?->product?->name ?? 'Unknown Product' }}</div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="inline-flex px-2 py-0.5 bg-neutral-100 text-neutral-700 rounded text-[11px] font-mono font-medium border border-neutral-200">
                                            {{ $item->sku?->sku_code }}
                                        </span>
                                        @if ($item->sku?->barcode)
                                            <span class="text-[11px] font-mono text-neutral-400">Barcode: {{ $item->sku->barcode }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $item->status->badgeClass() }}">
                                        {{ $item->status->label() }}
                                    </span>
                                </td>

                                <!-- On-Hand Quantity -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-800">
                                    {{ number_format($item->on_hand_quantity) }}
                                </td>

                                <!-- Reserved Quantity -->
                                <td class="py-3.5 px-4 text-right font-mono text-neutral-500">
                                    {{ number_format($item->reserved_quantity) }}
                                </td>

                                <!-- Available Quantity -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-900">
                                    <span class="{{ $item->available_quantity <= 0 ? 'text-red-600 font-extrabold' : 'text-neutral-900' }}">
                                        {{ number_format($item->available_quantity) }}
                                    </span>
                                </td>

                                <!-- Reorder Threshold -->
                                <td class="py-3.5 px-4 text-right font-mono text-neutral-400">
                                    {{ $item->resolvedLowStockThreshold() ?? 10 }}
                                </td>

                                <!-- Last Movement -->
                                <td class="py-3.5 px-4 text-neutral-500 text-[11px]">
                                    @if ($item->last_movement_at)
                                        <div>{{ $item->last_movement_at->format('Y-m-d H:i') }}</div>
                                    @else
                                        <span class="text-neutral-400 italic">No movement recorded</span>
                                    @endif
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @can('manage', 'inventory')
                                            <button type="button"
                                                data-item="{{ json_encode([
                                                    'id' => $item->id,
                                                    'sku_id' => $item->product_sku_id,
                                                    'sku_code' => $item->sku?->sku_code,
                                                    'product_name' => $item->sku?->product?->name,
                                                    'on_hand_quantity' => $item->on_hand_quantity,
                                                    'reserved_quantity' => $item->reserved_quantity,
                                                    'available_quantity' => $item->available_quantity,
                                                    'allow_negative_stock' => (bool) ($item->allow_negative_stock ?? false),
                                                    'adjust_url' => route('admin.inventory.adjust', $item->sku),
                                                ]) }}"
                                                @click="openAdjustModal(JSON.parse($el.dataset.item))"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                                <x-icons.lucide name="lucide-sliders" class="w-3.5 h-3.5 text-neutral-500" />
                                                <span>Adjust</span>
                                            </button>
                                        @endcan
                                        <a href="{{ route('admin.inventory.movements.index', ['sku_id' => $item->product_sku_id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                            <x-icons.lucide name="lucide-history" class="w-3.5 h-3.5 text-neutral-500" />
                                            <span>History</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-box" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No stock balances found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting your search terms or active warehouse filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($items->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $items->links() }}
                </div>
            @endif
        </div>

        <!-- Stock Adjustment Modal -->
        <div x-show="adjustModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="closeAdjustModal()"
                    x-show="adjustModalOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-neutral-950/40 backdrop-blur-xs" aria-hidden="true"></div>

                <div x-show="adjustModalOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white border border-neutral-200 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <template x-if="activeItem">
                        <form :action="activeItem.adjust_url" method="POST" class="p-6 space-y-4">
                            @csrf
                            <input type="hidden" name="expected_on_hand" :value="activeItem.on_hand_quantity">

                            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                                <div>
                                    <h3 class="text-base font-bold text-neutral-900">Adjust Inventory Stock</h3>
                                    <p class="text-xs text-neutral-500 mt-0.5" x-text="`${activeItem.product_name} (${activeItem.sku_code})`"></p>
                                </div>
                                <button type="button" @click="closeAdjustModal()" class="text-neutral-400 hover:text-neutral-600">
                                    <x-icons.lucide name="lucide-x" class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Stale Balance Alert Banner -->
                            @error('expected_on_hand')
                                <div class="p-3.5 rounded-xl border border-amber-200 bg-amber-50 text-xs text-amber-800 flex items-start gap-2.5">
                                    <x-icons.lucide name="lucide-alert-triangle" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <span class="font-bold">Stale Inventory Warning:</span>
                                        <p class="mt-0.5">{{ $message }}</p>
                                    </div>
                                </div>
                            @enderror

                            <!-- New On-Hand Quantity Input -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">New Physical On-Hand Quantity</label>
                                <input type="number" name="new_on_hand" :min="activeItem?.allow_negative_stock ? undefined : 0" x-model.number="newOnHand" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                <div class="flex justify-between text-[11px] text-neutral-500 mt-1">
                                    <span>Current On-Hand: <strong x-text="activeItem.on_hand_quantity"></strong></span>
                                    <span>Delta: <strong :class="deltaOnHand >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formattedDelta"></strong></span>
                                </div>
                                @error('new_on_hand')
                                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- New Reserved Quantity Input -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">New Reserved Quantity</label>
                                <input type="number" name="new_reserved" min="0" x-model.number="newReserved" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 font-mono focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                <div class="flex justify-between text-[11px] text-neutral-500 mt-1">
                                    <span>Current Reserved: <strong x-text="activeItem.reserved_quantity"></strong></span>
                                    <span>Delta: <strong :class="(newReserved - activeItem.reserved_quantity) >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="`${(newReserved - activeItem.reserved_quantity) >= 0 ? '+' : ''}${newReserved - activeItem.reserved_quantity}`"></strong></span>
                                </div>
                                @error('new_reserved')
                                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Reason Code Selection -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">Adjustment Reason</label>
                                <select name="reason_code" x-model="reasonCode" required class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]">
                                    <option value="manual_adjustment">Manual Adjustment</option>
                                    <option value="inventory_correction">Stock Audit Correction</option>
                                    <option value="damaged_goods">Damaged Goods</option>
                                    <option value="inventory_loss">Inventory Loss</option>
                                    <option value="expired_stock">Expired Stock</option>
                                    <option value="theft">Stolen / Missing</option>
                                </select>
                                @error('reason_code')
                                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                    <span>Notes &amp; Audit Trail Justification</span>
                                    <span x-show="isNotesRequired" class="text-red-600 font-bold">*</span>
                                </label>
                                <textarea name="notes" x-model="notes" rows="2" :required="isNotesRequired" placeholder="Reason for stock adjustment..." class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)]"></textarea>
                                @error('notes')
                                    <p class="mt-1 text-xs text-red-600 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Bar -->
                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-neutral-200">
                                <button type="button" @click="closeAdjustModal()" class="px-4 py-2 text-xs font-semibold text-neutral-700 hover:text-neutral-900 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-xl transition-colors">Cancel</button>
                                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-[color:var(--color-brand-600)] hover:bg-[color:var(--color-brand-700)] rounded-xl shadow-xs transition-colors">Save Stock Adjustment</button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>

