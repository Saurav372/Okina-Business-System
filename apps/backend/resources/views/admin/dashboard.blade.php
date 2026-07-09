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

    <!-- Responsive Split Columns Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8 items-start">
        <!-- Main Content Area (Left / Spans 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Charts Visualization Row Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 1. Revenue Trend Line Chart -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs" x-data="{ activePoint: null }">
                    <div class="flex items-start justify-between border-b border-[color:var(--color-border)] pb-4 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-800">Revenue Trend</h3>
                            <p class="text-[10px] text-neutral-400 font-medium uppercase mt-0.5">Last 6 Calendar Months</p>
                        </div>
                        @if($revenueSeries->changePercent !== null && $revenueSeries->changePercent != 0)
                            @php
                                $isUp = $revenueSeries->changeDirection === 'up';
                                $trendColor = $isUp ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-rose-600 bg-rose-50 border-rose-200';
                                $trendIcon = $isUp ? '↑' : '↓';
                            @endphp
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full border text-[11px] font-bold {{ $trendColor }}">
                                {{ $trendIcon }} {{ abs($revenueSeries->changePercent) }}%
                            </span>
                        @endif
                    </div>

                    @php
                        $revenueLayout = \App\Presenters\ChartGeometryPresenter::present($revenueSeries, 450, 180, 45, 20);
                        $linePath = \App\Support\Dashboard\ChartPathBuilder::toLinePath($revenueLayout->coordinates);
                        $areaPath = \App\Support\Dashboard\ChartPathBuilder::toAreaPath($revenueLayout->coordinates, $revenueLayout->baselineY);
                        $hasRevenueData = $revenueSeries->points->contains(fn($p) => $p->value > 0);
                    @endphp

                    @if(!$hasRevenueData)
                        <div class="h-44 flex items-center justify-center bg-neutral-50 border border-dashed border-neutral-200 rounded-xl">
                            <p class="text-xs text-neutral-400">No sales revenue logged for the last 6 months.</p>
                        </div>
                    @else
                        <div class="relative w-full h-44">
                            <svg viewBox="0 0 450 180" class="w-full h-full overflow-visible select-none" aria-label="Line chart showing monthly revenue trend.">
                                <title>Revenue Trend Chart</title>
                                <desc>Displays sales order completions totals over the last 6 calendar months.</desc>
                                
                                <defs>
                                    <linearGradient id="areaGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="var(--color-{{ $revenueSeries->color }})" stop-opacity="0.2" />
                                        <stop offset="100%" stop-color="var(--color-{{ $revenueSeries->color }})" stop-opacity="0" />
                                    </linearGradient>
                                </defs>

                                <!-- Y Axis Grid Lines & Ticks -->
                                @foreach($revenueLayout->ticks as $tick)
                                    <line x1="45" y1="{{ $tick['y'] }}" x2="430" y2="{{ $tick['y'] }}" stroke="#f3f4f6" stroke-width="1" />
                                    <text x="35" y="{{ $tick['y'] + 4 }}" text-anchor="end" class="text-[9px] font-medium fill-neutral-400 font-mono">{{ $tick['label'] }}</text>
                                @endforeach

                                <!-- Zero Baseline -->
                                <line x1="45" y1="{{ $revenueLayout->baselineY }}" x2="430" y2="{{ $revenueLayout->baselineY }}" stroke="#e5e7eb" stroke-width="1.5" />

                                <!-- Gradient Area path -->
                                <path d="{{ $areaPath }}" fill="url(#areaGradient)" class="chart-area-fade" />
                                <!-- Outline Line path -->
                                <path d="{{ $linePath }}" fill="none" stroke="var(--color-{{ $revenueSeries->color }})" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="chart-line-draw" />

                                <!-- Hover/Focus interactive markers -->
                                @foreach($revenueLayout->coordinates as $index => $pt)
                                    <circle 
                                        cx="{{ $pt['x'] }}" 
                                        cy="{{ $pt['y'] }}" 
                                        r="6" 
                                        fill="white" 
                                        stroke="var(--color-{{ $revenueSeries->color }})" 
                                        stroke-width="2.5"
                                        tabindex="0"
                                        aria-label="Month: {{ $pt['label'] }}, Revenue: {{ $pt['formatted'] }}"
                                        class="cursor-pointer focus:scale-125 focus:stroke-width-3 focus:outline-none transition-all duration-150"
                                        @mouseenter="activePoint = @js($pt)"
                                        @mouseleave="activePoint = null"
                                        @focus="activePoint = @js($pt)"
                                        @blur="activePoint = null"
                                        @click="activePoint = (activePoint && activePoint.x === @js($pt['x'])) ? null : @js($pt)"
                                    />
                                @endforeach

                                <!-- X Axis Labels -->
                                @foreach($revenueLayout->coordinates as $pt)
                                    <text x="{{ $pt['x'] }}" y="175" text-anchor="middle" class="text-[9px] font-bold fill-neutral-400 uppercase tracking-wider">{{ $pt['label'] }}</text>
                                @endforeach
                            </svg>

                            <!-- Tooltip Modal Card overlay -->
                            <div 
                                x-show="activePoint" 
                                x-transition 
                                class="absolute z-50 bg-neutral-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl border border-neutral-800 pointer-events-none"
                                :style="`left: ${activePoint ? (activePoint.x - 35) : 0}px; top: ${activePoint ? (activePoint.y - 50) : 0}px;`"
                            >
                                <span class="block font-bold text-[9px] text-neutral-400 uppercase tracking-wide" x-text="activePoint.label"></span>
                                <span class="block font-bold text-xs mt-0.5" x-text="activePoint.formatted"></span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 2. Monthly Orders Volume Bar Chart -->
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs" x-data="{ activeBar: null }">
                    <div class="flex items-start justify-between border-b border-[color:var(--color-border)] pb-4 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-neutral-800">Monthly Orders</h3>
                            <p class="text-[10px] text-neutral-400 font-medium uppercase mt-0.5">Last 6 Calendar Months</p>
                        </div>
                        @if($ordersSeries->changePercent !== null && $ordersSeries->changePercent != 0)
                            @php
                                $isUp = $ordersSeries->changeDirection === 'up';
                                $trendColor = $isUp ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-rose-600 bg-rose-50 border-rose-200';
                                $trendIcon = $isUp ? '↑' : '↓';
                            @endphp
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full border text-[11px] font-bold {{ $trendColor }}">
                                {{ $trendIcon }} {{ abs($ordersSeries->changePercent) }}%
                            </span>
                        @endif
                    </div>

                    @php
                        $ordersLayout = \App\Presenters\ChartGeometryPresenter::present($ordersSeries, 450, 180, 45, 20);
                        $hasOrdersData = $ordersSeries->points->contains(fn($p) => $p->value > 0);
                    @endphp

                    @if(!$hasOrdersData)
                        <div class="h-44 flex items-center justify-center bg-neutral-50 border border-dashed border-neutral-200 rounded-xl">
                            <p class="text-xs text-neutral-400">No order activity logged for the last 6 months.</p>
                        </div>
                    @else
                        <div class="relative w-full h-44">
                            <svg viewBox="0 0 450 180" class="w-full h-full overflow-visible select-none" aria-label="Bar chart showing monthly order counts.">
                                <title>Monthly Orders Chart</title>
                                <desc>Displays sales order counts placed over the last 6 calendar months.</desc>

                                <!-- Y Axis Grid Lines & Ticks -->
                                @foreach($ordersLayout->ticks as $tick)
                                    <line x1="45" y1="{{ $tick['y'] }}" x2="430" y2="{{ $tick['y'] }}" stroke="#f3f4f6" stroke-width="1" />
                                    <text x="35" y="{{ $tick['y'] + 4 }}" text-anchor="end" class="text-[9px] font-medium fill-neutral-400 font-mono">{{ $tick['label'] }}</text>
                                @endforeach

                                <!-- Zero Baseline -->
                                <line x1="45" y1="{{ $ordersLayout->baselineY }}" x2="430" y2="{{ $ordersLayout->baselineY }}" stroke="#e5e7eb" stroke-width="1.5" />

                                <!-- Columns/Bars -->
                                @foreach($ordersLayout->coordinates as $index => $pt)
                                    @php
                                        $barWidth = 24;
                                        $barHeight = max(2, $ordersLayout->baselineY - $pt['y']);
                                        $barX = $pt['x'] - ($barWidth / 2);
                                        $barY = $pt['y'];
                                    @endphp
                                    <rect 
                                        x="{{ $barX }}" 
                                        y="{{ $barY }}" 
                                        width="{{ $barWidth }}" 
                                        height="{{ $barHeight }}" 
                                        rx="4"
                                        fill="var(--color-{{ $ordersSeries->color }})" 
                                        tabindex="0"
                                        aria-label="Month: {{ $pt['label'] }}, Orders: {{ $pt['formatted'] }}"
                                        class="cursor-pointer hover:opacity-85 focus:opacity-85 focus:outline-none transition-opacity duration-150 chart-bar-grow"
                                        @mouseenter="activeBar = @js($pt)"
                                        @mouseleave="activeBar = null"
                                        @focus="activeBar = @js($pt)"
                                        @blur="activeBar = null"
                                    />
                                @endforeach

                                <!-- X Axis Labels -->
                                @foreach($ordersLayout->coordinates as $pt)
                                    <text x="{{ $pt['x'] }}" y="175" text-anchor="middle" class="text-[9px] font-bold fill-neutral-400 uppercase tracking-wider">{{ $pt['label'] }}</text>
                                @endforeach
                            </svg>

                            <!-- Tooltip Modal Card overlay -->
                            <div 
                                x-show="activeBar" 
                                x-transition 
                                class="absolute z-50 bg-neutral-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl border border-neutral-800 pointer-events-none"
                                :style="`left: ${activeBar ? (activeBar.x - 35) : 0}px; top: ${activeBar ? (activeBar.y - 50) : 0}px;`"
                            >
                                <span class="block font-bold text-[9px] text-neutral-400 uppercase tracking-wide" x-text="activeBar.label"></span>
                                <span class="block font-bold text-xs mt-0.5" x-text="activeBar.formatted"></span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Empty State Onboarding Guidance / Overview details -->
            @if($isEmptyState)
                <x-alert type="info" title="Welcome to your new dashboard!" dismissible="false">
                    <p class="text-sm leading-relaxed text-blue-900">
                        Your backend sandbox database is currently empty. Let's get started by creating your first sales orders or syncing catalogs via Google Sheets!
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-lg hover:bg-[color:var(--color-brand-700)] transition-colors">
                            Create Sales Order
                        </a>
                    </div>
                </x-alert>
            @else
                <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="p-1.5 bg-emerald-50 text-emerald-600 rounded-lg">
                            <x-icons.lucide name="lucide-bell" class="w-4 h-4" />
                        </span>
                        <h3 class="text-sm font-bold text-neutral-800">Operational Overview</h3>
                    </div>
                    <p class="text-xs text-neutral-600 leading-relaxed">
                        The metrics widgets above represent live system metrics aggregated from your orders, payments, stock levels, and purchase orders. Click on any widget to drill down directly to the corresponding module interface.
                    </p>
                </div>
            @endif
        </div>

        <!-- Recent Activity Sidebar Timeline (Right / Spans 1 col) -->
        <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs flex flex-col h-full">
            <div class="flex items-center justify-between border-b border-[color:var(--color-border)] pb-4 mb-5 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-neutral-100 text-neutral-600 rounded-lg">
                        <x-icons.lucide name="lucide-clipboard" class="w-4 h-4" />
                    </span>
                    <h3 class="text-sm font-bold text-neutral-800">Recent Activity</h3>
                </div>
            </div>

            <!-- Scrollable Timeline Container -->
            <div class="max-h-[32rem] overflow-y-auto pr-2 scrollbar-thin">
                @if($activities->isEmpty())
                    <x-empty-state 
                        title="No Activity Yet" 
                        description="Operations logs will appear here chronologically as actions are performed."
                        class="py-8"
                    />
                @else
                    <x-timeline.index>
                        @foreach($activities as $activity)
                            <x-timeline.item :item="$activity" />
                        @endforeach
                    </x-timeline.index>
                @endif
            </div>
        </div>
    </div>

    <!-- Interactive CSS Micro-Animations Style (Reduced motion compliant) -->
    <style>
        @media (prefers-reduced-motion: no-preference) {
            .chart-line-draw {
                stroke-dasharray: 1000;
                stroke-dashoffset: 1000;
                animation: dash 1.5s ease-out forwards;
            }
            .chart-area-fade {
                animation: fadeIn 0.8s ease-out 0.8s forwards;
                opacity: 0;
            }
            .chart-bar-grow {
                transform-origin: bottom;
                animation: growUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
            @keyframes dash {
                to { stroke-dashoffset: 0; }
            }
            @keyframes fadeIn {
                to { opacity: 0.15; }
            }
            @keyframes growUp {
                from { transform: scaleY(0); }
                to { transform: scaleY(1); }
            }
        }
    </style>
</x-layouts.admin>
