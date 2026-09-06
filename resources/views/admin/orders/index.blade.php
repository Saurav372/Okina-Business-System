<x-layouts.admin title="Sales Orders">
    <x-slot:header>
        @if(Route::has('admin.sales_orders.create'))
            <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
                New Sales Order
            </a>
        @endif
    </x-slot:header>

    <div 
        x-data="{
            selectedOrders: [],
            selectedBulkStatus: 'confirmed',
            bulkCancelReason: 'customer_request',
            bulkCancelNote: '',
            pageOrderIds: {{ json_encode($orders->pluck('public_id')->all()) }},
            get allSelected() {
                return this.pageOrderIds.length > 0 && this.pageOrderIds.every(id => this.selectedOrders.includes(id));
            },
            get someSelected() {
                return this.selectedOrders.length > 0 && !this.allSelected;
            },
            toggleAll() {
                if (this.allSelected) {
                    this.selectedOrders = this.selectedOrders.filter(id => !this.pageOrderIds.includes(id));
                } else {
                    this.pageOrderIds.forEach(id => {
                        if (!this.selectedOrders.includes(id)) {
                            this.selectedOrders.push(id);
                        }
                    });
                }
            },
            submitBulkAction(action, targetStatus = null) {
                const form = document.getElementById('bulk-action-form');
                form.action = '{{ route('admin.orders.bulk') }}';
                form.target = '_self';
                document.getElementById('bulk-action-input').value = action;
                document.getElementById('bulk-target-status-input').value = targetStatus || '';
                form.submit();
            },
            submitBulkCancel() {
                const form = document.getElementById('bulk-cancel-form');
                document.getElementById('bulk-cancel-reason-input').value = this.bulkCancelReason;
                document.getElementById('bulk-cancel-note-input').value = this.bulkCancelNote;
                form.submit();
            },
            submitPackingSlips() {
                const form = document.getElementById('bulk-action-form');
                form.action = '{{ route('admin.orders.bulk.packing_slips') }}';
                form.target = '_blank';
                form.submit();
            },
            submitExportManifest() {
                const form = document.getElementById('bulk-action-form');
                form.action = '{{ route('admin.orders.bulk.manifest') }}';
                form.target = '_self';
                form.submit();
            }
        }"
    >
    @php
        $hasFilters = !empty($activeFilters['search']) || !empty($activeFilters['order_source']) || isset($activeFilters['design_approved']) && $activeFilters['design_approved'] !== '' || !empty($activeFilters['placed_from']) || !empty($activeFilters['placed_to']);
    @endphp

    <!-- Scopes (Tabs Toolbar) - Horizontally Scrollable & Compact Spacing -->
    <div class="flex items-center gap-1.5 border-b border-neutral-200 pb-3 mb-4 overflow-x-auto flex-nowrap scrollbar-none" style="scrollbar-width: none; -ms-overflow-style: none;">
        @foreach($scopes as $s)
            @php
                $activeScope = $activeFilters['scope'] ?? 'all';
                $isCurrent = $activeScope === $s['key'];
            @endphp
            <a 
                href="{{ route('admin.orders.index', array_merge($activeFilters, ['scope' => $s['key'], 'page' => 1])) }}" 
                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shrink-0
                    {{ $isCurrent 
                        ? 'bg-[color:var(--color-brand-50)] text-[color:var(--color-brand-700)] border border-[color:var(--color-brand-200)]' 
                        : 'text-neutral-500 hover:text-neutral-800 hover:bg-neutral-50' }}"
            >
                {{ $s['label'] }}
            </a>
        @endforeach
    </div>

    <!-- Filters & Search Form - Collapsible on Mobile -->
    <div 
        x-data="{ 
            showFilters: {{ $hasFilters ? 'true' : 'false' }},
            isMobile: window.innerWidth < 1024,
            init() {
                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 1024;
                });
            }
        }"
        class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 sm:p-5 shadow-xs mb-4"
    >
        <form method="GET" action="{{ route('admin.orders.index') }}">
            <!-- Hidden inputs to preserve sorting and active scope -->
            <input type="hidden" name="scope" value="{{ $activeFilters['scope'] ?? 'all' }}">
            @if(!empty($activeFilters['sort']))
                <input type="hidden" name="sort" value="{{ $activeFilters['sort'] }}">
            @endif
            @if(!empty($activeFilters['direction']))
                <input type="hidden" name="direction" value="{{ $activeFilters['direction'] }}">
            @endif

            <!-- Main Search Row -->
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                <!-- Search Input -->
                <div class="flex-1 space-y-1">
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Search Orders</label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $activeFilters['search'] ?? '' }}" 
                            placeholder="Order No, Customer, Phone, Email..."
                            @input.debounce.400ms="$el.form.submit()"
                            x-init="if ($el.value) { $el.focus(); $el.setSelectionRange($el.value.length, $el.value.length); }"
                            class="w-full pl-4 @if(!empty($activeFilters['search'])) pr-9 @else pr-4 @endif py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 placeholder-neutral-400"
                        >
                        @if(!empty($activeFilters['search']))
                            <a 
                                href="{{ route('admin.orders.index', array_merge(request()->except('search', 'page'))) }}"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600 p-0.5 rounded-full hover:bg-neutral-100 transition-colors"
                                title="Clear search"
                            >
                                <x-icons.lucide name="lucide-x" class="w-3.5 h-3.5" />
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Mobile Toggle button & Desktop direct Apply Button -->
                <div class="flex gap-2 shrink-0">
                    <button 
                        type="button" 
                        @click="showFilters = !showFilters"
                        class="lg:hidden flex-1 sm:flex-none px-4 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-neutral-50 hover:bg-neutral-100 flex items-center justify-center gap-1.5 whitespace-nowrap"
                    >
                        <x-icons.lucide name="lucide-sliders-horizontal" class="w-4 h-4 text-neutral-500" />
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'">Filters</span>
                    </button>

                    <!-- Hidden on Mobile, Shown Inline on Desktop -->
                    <button type="submit" class="hidden lg:inline-flex px-5 py-2 bg-neutral-800 text-white rounded-xl text-xs font-bold hover:bg-neutral-900 transition-colors focus-visible:outline-none">
                        Filter
                    </button>
                </div>
            </div>

            <!-- Collapsible Advanced Filters Section -->
            <div 
                x-show="!isMobile || showFilters" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 pt-4 border-t border-neutral-100"
                :class="{ 'hidden': isMobile && !showFilters }"
            >
                <!-- Order Source -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Source</label>
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
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Design Approval</label>
                    <select 
                        name="design_approved"
                        class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 bg-white"
                    >
                        <option value="">All Designs</option>
                        <option value="1" {{ (string) ($activeFilters['design_approved'] ?? '') === '1' ? 'selected' : '' }}>Approved</option>
                        <option value="0" {{ (string) ($activeFilters['design_approved'] ?? '') === '0' ? 'selected' : '' }}>Not Approved</option>
                    </select>
                </div>

                <!-- Date Range - Collapsible Side by Side -->
                <div class="sm:col-span-2 grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">From Date</label>
                        <input 
                            type="date" 
                            name="placed_from" 
                            value="{{ $activeFilters['placed_from'] ?? '' }}"
                            class="w-full px-3 py-1.5 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800"
                        >
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">To Date</label>
                        <input 
                            type="date" 
                            name="placed_to" 
                            value="{{ $activeFilters['placed_to'] ?? '' }}"
                            class="w-full px-3 py-1.5 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800"
                        >
                    </div>
                </div>

                <!-- Collapsible Panel Action Buttons (Mobile View Only) -->
                <div class="sm:col-span-2 lg:col-span-4 flex gap-3 justify-end mt-2">
                    @if($hasFilters)
                        <a href="{{ route('admin.orders.index', ['scope' => $activeFilters['scope'] ?? 'all']) }}" class="px-4 py-2 bg-neutral-100 text-neutral-700 rounded-xl text-xs font-bold hover:bg-neutral-200 transition-colors text-center w-full sm:w-auto">
                            Clear Filters
                        </a>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-neutral-800 text-white rounded-xl text-xs font-bold hover:bg-neutral-900 transition-colors focus-visible:outline-none w-full sm:w-auto">
                        Filter Orders
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Mobile Card Listing (Hidden on Desktop: lg:hidden) -->
    <div class="space-y-4 lg:hidden mb-6">
        @forelse($orders as $o)
            @php
                $paidSum = (int) ($o->payments_sum_amount_minor ?? 0);
                $totalAmount = (int) $o->total_amount_minor;

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

                $statusConfig = [
                    'pending_payment' => ['intent' => 'warning', 'label' => 'Pending Payment'],
                    'confirmed'       => ['intent' => 'blue',    'label' => 'Confirmed'],
                    'in_production'   => ['intent' => 'purple',  'label' => 'In Production'],
                    'ready_to_ship'   => ['intent' => 'warning', 'label' => 'Ready to Ship'],
                    'shipped'         => ['intent' => 'info',    'label' => 'Shipped'],
                    'delivered'       => ['intent' => 'success', 'label' => 'Delivered'],
                    'cancelled'       => ['intent' => 'danger',  'label' => 'Cancelled'],
                    'refunded'        => ['intent' => 'neutral', 'label' => 'Refunded'],
                ];

                $orderStatus = $statusConfig[$o->status] ?? [
                    'intent' => 'neutral',
                    'label'  => ucwords(str_replace('_', ' ', $o->status)),
                ];

                $sourcesConfig = config('orders.sources', []);
                $sourceLabel = $sourcesConfig[$o->order_source] ?? ucfirst($o->order_source);

                $custName = data_get($o->customer_snapshot, 'name', 'N/A');
                $custPhone = data_get($o->customer_snapshot, 'phone', 'N/A');
            @endphp
            <div 
                class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 shadow-xs hover:border-neutral-300 transition-colors"
                :class="{ '!bg-red-50/50 !border-red-300 ring-1 ring-red-200': selectedOrders.includes('{{ $o->public_id }}') }"
            >
                <div class="flex items-center justify-between gap-2 border-b border-neutral-100 pb-2.5 mb-2.5">
                    <div class="flex items-center gap-2">
                        <input 
                            type="checkbox" 
                            :value="'{{ $o->public_id }}'" 
                            x-model="selectedOrders"
                            class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                        >
                        <a href="{{ route('admin.orders.show', ['order' => $o->public_id]) }}" class="font-mono font-bold text-[color:var(--color-brand-600)] text-sm hover:underline">
                            {{ $o->public_id }}
                        </a>
                    </div>
                    <div class="text-[10px] text-neutral-400 font-mono">
                        {{ $o->placed_at ? $o->placed_at->format('d M Y') : ($o->created_at ? $o->created_at->format('d M Y') : 'N/A') }}
                    </div>
                </div>

                <!-- Card Attributes Grid -->
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-start">
                        <span class="text-neutral-400 font-semibold">Customer</span>
                        <div class="text-right">
                            <div class="font-semibold text-neutral-800">{{ $custName }}</div>
                            <div class="text-[10px] text-neutral-400 font-mono">{{ $custPhone }}</div>
                        </div>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-neutral-400 font-semibold">Source</span>
                        <span class="text-neutral-700 font-medium">{{ $sourceLabel }}</span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-neutral-400 font-semibold">Status</span>
                        <x-badge :intent="$orderStatus['intent']" size="sm">
                            {{ $orderStatus['label'] }}
                        </x-badge>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-neutral-400 font-semibold">Payment</span>
                        <x-badge :intent="$payIntent" size="sm">
                            {{ $payStatus }}
                        </x-badge>
                    </div>

                    <div class="flex justify-between items-center border-t border-neutral-50 pt-2 mt-2">
                        <span class="text-neutral-400 font-semibold">Total</span>
                        <span class="font-mono font-bold text-neutral-900 text-sm">₹{{ number_format($totalAmount / 100, 2) }}</span>
                    </div>
                </div>

                <!-- Card Action Bar -->
                <div class="flex gap-2 mt-3.5 pt-3 border-t border-neutral-100">
                    <a 
                        href="{{ route('admin.orders.show', ['order' => $o->public_id]) }}" 
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 border border-neutral-200 rounded-xl text-neutral-700 bg-neutral-50 hover:bg-neutral-100 text-xs font-bold transition-colors"
                    >
                        <x-icons.lucide name="lucide-eye" class="w-3.5 h-3.5" />
                        View Details
                    </a>

                    @if($o->status !== 'pending_payment' && $o->status !== 'cancelled' && Route::has('admin.orders.pdf.download'))
                        <a 
                            href="{{ route('admin.orders.pdf.download', ['order' => $o->public_id]) }}" 
                            class="inline-flex items-center justify-center p-2 border border-neutral-200 rounded-xl text-neutral-600 bg-neutral-50 hover:bg-neutral-100 transition-colors"
                            title="Download PDF"
                        >
                            <x-icons.lucide name="lucide-file-text" class="w-4 h-4" />
                        </a>
                    @endif
                </div>
            </div>
        @empty
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
        @endforelse

        <!-- Pagination for Mobile Cards -->
        <div class="pt-2">
            <x-table.pagination :paginator="$orders" />
        </div>
    </div>

    <!-- Desktop Data Table Container (Hidden on Mobile: hidden lg:block) -->
    <div class="hidden lg:block">
        <x-table>
            <x-table.head>
                <tr>
                    <th scope="col" class="w-10 px-4 py-3">
                        <input 
                            type="checkbox" 
                            @change="toggleAll()"
                            :checked="allSelected"
                            :indeterminate="someSelected"
                            class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                        >
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
                        $paidSum = (int) ($o->payments_sum_amount_minor ?? 0);
                        $totalAmount = (int) $o->total_amount_minor;

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

                        $statusConfig = [
                            'pending_payment' => ['intent' => 'warning', 'label' => 'Pending Payment'],
                            'confirmed'       => ['intent' => 'blue',    'label' => 'Confirmed'],
                            'in_production'   => ['intent' => 'purple',  'label' => 'In Production'],
                            'ready_to_ship'   => ['intent' => 'warning', 'label' => 'Ready to Ship'],
                            'shipped'         => ['intent' => 'info',    'label' => 'Shipped'],
                            'delivered'       => ['intent' => 'success', 'label' => 'Delivered'],
                            'cancelled'       => ['intent' => 'danger',  'label' => 'Cancelled'],
                            'refunded'        => ['intent' => 'neutral', 'label' => 'Refunded'],
                        ];

                        $orderStatus = $statusConfig[$o->status] ?? [
                            'intent' => 'neutral',
                            'label'  => ucwords(str_replace('_', ' ', $o->status)),
                        ];

                        $sourcesConfig = config('orders.sources', []);
                        $sourceLabel = $sourcesConfig[$o->order_source] ?? ucfirst($o->order_source);

                        $custName = data_get($o->customer_snapshot, 'name', 'N/A');
                        $custPhone = data_get($o->customer_snapshot, 'phone', 'N/A');
                    @endphp
                    <x-table.row x-bind:class="{ '!bg-red-50/60 font-medium': selectedOrders.includes('{{ $o->public_id }}') }">
                        <x-table.cell>
                            <input 
                                type="checkbox" 
                                :value="'{{ $o->public_id }}'" 
                                x-model="selectedOrders"
                                class="rounded border-neutral-300 text-[color:var(--color-brand-600)] focus:ring-[color:var(--color-brand-500)] cursor-pointer"
                            >
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
                            <x-badge :intent="$orderStatus['intent']" size="sm">
                                {{ $orderStatus['label'] }}
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
                                    title="More actions (Reserved)"
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
    </div>

    <!-- Hidden scrollbar styling support -->
    <style>
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
    </style>

    <!-- Hidden Form for Bulk Action Submission -->
    <form id="bulk-action-form" method="POST" action="{{ route('admin.orders.bulk') }}" class="hidden">
        @csrf
        <input type="hidden" name="action" id="bulk-action-input">
        <input type="hidden" name="target_status" id="bulk-target-status-input">
        <template x-for="id in selectedOrders" :key="id">
            <input type="hidden" name="order_ids[]" :value="id">
        </template>
    </form>

    <!-- Hidden Form for Dedicated Bulk Cancel Submission -->
    <form id="bulk-cancel-form" method="POST" action="{{ route('admin.orders.bulk.cancel') }}" class="hidden">
        @csrf
        <input type="hidden" name="reason_code" id="bulk-cancel-reason-input">
        <input type="hidden" name="reason_note" id="bulk-cancel-note-input">
        <template x-for="id in selectedOrders" :key="id">
            <input type="hidden" name="order_ids[]" :value="id">
        </template>
    </form>

    <!-- Floating Bulk Action Toolbar -->
    <div 
        x-show="selectedOrders.length > 0"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-10"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-neutral-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-4 z-50 border border-neutral-800 max-w-[95vw] overflow-x-auto"
    >
        <!-- Selection Counter -->
        <div class="flex items-center gap-2 shrink-0">
            <span class="w-2.5 h-2.5 rounded-full bg-[color:var(--color-brand-500)] animate-pulse"></span>
            <span class="text-xs font-bold text-neutral-200 whitespace-nowrap">
                <span x-text="selectedOrders.length"></span>
                <span x-text="selectedOrders.length === 1 ? ' order selected' : ' orders selected'"></span>
            </span>
        </div>
        
        <div class="h-5 w-px bg-neutral-800 shrink-0"></div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 shrink-0">
            <!-- Change Status Dropdown / Modal Trigger -->
            <button 
                type="button"
                @click="$dispatch('open-overlay', 'bulk-status-modal')"
                class="px-3.5 py-1.5 bg-neutral-800 hover:bg-neutral-700 text-white rounded-xl text-xs font-bold transition-colors focus:outline-none flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            >
                <x-icons.lucide name="lucide-sliders-horizontal" class="w-3.5 h-3.5 text-neutral-400" />
                <span>Change Status</span>
            </button>

            <!-- Batch Print Packing Slips -->
            <button 
                type="button"
                @click="submitPackingSlips()"
                class="px-3.5 py-1.5 bg-neutral-800 hover:bg-neutral-700 text-white rounded-xl text-xs font-bold transition-colors focus:outline-none flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                title="Print batch packing slips in a new window"
            >
                <x-icons.lucide name="lucide-printer" class="w-3.5 h-3.5 text-neutral-400" />
                <span>Packing Slips</span>
            </button>

            <!-- Export Courier Manifest -->
            <button 
                type="button"
                @click="submitExportManifest()"
                class="px-3.5 py-1.5 bg-neutral-800 hover:bg-neutral-700 text-white rounded-xl text-xs font-bold transition-colors focus:outline-none flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
                title="Export shipping manifest CSV for courier pickups"
            >
                <x-icons.lucide name="lucide-download" class="w-3.5 h-3.5 text-neutral-400" />
                <span>Courier Manifest</span>
            </button>

            <!-- Quick Cancel -->
            <button 
                type="button"
                @click="$dispatch('open-overlay', 'bulk-cancel-modal')"
                class="px-3 py-1.5 bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white rounded-xl text-xs font-bold transition-colors focus:outline-none cursor-pointer whitespace-nowrap"
            >
                Cancel
            </button>
        </div>

        <div class="h-5 w-px bg-neutral-800 shrink-0"></div>

        <!-- Clear Selection -->
        <button 
            type="button"
            @click="selectedOrders = []"
            class="text-neutral-400 hover:text-white text-xs font-semibold transition-colors focus:outline-none whitespace-nowrap cursor-pointer shrink-0"
        >
            Clear
        </button>
    </div>

    <!-- Batch Change Status Modal -->
    <x-modal id="bulk-status-modal" title="Update Status for Selected Orders">
        <div class="space-y-4">
            <p class="text-xs text-neutral-600">
                Choose the new status to apply across all <strong x-text="selectedOrders.length"></strong> selected orders:
            </p>
            <div class="space-y-2">
                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors" :class="selectedBulkStatus === 'confirmed' ? 'bg-neutral-50 border-neutral-800' : ''">
                    <input type="radio" name="bulk_status_choice" value="confirmed" x-model="selectedBulkStatus" class="text-neutral-900 focus:ring-neutral-800">
                    <div>
                        <div class="text-xs font-bold text-neutral-900">Confirmed</div>
                        <div class="text-[11px] text-neutral-500">Mark orders as verified and ready for production prep</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors" :class="selectedBulkStatus === 'in_production' ? 'bg-neutral-50 border-neutral-800' : ''">
                    <input type="radio" name="bulk_status_choice" value="in_production" x-model="selectedBulkStatus" class="text-neutral-900 focus:ring-neutral-800">
                    <div>
                        <div class="text-xs font-bold text-neutral-900">In Production</div>
                        <div class="text-[11px] text-neutral-500">Move orders into screen/DTF printing and decoration queue</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors" :class="selectedBulkStatus === 'ready_to_ship' ? 'bg-neutral-50 border-neutral-800' : ''">
                    <input type="radio" name="bulk_status_choice" value="ready_to_ship" x-model="selectedBulkStatus" class="text-neutral-900 focus:ring-neutral-800">
                    <div>
                        <div class="text-xs font-bold text-neutral-900">Ready to Ship</div>
                        <div class="text-[11px] text-neutral-500">Printing complete; garments packed and ready for carrier pickup</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors" :class="selectedBulkStatus === 'shipped' ? 'bg-neutral-50 border-neutral-800' : ''">
                    <input type="radio" name="bulk_status_choice" value="shipped" x-model="selectedBulkStatus" class="text-neutral-900 focus:ring-neutral-800">
                    <div>
                        <div class="text-xs font-bold text-neutral-900">Shipped</div>
                        <div class="text-[11px] text-neutral-500">Parcels handed over to courier for transit</div>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-neutral-200 hover:bg-neutral-50 cursor-pointer transition-colors" :class="selectedBulkStatus === 'delivered' ? 'bg-neutral-50 border-neutral-800' : ''">
                    <input type="radio" name="bulk_status_choice" value="delivered" x-model="selectedBulkStatus" class="text-neutral-900 focus:ring-neutral-800">
                    <div>
                        <div class="text-xs font-bold text-neutral-900">Delivered</div>
                        <div class="text-[11px] text-neutral-500">Fulfilled and delivered to customers</div>
                    </div>
                </label>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" @click="$dispatch('close-overlay', 'bulk-status-modal')" class="px-4 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 cursor-pointer">
                Cancel
            </button>
            <button type="button" @click="submitBulkAction('update_status', selectedBulkStatus)" class="px-4 py-2 bg-neutral-900 text-white hover:bg-neutral-800 rounded-xl text-xs font-bold cursor-pointer transition-colors">
                Apply Status Update
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Confirmation Modals -->
    <x-modal id="bulk-confirm-modal" title="Confirm selected orders?">
        <p class="text-sm text-neutral-600">This action will mark all selected orders as <strong>Confirmed</strong>. Are you sure you want to proceed?</p>
        <x-slot:footer>
            <button type="button" @click="$dispatch('close-overlay', 'bulk-confirm-modal')" class="px-4 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 cursor-pointer">Cancel</button>
            <button type="button" @click="submitBulkAction('confirm')" class="px-4 py-2 bg-[color:var(--color-brand-600)] text-white hover:bg-[color:var(--color-brand-700)] rounded-xl text-xs font-bold cursor-pointer">Confirm</button>
        </x-slot:footer>
    </x-modal>

    <x-modal id="bulk-cancel-modal" title="Cancel Selected Orders">
        <div class="space-y-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 leading-relaxed space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <x-icons.lucide name="lucide-alert-triangle" class="w-4 h-4 text-amber-600" />
                    Pre-Production Orders Only
                </div>
                <p class="text-amber-800">
                    Bulk cancellation applies strictly to orders in <span class="font-semibold">Pending Payment</span> and <span class="font-semibold">Confirmed</span>. Any selected orders currently <span class="font-semibold">In Production</span> or <span class="font-semibold">Ready to Ship</span> will be safely skipped to ensure mandatory operational and scrap inspection.
                </p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-700">
                    Cancellation Reason <span class="text-rose-600">*</span>
                </label>
                <select 
                    x-model="bulkCancelReason" 
                    class="w-full rounded-xl border border-neutral-300 px-3 py-2 text-xs font-semibold text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                >
                    <option value="customer_request">Customer Request</option>
                    <option value="artwork_issue">Artwork / Design Issue</option>
                    <option value="lead_time_delay">Lead Time Delay</option>
                    <option value="pricing_error">Pricing / Quotation Error</option>
                    <option value="duplicate_order">Duplicate Order</option>
                    <option value="other">Other Reason</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-neutral-700">
                    Notes / Explanation <span x-show="bulkCancelReason === 'other'" class="text-rose-600">*</span>
                </label>
                <textarea 
                    x-model="bulkCancelNote" 
                    rows="2" 
                    placeholder="Provide operational reason or context for bulk cancellation..."
                    class="w-full rounded-xl border border-neutral-300 px-3 py-2 text-xs text-neutral-800 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900"
                ></textarea>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" @click="$dispatch('close-overlay', 'bulk-cancel-modal')" class="px-4 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-white hover:bg-neutral-50 cursor-pointer">
                Close
            </button>
            <button 
                type="button" 
                @click="submitBulkCancel()" 
                :disabled="bulkCancelReason === 'other' && !bulkCancelNote.trim()"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold cursor-pointer transition-colors"
            >
                Cancel Selected Orders
            </button>
        </x-slot:footer>
    </x-modal>
    </div>
</x-layouts.admin>
