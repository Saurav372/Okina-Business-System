<x-layouts.admin title="Audit Trail Movements">
    <div class="space-y-6">

        <!-- Scopes (Tabs Toolbar) -->
        <div class="flex items-center gap-1.5 border-b border-neutral-200 pb-3 mb-4 overflow-x-auto flex-nowrap scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
            <a href="{{ route('admin.inventory.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                Stock Balances
            </a>
            <a href="{{ route('admin.inventory.movements.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)] shrink-0">
                Audit Trail Log
            </a>
            <a href="{{ route('admin.inventory.transfers.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                Warehouse Transfers
            </a>
        </div>

        <!-- KPI Metrics Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Movements -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Audit Logged</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-activity" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->totalMovementsCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">In active filter scope</div>
            </div>

            <!-- Inbound Movements -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Stock-In Inbound</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-arrow-down-right" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">+{{ number_format($metrics->totalStockInQuantity) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Received / Adjustments</div>
            </div>

            <!-- Outbound Movements -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Stock-Out Outbound</span>
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600 border border-red-100">
                        <x-icons.lucide name="lucide-arrow-up-right" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-red-600">-{{ number_format($metrics->totalStockOutQuantity) }}</div>
                <div class="mt-1 text-xs text-red-700/80">Fulfilled / Dispatched</div>
            </div>

            <!-- Net Flow -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Net Quantity Delta</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-scale" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold {{ $metrics->netQuantityDelta >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $metrics->netQuantityDelta >= 0 ? '+'.number_format($metrics->netQuantityDelta) : number_format($metrics->netQuantityDelta) }}
                </div>
                <div class="mt-1 text-xs text-neutral-500">Inbound vs Outbound</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.inventory.movements.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search ?? '' }}" placeholder="Search SKU, product, barcode, or idempotency key..." class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Direction Selector -->
                <div>
                    <select name="direction" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->direction?->value ?? '') === '' ? 'selected' : '' }}>All Directions</option>
                        @foreach ($directions as $dir)
                            <option value="{{ $dir->value }}" {{ ($filters->direction?->value ?? '') === $dir->value ? 'selected' : '' }}>{{ $dir->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reason Selector -->
                <div>
                    <select name="reason_code" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->reasonCode?->value ?? '') === '' ? 'selected' : '' }}>All Reason Codes</option>
                        @foreach ($reasons as $rs)
                            <option value="{{ $rs->value }}" {{ ($filters->reasonCode?->value ?? '') === $rs->value ? 'selected' : '' }}>{{ $rs->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit & Export -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors">Filter</button>
                    <a href="{{ route('admin.inventory.movements.export', $filters->toArray()) }}" class="px-3.5 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 transition-colors shrink-0 flex items-center gap-1.5">
                        <x-icons.lucide name="lucide-download" class="w-4 h-4 text-neutral-500" />
                        <span>CSV</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Movements Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Movement Timestamp</th>
                            <th class="py-3.5 px-4 font-semibold">SKU &amp; Product</th>
                            <th class="py-3.5 px-4 font-semibold">Type &amp; Reason</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Quantity Delta</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Before $\rightarrow$ After</th>
                            <th class="py-3.5 px-4 font-semibold">Performed By</th>
                            <th class="py-3.5 px-4 font-semibold">Notes &amp; Reference</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($movements as $m)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Timestamp -->
                                <td class="py-3.5 px-4 font-mono text-neutral-600 whitespace-nowrap">
                                    {{ $m->created_at ? $m->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                                </td>

                                <!-- SKU & Product -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $m->sku?->product?->name ?? 'Unknown Product' }}</div>
                                    <div class="font-mono text-[11px] text-neutral-500 mt-0.5">{{ $m->sku?->sku_code }}</div>
                                </td>

                                <!-- Type & Reason -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $m->direction->badgeClass() }}">
                                        {{ $m->reason_code?->label() ?? str_replace('_', ' ', $m->movement_type->value) }}
                                    </span>
                                </td>

                                <!-- Quantity Delta -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-sm">
                                    <span class="{{ $m->direction === \App\Enums\InventoryDirection::INBOUND ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $m->direction === \App\Enums\InventoryDirection::INBOUND ? '+'.number_format($m->quantity) : '-'.number_format($m->quantity) }}
                                    </span>
                                </td>

                                <!-- Before -> After -->
                                <td class="py-3.5 px-4 text-right font-mono text-xs text-neutral-500 whitespace-nowrap">
                                    {{ number_format($m->quantity_before) }} <span class="text-neutral-300">→</span> <strong class="text-neutral-900">{{ number_format($m->quantity_after) }}</strong>
                                </td>

                                <!-- Performed By -->
                                <td class="py-3.5 px-4 text-neutral-700 font-medium whitespace-nowrap">
                                    {{ $m->creator?->name ?? 'System' }}
                                </td>

                                <!-- Notes & Reference -->
                                <td class="py-3.5 px-4 text-neutral-500 text-xs">
                                    <div>{{ $m->notes ?? 'N/A' }}</div>
                                    @if ($m->idempotency_key)
                                        <div class="text-[10px] font-mono text-neutral-400 mt-0.5">Key: {{ $m->idempotency_key }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-activity" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No inventory movements recorded</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting filter parameters or search terms.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($movements->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
