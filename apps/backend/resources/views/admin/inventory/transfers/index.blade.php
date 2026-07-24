<x-layouts.admin title="Warehouse Location Transfers">
    <div class="space-y-6">

        <!-- Scopes (Tabs Toolbar) -->
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
            <div class="flex items-center gap-1.5 overflow-x-auto flex-nowrap scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
                <a href="{{ route('admin.inventory.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                    Stock Balances
                </a>
                <a href="{{ route('admin.inventory.movements.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors shrink-0">
                    Audit Trail Log
                </a>
                <a href="{{ route('admin.inventory.transfers.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)] shrink-0">
                    Warehouse Transfers
                </a>
            </div>

            @can('manage', 'inventory')
                <a href="{{ route('admin.inventory.transfers.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors shadow-xs">
                    <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                    <span>Initiate Transfer</span>
                </a>
            @endcan
        </div>

        <!-- Session Flash Messages -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center gap-3 shadow-xs">
                <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4 flex-shrink-0 text-emerald-600" />
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- KPI Metrics Grid (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Transfers -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Transfers</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-arrow-left-right" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->totalTransfersCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">In active filter scope</div>
            </div>

            <!-- Active In Transit -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">In Transit Transfers</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-truck" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-amber-600">{{ number_format($metrics->activeInTransitCount) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Shipped &amp; awaiting receipt</div>
            </div>

            <!-- Total Transferred Volume -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Transferred Volume</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-boxes" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->totalTransferredUnits) }} <span class="text-xs font-normal text-neutral-500">Units</span></div>
                <div class="mt-1 text-xs text-neutral-500">In-transit + completed stock</div>
            </div>

            <!-- Completed Transfers -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Completed Transfers</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-check-check" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($metrics->completedTransfersCount) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Successfully received</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.inventory.transfers.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search ?? '' }}" placeholder="Search TRF code, SKU, or product name..." class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Source Location Selector -->
                <div>
                    <select name="source_location" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->sourceLocation?->value ?? '') === '' ? 'selected' : '' }}>From: All Source Locations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->value }}" {{ ($filters->sourceLocation?->value ?? '') === $loc->value ? 'selected' : '' }}>{{ $loc->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Destination Location Selector -->
                <div>
                    <select name="destination_location" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->destinationLocation?->value ?? '') === '' ? 'selected' : '' }}>To: All Destinations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->value }}" {{ ($filters->destinationLocation?->value ?? '') === $loc->value ? 'selected' : '' }}>{{ $loc->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Selector & Submit -->
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->status?->value ?? '') === '' ? 'selected' : '' }}>All Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ ($filters->status?->value ?? '') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shrink-0">Filter</button>
                </div>
            </form>
        </div>

        <!-- Transfers Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Transfer Code</th>
                            <th class="py-3.5 px-4 font-semibold">SKU &amp; Product</th>
                            <th class="py-3.5 px-4 font-semibold">Source $\rightarrow$ Destination</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Quantity</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Initiated Date</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($transfers as $trf)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Transfer Code -->
                                <td class="py-3.5 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    <a href="{{ route('admin.inventory.transfers.show', $trf) }}" class="hover:underline">
                                        {{ $trf->transfer_code }}
                                    </a>
                                </td>

                                <!-- SKU & Product -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $trf->productSku?->product?->name ?? 'Unknown Product' }}</div>
                                    <div class="text-[11px] font-mono text-neutral-500 mt-0.5">{{ $trf->productSku?->sku_code }}</div>
                                </td>

                                <!-- Source -> Destination -->
                                <td class="py-3.5 px-4 text-xs font-medium">
                                    <span class="text-neutral-700">{{ $trf->source_location->label() }}</span>
                                    <span class="text-neutral-400 px-1 font-bold">→</span>
                                    <span class="text-neutral-900 font-semibold">{{ $trf->destination_location->label() }}</span>
                                </td>

                                <!-- Quantity -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-900">
                                    {{ number_format($trf->quantity) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    @if ($trf->status->value === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Completed</span>
                                    @elseif ($trf->status->value === 'in_transit')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">In Transit</span>
                                    @elseif ($trf->status->value === 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">Cancelled</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-neutral-100 text-neutral-700 border border-neutral-200">Draft</span>
                                    @endif
                                </td>

                                <!-- Initiated Date -->
                                <td class="py-3.5 px-4 text-xs text-neutral-500 font-mono">
                                    {{ $trf->created_at ? $trf->created_at->format('Y-m-d H:i') : 'N/A' }}
                                </td>

                                <!-- Action -->
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('admin.inventory.transfers.show', $trf) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                        <x-icons.lucide name="lucide-eye" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-arrow-left-right" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No warehouse transfers found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting filter criteria or initiate a new location transfer.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($transfers->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
