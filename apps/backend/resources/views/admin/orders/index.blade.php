<x-layouts.admin title="Sales Orders">
    <x-slot:header>
        @if(Route::has('admin.sales_orders.create'))
            <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
                New Sales Order
            </a>
        @endif
    </x-slot:header>

    <!-- Scopes (Tabs Toolbar) -->
    <div class="flex items-center gap-2 border-b border-neutral-200 pb-4 mb-6 overflow-x-auto flex-nowrap scrollbar-none">
        @foreach($scopes as $s)
            @php
                $activeScope = $activeFilters['scope'] ?? 'all';
                $isCurrent = $activeScope === $s['key'];
            @endphp
            <a 
                href="{{ route('admin.orders.index', array_merge($activeFilters, ['scope' => $s['key'], 'page' => 1])) }}" 
                class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors shrink-0
                    {{ $isCurrent 
                        ? 'bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)]' 
                        : 'text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50' }}"
            >
                {{ $s['label'] }}
            </a>
        @endforeach
    </div>

    <!-- Filters & Search Form -->
    <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs mb-6">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
            <!-- Hidden inputs to preserve sorting and active scope -->
            <input type="hidden" name="scope" value="{{ $activeFilters['scope'] ?? 'all' }}">
            @if(!empty($activeFilters['sort']))
                <input type="hidden" name="sort" value="{{ $activeFilters['sort'] }}">
            @endif
            @if(!empty($activeFilters['direction']))
                <input type="hidden" name="direction" value="{{ $activeFilters['direction'] }}">
            @endif

            <!-- Search input -->
            <div class="space-y-1 sm:col-span-2 md:col-span-3 lg:col-span-2">
                <label class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Search</label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $activeFilters['search'] ?? '' }}" 
                    placeholder="Order No, Customer, Phone, Email..."
                    class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 placeholder-neutral-400"
                >
            </div>

            <!-- Order Source -->
            <div class="space-y-1">
                <label class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Source</label>
                <select 
                    name="order_source"
                    class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 bg-white"
                >
                    <option value="">All Sources</option>
                    @foreach(config('orders.sources', []) as $val => $lbl)
                        <option value="{{ $val }}" {{ (string) ($activeFilters['order_source'] ?? '') === (string) $val ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Design Approved -->
            <div class="space-y-1">
                <label class="text-xs font-bold text-neutral-400 uppercase tracking-wider">Design Approval</label>
                <select 
                    name="design_approved"
                    class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 bg-white"
                >
                    <option value="">All Designs</option>
                    <option value="1" {{ (string) ($activeFilters['design_approved'] ?? '') === '1' ? 'selected' : '' }}>Approved</option>
                    <option value="0" {{ (string) ($activeFilters['design_approved'] ?? '') === '0' ? 'selected' : '' }}>Not Approved</option>
                </select>
            </div>

            <!-- Placed From -->
            <div class="space-y-1">
                <label class="text-xs font-bold text-neutral-400 uppercase tracking-wider">From Date</label>
                <input 
                    type="date" 
                    name="placed_from" 
                    value="{{ $activeFilters['placed_from'] ?? '' }}"
                    class="w-full px-3 py-1.5 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800"
                >
            </div>

            <!-- Placed To -->
            <div class="space-y-1">
                <label class="text-xs font-bold text-neutral-400 uppercase tracking-wider">To Date</label>
                <input 
                    type="date" 
                    name="placed_to" 
                    value="{{ $activeFilters['placed_to'] ?? '' }}"
                    class="w-full px-3 py-1.5 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800"
                >
            </div>

            <!-- Apply & Clear Buttons -->
            <div class="flex gap-2 lg:col-span-6 justify-end mt-2">
                @php
                    $hasFilters = !empty($activeFilters['search']) || !empty($activeFilters['order_source']) || isset($activeFilters['design_approved']) && $activeFilters['design_approved'] !== '' || !empty($activeFilters['placed_from']) || !empty($activeFilters['placed_to']);
                @endphp
                @if($hasFilters)
                    <a href="{{ route('admin.orders.index', ['scope' => $activeFilters['scope'] ?? 'all']) }}" class="px-4 py-2 bg-neutral-100 text-neutral-700 rounded-xl text-xs font-bold hover:bg-neutral-200 transition-colors text-center">
                        Clear Filters
                    </a>
                @endif
                <button type="submit" class="px-5 py-2 bg-neutral-800 text-white rounded-xl text-xs font-bold hover:bg-neutral-900 transition-colors focus-visible:outline-none">
                    Filter Orders
                </button>
            </div>
        </form>
    </div>

    <!-- Orders Table Container -->
    <x-table>
        <x-table.head>
            <tr>
                <th scope="col" class="w-10 px-4 py-3">
                    <input type="checkbox" disabled class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-not-allowed opacity-50" title="Bulk actions are disabled in V1">
                </th>
                <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'public_id' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.orders.index', array_merge($activeFilters, ['sort' => 'public_id', 'direction' => ($activeFilters['sort'] ?? '') === 'public_id' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                    Order No
                </x-table.heading>
                <x-table.heading>Customer</x-table.heading>
                <x-table.heading>Source</x-table.heading>
                <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'status' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.orders.index', array_merge($activeFilters, ['sort' => 'status', 'direction' => ($activeFilters['sort'] ?? '') === 'status' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                    Status
                </x-table.heading>
                <x-table.heading>Payment</x-table.heading>
                <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'total_amount_minor' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.orders.index', array_merge($activeFilters, ['sort' => 'total_amount_minor', 'direction' => ($activeFilters['sort'] ?? '') === 'total_amount_minor' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                    Total
                </x-table.heading>
                <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'placed_at' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.orders.index', array_merge($activeFilters, ['sort' => 'placed_at', 'direction' => ($activeFilters['sort'] ?? '') === 'placed_at' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                    Created
                </x-table.heading>
                <x-table.heading align="right">Actions</x-table.heading>
            </tr>
        </x-table.head>
        <x-table.body class="divide-y divide-neutral-100 text-sm bg-white">
            @forelse($orders as $o)
                @php
                    // Map order resource parameters dynamically for Blade usage
                    $paidSum = (int) ($o->payments_sum_amount_minor ?? 0);
                    $totalAmount = (int) $o->total_amount_minor;

                    // Payment status
                    if ($paidSum >= $totalAmount && $totalAmount > 0) {
                        $payStatus = 'Paid';
                        $payIntent = 'success';
                    } elseif ($paidSum > 0) {
                        $payStatus = 'Partially Paid';
                        $payIntent = 'warning';
                    } else {
                        $payStatus = 'Unpaid';
                        $payIntent = 'danger';
                    }

                    // Order status intent map
                    $statusIntent = match ($o->status) {
                        'pending_payment' => 'warning',
                        'confirmed' => 'primary',
                        'in_production' => 'info',
                        'ready_to_ship' => 'warning',
                        'shipped' => 'warning',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'success',
                        default => 'neutral',
                    };

                    // Label mapping
                    $sourcesConfig = config('orders.sources', []);
                    $sourceLabel = $sourcesConfig[$o->order_source] ?? ucfirst($o->order_source);

                    // Customer details
                    $custName = data_get($o->customer_snapshot, 'name', 'N/A');
                    $custPhone = data_get($o->customer_snapshot, 'phone', 'N/A');
                @endphp
                <x-table.row>
                    <x-table.cell>
                        <input type="checkbox" disabled class="rounded border-neutral-300 opacity-50 cursor-not-allowed">
                    </x-table.cell>
                    <x-table.cell class="font-mono font-bold text-[color:var(--color-brand-600)]">
                        <a href="{{ route('admin.orders.show', ['order' => $o->public_id]) }}" class="hover:underline focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] rounded">
                            {{ $o->public_id }}
                        </a>
                    </x-table.cell>
                    <x-table.cell>
                        <div class="font-semibold text-neutral-800">{{ $custName }}</div>
                        <div class="text-xs text-neutral-400 font-mono">{{ $custPhone }}</div>
                    </x-table.cell>
                    <x-table.cell class="text-xs font-medium text-neutral-600">
                        {{ $sourceLabel }}
                    </x-table.cell>
                    <x-table.cell>
                        <x-badge :intent="$statusIntent" size="sm">
                            {{ str_replace('_', ' ', $o->status) }}
                        </x-badge>
                    </x-table.cell>
                    <x-table.cell>
                        <x-badge :intent="$payIntent" size="sm">
                            {{ $payStatus }}
                        </x-badge>
                    </x-table.cell>
                    <x-table.cell class="font-mono font-bold text-neutral-700">
                        ₹{{ number_format($totalAmount / 100, 2) }}
                    </x-table.cell>
                    <x-table.cell class="text-neutral-500 text-xs font-mono">
                        {{ $o->placed_at ? $o->placed_at->format('Y-m-d H:i') : ($o->created_at ? $o->created_at->format('Y-m-d H:i') : 'N/A') }}
                    </x-table.cell>
                    <x-table.cell align="right">
                        <div class="inline-flex items-center gap-1.5">
                            <a 
                                href="{{ route('admin.orders.show', ['order' => $o->public_id]) }}" 
                                class="inline-flex items-center justify-center p-1.5 rounded-lg text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors"
                                title="View details"
                            >
                                <x-icons.lucide name="lucide-eye" class="w-4 h-4" />
                            </a>

                            @if($o->status !== 'pending_payment' && $o->status !== 'cancelled' && Route::has('admin.orders.pdf.download'))
                                <a 
                                    href="{{ route('admin.orders.pdf.download', ['order' => $o->public_id]) }}" 
                                    class="inline-flex items-center justify-center p-1.5 rounded-lg text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50 transition-colors"
                                    title="Download Order PDF"
                                >
                                    <x-icons.lucide name="lucide-file-text" class="w-4 h-4" />
                                </a>
                            @else
                                <button 
                                    disabled 
                                    class="inline-flex items-center justify-center p-1.5 rounded-lg text-neutral-300 cursor-not-allowed opacity-50"
                                    title="PDF unavailable"
                                >
                                    <x-icons.lucide name="lucide-file-text" class="w-4 h-4" />
                                </button>
                            @endif

                            <button 
                                disabled
                                class="inline-flex items-center justify-center p-1.5 rounded-lg text-neutral-300 cursor-not-allowed opacity-50"
                                title="More actions (Reserved for future)"
                            >
                                <x-icons.lucide name="lucide-more-horizontal" class="w-4 h-4" />
                            </button>
                        </div>
                    </x-table.cell>
                </x-table.row>
            @empty
                <x-table.row>
                    <x-table.cell colspan="9" class="py-0">
                        <x-empty-state 
                            title="No orders match the selected filters." 
                            description="Adjust or reset your query filters to find the orders you are looking for."
                            size="md"
                        >
                            <x-slot:icon>
                                <x-icons.lucide name="lucide-search" class="w-8 h-8 text-neutral-400" />
                            </x-slot:icon>
                            <x-slot:actions>
                                <div class="flex gap-3 justify-center">
                                    @if($hasFilters)
                                        <a href="{{ route('admin.orders.index', ['scope' => $activeFilters['scope'] ?? 'all']) }}" class="px-4 py-2 bg-neutral-100 text-neutral-700 font-bold rounded-xl text-xs hover:bg-neutral-200 transition-colors">
                                            Clear Filters
                                        </a>
                                    @endif
                                    @if(Route::has('admin.sales_orders.create'))
                                        <a href="{{ route('admin.sales_orders.create') }}" class="px-4 py-2 bg-[color:var(--color-brand-600)] text-white font-bold rounded-xl text-xs hover:bg-[color:var(--color-brand-700)] transition-colors">
                                            Create Order
                                        </a>
                                    @endif
                                </div>
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.cell>
                </x-table.row>
            @endforelse
        </x-table.body>
        <x-slot:footer>
            <x-table.pagination :paginator="$orders" />
        </x-slot:footer>
    </x-table>
</x-layouts.admin>
