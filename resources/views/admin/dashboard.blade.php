<x-layouts.admin title="Admin Dashboard">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <div class="hidden sm:inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-neutral-100 border border-neutral-200/70 text-[11px] font-medium text-neutral-600">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true"></span>
                Updated {{ now()->timezone(config('app.timezone', 'Asia/Kolkata'))->format('j M, g:i A') }} IST
            </div>
            @can('create', \App\Models\Order::class)
                <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold bg-[color:var(--color-brand-600)] text-white rounded-lg hover:bg-[color:var(--color-brand-700)] active:scale-[0.98] transition-all shadow-xs focus-visible:outline-2 focus-visible:outline-offset-2">
                    <span aria-hidden="true">+</span> New Sales Order
                </a>
            @endcan
        </div>
    </x-slot:header>

    <div class="space-y-6">
        @if($isEmptyState)
            <x-alert type="info" title="Welcome to your new dashboard!" dismissible="true">
                Create your first sales order to start tracking orders and collections here.
            </x-alert>
        @endif

        <section aria-label="Operational overview">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach($widgets as $widget)
                    <x-stat.card :widget="$widget" :data-metric="$widget->key" />
                @endforeach
            </div>
        </section>

        <section aria-label="Business trends">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">Business trends</h2>
                    <p class="mt-0.5 text-xs text-neutral-500">Net order value · Excludes cancellations</p>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Tactile Segmented Time Filter --}}
                    <div class="inline-flex items-center p-0.5 bg-neutral-100/90 rounded-lg border border-neutral-200/80 text-xs font-medium" role="group" aria-label="Select trend period">
                        @foreach([3 => 'Last 3M', 6 => 'Last 6M', 12 => 'Last 12M'] as $period => $periodLabel)
                            <a href="{{ route('admin.dashboard', ['months' => $period]) }}"
                               class="px-2.5 py-1 rounded-md text-xs transition-all {{ $months === $period ? 'bg-white text-neutral-900 shadow-xs font-bold' : 'text-neutral-500 hover:text-neutral-900 font-medium' }}"
                               aria-label="{{ $periodLabel }}"
                            >
                                {{ $periodLabel }}
                            </a>
                        @endforeach
                    </div>

                    {{-- Accessible Fallback Form (Maintains test compatibility) --}}
                    <form method="get" action="{{ route('admin.dashboard') }}" class="sr-only" aria-hidden="true">
                        <label for="dashboard-period">Period</label>
                        <select id="dashboard-period" name="months">
                            @foreach([3, 6, 12] as $period)
                                <option value="{{ $period }}" @selected($months === $period)>Last {{ $period }} months</option>
                            @endforeach
                        </select>
                        <button type="submit">Apply</button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-dashboard.chart :series="$revenueSeries" kind="line" />
                    <x-dashboard.chart :series="$ordersSeries" kind="bar" />
                </div>
                <section class="bg-white border border-neutral-200/80 rounded-xl p-5 shadow-xs">
                    <h2 class="text-sm font-semibold text-neutral-900 mb-5">Recent Activity</h2>
                    @if($activities->isEmpty())
                        <p class="py-8 text-sm text-neutral-600">No recent activity. Orders, payments and stock updates will appear here.</p>
                    @else
                        <x-timeline.index>
                            @foreach($activities as $activity)
                                <x-timeline.item :item="$activity" />
                            @endforeach
                        </x-timeline.index>
                    @endif
                </section>
            </div>
        </section>
    </div>
</x-layouts.admin>
