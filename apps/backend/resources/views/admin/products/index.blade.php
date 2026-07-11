<x-layouts.admin title="Products">
    <x-slot:header>
        @can('create', \App\Models\Product::class)
            <a href="#" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
                New Product
            </a>
        @endcan
    </x-slot:header>

    @php
        $hasFilters = !empty($activeFilters['search']) || !empty($activeFilters['status']) || !empty($activeFilters['visibility']) || !empty($activeFilters['product_type']);
    @endphp

    <!-- Filters & Search Form -->
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
        <form method="GET" action="{{ route('admin.products.index') }}">
            <!-- Hidden inputs to preserve sorting -->
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
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Search Products</label>
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $activeFilters['search'] ?? '' }}" 
                        placeholder="Search by product name..."
                        class="w-full px-4 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 placeholder-neutral-400"
                    >
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 shrink-0">
                    <button 
                        type="button" 
                        @click="showFilters = !showFilters"
                        class="lg:hidden flex-1 sm:flex-none px-4 py-2 border border-neutral-300 rounded-xl text-xs font-semibold text-neutral-700 bg-neutral-50 hover:bg-neutral-100 flex items-center justify-center gap-1.5 whitespace-nowrap cursor-pointer"
                    >
                        <x-icons.lucide name="lucide-sliders-horizontal" class="w-4 h-4 text-neutral-500" />
                        <span x-text="showFilters ? 'Hide Filters' : 'Filters'">Filters</span>
                    </button>

                    <button type="submit" class="inline-flex px-5 py-2 bg-neutral-800 text-white rounded-xl text-xs font-bold hover:bg-neutral-900 transition-colors focus-visible:outline-none cursor-pointer">
                        Apply
                    </button>

                    @if($hasFilters)
                        <a href="{{ route('admin.products.index') }}" class="inline-flex px-4 py-2 border border-neutral-300 text-neutral-700 bg-white hover:bg-neutral-50 rounded-xl text-xs font-semibold justify-center items-center gap-1 cursor-pointer">
                            <x-icons.lucide name="lucide-rotate-ccw" class="w-3.5 h-3.5" />
                            Reset
                        </a>
                    @endif
                </div>
            </div>

            <!-- Collapsible Advanced Filters Section -->
            <div 
                x-show="!isMobile || showFilters" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 pt-4 border-t border-neutral-100"
                :class="{ 'hidden': isMobile && !showFilters }"
            >
                <!-- Status Filter -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Status</label>
                    <select 
                        name="status"
                        class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 bg-white"
                    >
                        <option value="">All Statuses</option>
                        @foreach($definition['filters']['status']['options'] as $val => $lbl)
                            <option value="{{ $val }}" {{ (string) ($activeFilters['status'] ?? '') === (string) $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Visibility Filter -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Visibility</label>
                    <select 
                        name="visibility"
                        class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 bg-white"
                    >
                        <option value="">All Visibilities</option>
                        @foreach($definition['filters']['visibility']['options'] as $val => $lbl)
                            <option value="{{ $val }}" {{ (string) ($activeFilters['visibility'] ?? '') === (string) $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Type Filter -->
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Type</label>
                    <select 
                        name="product_type"
                        class="w-full px-3 py-2 border border-neutral-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] text-xs text-neutral-800 bg-white"
                    >
                        <option value="">All Types</option>
                        @foreach($definition['filters']['product_type']['options'] as $val => $lbl)
                            <option value="{{ $val }}" {{ (string) ($activeFilters['product_type'] ?? '') === (string) $val ? 'selected' : '' }}>
                                {{ $lbl }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Mobile Card Layout -->
    <div class="space-y-3 lg:hidden">
        @forelse($products as $p)
            @php
                $statusIntent = match ($p->status) {
                    'active' => 'success',
                    'draft', 'discontinued' => 'neutral',
                    'out_of_stock' => 'danger',
                    'bulk_only' => 'info',
                    default => 'neutral',
                };
                $visibilityIntent = match ($p->visibility) {
                    'public' => 'success',
                    'private' => 'neutral',
                    default => 'neutral',
                };
            @endphp
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-4 shadow-xs">
                <div class="flex items-center justify-between border-b border-neutral-100 pb-2.5 mb-2.5">
                    <span class="font-mono text-xs font-bold text-neutral-400">ID: {{ $p->id }}</span>
                    <x-badge :intent="$statusIntent" size="sm">
                        {{ ucfirst(str_replace('_', ' ', $p->status)) }}
                    </x-badge>
                </div>
                <h3 class="font-bold text-neutral-800 text-sm mb-1">{{ $p->name }}</h3>
                <p class="font-mono text-xs text-neutral-400 mb-3 truncate">{{ $p->slug }}</p>
                <div class="grid grid-cols-2 gap-y-2 text-xs text-neutral-500">
                    <div>Category: <span class="font-semibold text-neutral-700">{{ $p->category?->name ?? 'Uncategorized' }}</span></div>
                    <div>Type: <span class="font-semibold text-neutral-700">{{ ucfirst($p->product_type) }}</span></div>
                    <div>SKUs: <span class="font-semibold text-neutral-700">{{ $p->skus_count }}</span></div>
                    <div>Visibility: <span class="font-semibold"><x-badge :intent="$visibilityIntent" size="sm">{{ ucfirst($p->visibility) }}</x-badge></span></div>
                </div>
                @can('update', $p)
                    <div class="mt-4 pt-3 border-t border-neutral-100 flex justify-end">
                        <a href="{{ route('admin.products.edit', $p) }}" class="inline-flex items-center justify-center px-3.5 py-1.5 border border-neutral-300 rounded-xl text-xs font-bold text-neutral-700 hover:bg-neutral-50">
                            Edit
                        </a>
                    </div>
                @endcan
            </div>
        @empty
            <x-empty-state 
                title="No Products Found" 
                description="Try broadening your search query or removing active filter options."
                size="sm"
            />
        @endforelse

        <!-- Pagination for Mobile Cards -->
        <div class="pt-2">
            <x-table.pagination :paginator="$products" />
        </div>
    </div>

    <!-- Desktop Data Table Container -->
    <div class="hidden lg:block">
        <x-table>
            <x-table.head>
                <tr>
                    <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'id' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.products.index', array_merge($activeFilters, ['sort' => 'id', 'direction' => ($activeFilters['sort'] ?? '') === 'id' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                        ID
                    </x-table.heading>
                    <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'name' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.products.index', array_merge($activeFilters, ['sort' => 'name', 'direction' => ($activeFilters['sort'] ?? '') === 'name' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                        Name
                    </x-table.heading>
                    <x-table.heading>Slug</x-table.heading>
                    <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'status' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.products.index', array_merge($activeFilters, ['sort' => 'status', 'direction' => ($activeFilters['sort'] ?? '') === 'status' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                        Status
                    </x-table.heading>
                    <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'visibility' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.products.index', array_merge($activeFilters, ['sort' => 'visibility', 'direction' => ($activeFilters['sort'] ?? '') === 'visibility' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                        Visibility
                    </x-table.heading>
                    <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'product_type' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.products.index', array_merge($activeFilters, ['sort' => 'product_type', 'direction' => ($activeFilters['sort'] ?? '') === 'product_type' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                        Type
                    </x-table.heading>
                    <x-table.heading>Category</x-table.heading>
                    <x-table.heading>SKUs</x-table.heading>
                    <x-table.heading sortable :direction="($activeFilters['sort'] ?? '') === 'created_at' ? ($activeFilters['direction'] ?? 'desc') : null" :href="route('admin.products.index', array_merge($activeFilters, ['sort' => 'created_at', 'direction' => ($activeFilters['sort'] ?? '') === 'created_at' && ($activeFilters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc']))">
                        Created
                    </x-table.heading>
                    <x-table.heading align="right">Actions</x-table.heading>
                </tr>
            </x-table.head>
            <x-table.body class="divide-y divide-neutral-100 text-sm bg-white">
                @forelse($products as $p)
                    @php
                        $statusIntent = match ($p->status) {
                            'active' => 'success',
                            'draft', 'discontinued' => 'neutral',
                            'out_of_stock' => 'danger',
                            'bulk_only' => 'info',
                            default => 'neutral',
                        };
                        $visibilityIntent = match ($p->visibility) {
                            'public' => 'success',
                            'private' => 'neutral',
                            default => 'neutral',
                        };
                    @endphp
                    <x-table.row>
                        <x-table.cell class="font-mono text-neutral-500">{{ $p->id }}</x-table.cell>
                        <x-table.cell class="font-semibold text-neutral-800">{{ $p->name }}</x-table.cell>
                        <x-table.cell class="font-mono text-xs text-neutral-400">{{ $p->slug }}</x-table.cell>
                        <x-table.cell>
                            <x-badge :intent="$statusIntent" size="sm">
                                {{ str_replace('_', ' ', $p->status) }}
                            </x-badge>
                        </x-table.cell>
                        <x-table.cell>
                            <x-badge :intent="$visibilityIntent" size="sm">
                                {{ $p->visibility }}
                            </x-badge>
                        </x-table.cell>
                        <x-table.cell class="text-xs font-semibold text-neutral-600">
                            {{ ucfirst($p->product_type) }}
                        </x-table.cell>
                        <x-table.cell class="text-neutral-700">
                            {{ $p->category?->name ?? 'Uncategorized' }}
                        </x-table.cell>
                        <x-table.cell class="font-mono font-bold text-neutral-600">
                            {{ $p->skus_count }}
                        </x-table.cell>
                        <x-table.cell class="text-xs text-neutral-500 font-mono">
                            {{ $p->created_at->format('d M Y') }}
                        </x-table.cell>
                        <x-table.cell align="right">
                            @can('update', $p)
                                <a href="{{ route('admin.products.edit', $p) }}" class="inline-flex px-3 py-1.5 border border-neutral-300 text-neutral-700 hover:bg-neutral-50 rounded-xl text-xs font-bold transition-colors">
                                    Edit
                                </a>
                            @endcan
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colspan="10">
                            <x-empty-state 
                                title="No Products Found" 
                                description="Try broadening your search query or removing active filter options."
                                size="md"
                            />
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table>
        <div class="pt-4">
            <x-table.pagination :paginator="$products" />
        </div>
    </div>
</x-layouts.admin>
