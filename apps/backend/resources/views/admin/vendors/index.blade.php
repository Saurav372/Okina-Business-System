<x-layouts.admin title="Vendors">
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between border-b border-neutral-200 pb-3 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Vendors</h1>
                <p class="text-xs text-neutral-500 mt-1">Manage your supplier directory, contact details, and vendor status.</p>
            </div>
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

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Vendors -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Total Vendors</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-700">
                        <x-icons.lucide name="lucide-building-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-900">{{ number_format($totalVendors) }}</div>
                <div class="mt-1 text-xs text-neutral-500">All registered suppliers</div>
            </div>

            <!-- Active -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Active</span>
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100">
                        <x-icons.lucide name="lucide-check-circle-2" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ number_format($activeVendors) }}</div>
                <div class="mt-1 text-xs text-emerald-700/80">Available for procurement</div>
            </div>

            <!-- Inactive -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider">Inactive</span>
                    <div class="p-2.5 rounded-xl bg-neutral-100 text-neutral-500 border border-neutral-200">
                        <x-icons.lucide name="lucide-pause-circle" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-neutral-600">{{ number_format($inactiveVendors) }}</div>
                <div class="mt-1 text-xs text-neutral-400">Temporarily paused</div>
            </div>

            <!-- Blocked -->
            <div class="bg-white border border-neutral-200 rounded-2xl p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Blocked</span>
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600 border border-red-100">
                        <x-icons.lucide name="lucide-ban" class="w-4 h-4" />
                    </div>
                </div>
                <div class="mt-3 text-2xl sm:text-3xl font-extrabold text-red-600">{{ number_format($blockedVendors) }}</div>
                <div class="mt-1 text-xs text-red-700/80">Restricted from orders</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 sm:p-5 shadow-xs">
            <form method="GET" action="{{ route('admin.vendors.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Search -->
                <div class="sm:col-span-2 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-neutral-400">
                        <x-icons.lucide name="lucide-search" class="w-4 h-4" />
                    </div>
                    <input type="text" name="search" value="{{ $filters->search }}"
                           placeholder="Search vendor name, code, GSTIN, email, phone..."
                           class="w-full pl-9 pr-4 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                </div>

                <!-- Status & Submit -->
                <div class="flex items-center gap-2">
                    <select name="status" class="w-full px-3.5 py-2 border border-neutral-300 rounded-xl text-xs text-neutral-800 bg-white focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-colors">
                        <option value="all" {{ $filters->status === '' ? 'selected' : '' }}>All Statuses</option>
                        @foreach ($statuses as $st)
                            <option value="{{ $st->value }}" {{ $filters->status === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-5 py-2 bg-neutral-900 text-white rounded-xl text-xs font-bold hover:bg-neutral-800 transition-colors shrink-0">Filter</button>
                </div>
            </form>
        </div>

        <!-- Vendors Table -->
        <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-neutral-800">
                    <thead class="bg-neutral-50/80 text-[10px] uppercase font-bold text-neutral-400 tracking-wider border-b border-neutral-200">
                        <tr>
                            <th class="py-3.5 px-4 font-semibold">Vendor</th>
                            <th class="py-3.5 px-4 font-semibold">Status</th>
                            <th class="py-3.5 px-4 font-semibold">Contact</th>
                            <th class="py-3.5 px-4 font-semibold">GSTIN</th>
                            <th class="py-3.5 px-4 font-semibold">Payment Terms</th>
                            <th class="py-3.5 px-4 font-semibold text-right">Purchase Orders</th>
                            <th class="py-3.5 px-4 font-semibold">Created</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @forelse ($vendors as $vendor)
                            <tr class="hover:bg-neutral-50/60 transition-colors">
                                <!-- Vendor Name & Code -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-neutral-900">{{ $vendor->name }}</div>
                                    <span class="inline-flex px-2 py-0.5 bg-neutral-100 text-neutral-700 rounded text-[11px] font-mono font-medium border border-neutral-200 mt-1">
                                        {{ $vendor->vendor_code }}
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $vendor->status->badgeClass() }}">
                                        {{ $vendor->status->label() }}
                                    </span>
                                </td>

                                <!-- Contact -->
                                <td class="py-3.5 px-4">
                                    @if ($vendor->contact_name)
                                        <div class="font-medium text-neutral-800">{{ $vendor->contact_name }}</div>
                                    @endif
                                    @if ($vendor->email)
                                        <div class="text-neutral-500 text-[11px]">{{ $vendor->email }}</div>
                                    @endif
                                    @if ($vendor->phone)
                                        <div class="text-neutral-400 text-[11px]">{{ $vendor->phone }}</div>
                                    @endif
                                    @if (!$vendor->contact_name && !$vendor->email && !$vendor->phone)
                                        <span class="text-neutral-300 italic">—</span>
                                    @endif
                                </td>

                                <!-- GSTIN -->
                                <td class="py-3.5 px-4 font-mono text-[11px] text-neutral-600">
                                    {{ $vendor->gstin ?? '—' }}
                                </td>

                                <!-- Payment Terms -->
                                <td class="py-3.5 px-4 text-neutral-500">
                                    {{ $vendor->payment_terms ?? '—' }}
                                </td>

                                <!-- Purchase Orders Count -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-neutral-800">
                                    <a href="{{ route('admin.purchases.index') }}?vendor_id={{ $vendor->id }}"
                                       class="hover:text-[color:var(--color-brand-600)] transition-colors">
                                        {{ number_format($vendor->purchase_orders_count) }}
                                    </a>
                                </td>

                                <!-- Created At -->
                                <td class="py-3.5 px-4 text-neutral-400 text-[11px]">
                                    {{ $vendor->created_at?->format('Y-m-d') }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('admin.purchases.index') }}?vendor_id={{ $vendor->id }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 border border-neutral-300 rounded-lg shadow-2xs transition-colors">
                                            <x-icons.lucide name="lucide-shopping-bag" class="w-3.5 h-3.5 text-neutral-500" />
                                            <span>Orders</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-neutral-400">
                                    <x-icons.lucide name="lucide-building-2" class="w-10 h-10 mx-auto text-neutral-300 mb-3" />
                                    <p class="text-sm font-semibold text-neutral-700">No vendors found</p>
                                    <p class="text-xs text-neutral-400 mt-1">Try adjusting your search or status filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($vendors->hasPages())
                <div class="p-4 border-t border-neutral-200">
                    {{ $vendors->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.admin>
