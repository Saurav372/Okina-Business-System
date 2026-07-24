<x-layouts.admin title="Purchase Orders">
    <div class="space-y-6">

        <!-- Header & Action -->
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Purchase Orders</h1>
                <p class="text-xs text-neutral-500 mt-1">Manage vendor procurement, stock-in receiving, and order receipts.</p>
            </div>
            @can('create', \App\Models\VendorOrder::class)
                <a href="{{ route('admin.purchases.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors shadow-xs">
                    <x-icons.lucide name="lucide-plus" class="w-4 h-4" />
                    <span>Create Purchase Order</span>
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
            <!-- Total POs -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Purchase Orders</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-shopping-bag" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->totalOrdersCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">In active filter scope</div>
            </div>

            <!-- Total PO Value -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Total Procurement Value</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-indian-rupee" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">₹{{ number_format($metrics->totalPurchaseValueMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Committed order value</div>
            </div>

            <!-- Outstanding Balance -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Outstanding Vendor Payable</span>
                    <div class="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100">
                        <x-icons.lucide name="lucide-wallet" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-amber-600">₹{{ number_format($metrics->unpaidLiabilityMinor / 100, 2) }}</div>
                <div class="mt-1 text-xs text-amber-700/80">Pending payment to vendors</div>
            </div>

            <!-- Active Pending POs -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Active Pending POs</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-box" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($metrics->activePendingCount) }}</div>
                <div class="mt-1 text-xs text-neutral-500">Awaiting goods receipt</div>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.purchases.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <!-- Search Input -->
                <div class="lg:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search ?? '' }}" placeholder="Search PO public ID, vendor name, SKU, or barcode..." class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Vendor Selector -->
                <div>
                    <select name="vendor_id" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->vendorId ?? '') === '' ? 'selected' : '' }}>All Vendors</option>
                        @foreach ($vendors as $v)
                            <option value="{{ $v->id }}" {{ ($filters->vendorId ?? '') == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Selector -->
                <div>
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ ($filters->status ?? '') === '' ? 'selected' : '' }}>All Order Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ ($filters->status ?? '') === $st->value ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $st->value)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors">Filter</button>
                </div>
            </form>
        </div>

        <!-- Purchase Orders Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">PO Number</th>
                            <th class="py-3.5 px-4 font-semibold">Vendor</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Order Total</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Order Date</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($orders as $po)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- PO Number -->
                                <td class="py-3.5 px-4 font-mono font-bold text-[color:var(--color-brand-600)]">
                                    <a href="{{ route('admin.purchases.show', $po->public_id) }}" class="hover:underline">
                                        {{ $po->public_id }}
                                    </a>
                                </td>

                                <!-- Vendor -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $po->vendor?->name ?? 'Unknown Vendor' }}</div>
                                    <div class="text-[11px] font-mono text-neutral-400 mt-0.5">{{ $po->vendor?->vendor_code }}</div>
                                </td>

                                <!-- Order Total -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-900">
                                    ₹{{ number_format($po->total_amount_minor / 100, 2) }}
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $po->status->badgeClass() }}">
                                        {{ $po->status->label() }}
                                    </span>
                                </td>

                                <!-- Date -->
                                <td class="py-3.5 px-4 text-xs text-neutral-500 font-mono">
                                    {{ $po->ordered_at ? $po->ordered_at->format('Y-m-d') : '—' }}
                                </td>

                                <!-- Action -->
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('admin.purchases.show', $po->public_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                        <x-icons.lucide name="lucide-eye" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-shopping-bag" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No purchase orders found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting filter parameters or create a new purchase order.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Server-Side Pagination -->
            @if ($orders->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
