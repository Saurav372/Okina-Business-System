<x-layouts.admin title="Admin Dashboard">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <span class="hidden sm:block text-xs font-medium text-neutral-500">Updated {{ now()->timezone(config('app.timezone', 'Asia/Kolkata'))->format('j M, g:i A') }} IST</span>
            @can('create', \App\Models\Order::class)
                <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] focus-visible:outline-2 focus-visible:outline-offset-2">
                    <span aria-hidden="true">+</span> New Sales Order
                </a>
            @endcan
        </div>
    </x-slot:header>
    <div class="space-y-6">
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
                <form method="get" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <label for="dashboard-period" class="text-xs font-medium text-neutral-600">Period</label>
                    <select id="dashboard-period" name="months" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs">
                        @foreach([3, 6, 12] as $period)
                            <option value="{{ $period }}" @selected($months === $period)>Last {{ $period }} months</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 text-xs font-semibold hover:bg-neutral-50">Apply</button>
                </form>
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <div class="xl:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <x-dashboard.chart :series="$revenueSeries" kind="line" />
                    <x-dashboard.chart :series="$ordersSeries" kind="bar" />
                </div>
                <section class="bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs">
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
        @if($isEmptyState)
            <x-alert type="info" title="Welcome to your new dashboard!" dismissible="true">
                Create your first sales order to start tracking orders and collections here.
            </x-alert>
        @endif
    </div>
</x-layouts.admin>
