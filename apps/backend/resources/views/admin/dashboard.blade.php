<x-layouts.admin title="Admin Dashboard | Okina Craft">
    <x-slot:header>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Live System Status
            </span>
            <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
                New Sales Order
            </a>
        </div>
    </x-slot:header>

    <!-- Stat KPI Widgets Grid -->
    <x-stat.grid>
        @foreach($widgets as $widget)
            <x-stat.card :widget="$widget" />
        @endforeach
    </x-stat.grid>

    <!-- Empty State Onboarding Guidance -->
    @if($isEmptyState)
        <div class="mt-8">
            <x-alert type="info" title="Welcome to your new dashboard!" dismissible="false">
                <p class="text-sm leading-relaxed text-blue-900">
                    Your backend sandbox database is currently empty. Let's get started by creating your first orders, logging CRM leads, or syncing catalogs via Google Sheets!
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-lg hover:bg-[color:var(--color-brand-700)] transition-colors">
                        Create Sales Order
                    </a>
                    <a href="{{ route('admin.leads.index') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-white text-neutral-800 rounded-lg hover:bg-neutral-50 transition-colors border border-neutral-300">
                        Add CRM Lead
                    </a>
                </div>
            </x-alert>
        </div>
    @else
        <div class="mt-8 bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs">
            <div class="flex items-center gap-2 mb-3">
                <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg">
                    <x-icons.lucide name="lucide-bell" class="w-4 h-4" />
                </span>
                <h3 class="text-sm font-bold text-neutral-800">Operational Overview</h3>
            </div>
            <p class="text-xs text-neutral-600 leading-relaxed">
                The metrics widgets above represent live system metrics aggregated from your orders, customer quotes, and stock levels. Click on any widget to drill down directly to the corresponding module interface.
            </p>
        </div>
    @endif
</x-layouts.admin>
