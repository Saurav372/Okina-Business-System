<x-layouts.admin title="Admin Dashboard">
    <x-slot:header>
        <!-- Mobile Buttons (stay on one line) -->
        <div class="flex items-center gap-1.5 lg:hidden">
            @if(Route::has('admin.sales_orders.create'))
                <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[color:var(--color-brand-600)] text-[10px] font-bold text-white hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none">
                    + New Order
                </a>
            @endif
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-[10px] font-bold text-emerald-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Live Status
            </span>
        </div>

        <!-- Desktop Buttons -->
        <div class="hidden lg:flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Live System Status
            </span>
            @if(Route::has('admin.sales_orders.create'))
                <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-xl hover:bg-[color:var(--color-brand-700)] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)]">
                    New Sales Order
                </a>
            @endif
        </div>
    </x-slot:header>

    @php
        // Dynamically split primary and secondary widgets to prevent ordering issues
        $primaryLabels = ["Today's Orders", "Pending Orders", "Outstanding Balance", "Today's Collections"];
        $primaryWidgets = [];
        $secondaryWidgets = [];
        foreach($widgets as $w) {
            if(in_array($w->label, $primaryLabels)) {
                $primaryWidgets[] = $w;
            } else {
                $secondaryWidgets[] = $w;
            }
        }
    @endphp

    <!-- MOBILE VIEW (lg:hidden) - Follows the Mobile Dashboard Priority Schema -->
    <div class="lg:hidden space-y-4">
        <!-- 1. Primary KPIs Grid (2-column compact layout) -->
        <div class="grid grid-cols-2 gap-3">
            @foreach($primaryWidgets as $widget)
                @php
                    $mobLabel = match($widget->label) {
                        "Today's Orders" => "Orders",
                        "Pending Orders" => "Pending",
                        "Outstanding Balance" => "Outstanding",
                        "Today's Collections" => "Collections",
                        default => $widget->label
                    };

                    $mobDesc = match($widget->label) {
                        "Today's Orders" => "Today",
                        "Pending Orders" => "Active",
                        "Outstanding Balance" => "Due",
                        "Today's Collections" => "Today",
                        default => $widget->description
                    };

                    $borderClass = match ($widget->variant) {
                        'danger' => 'border-rose-300 ring-1 ring-rose-100/50 bg-rose-50/5',
                        'warning' => 'border-amber-300 ring-1 ring-amber-100/50 bg-amber-50/5',
                        default => 'border-neutral-200',
                    };
                @endphp
                <a href="{{ $widget->href }}" class="relative bg-white rounded-xl border {{ $borderClass }} shadow-xs p-3.5 flex flex-col justify-between h-[96px] hover:border-neutral-300 transition-colors">
                    <div class="flex items-center justify-between w-full">
                        <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider truncate pr-1">
                            {{ $mobLabel }}
                        </span>
                        <x-icons.lucide name="{{ $widget->icon }}" class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
                    </div>
                    <div class="flex items-baseline justify-between w-full mt-1.5">
                        <span class="text-lg font-extrabold text-neutral-800 tracking-tight tabular-nums truncate">
                            {{ $widget->value }}
                        </span>
                        <span class="text-[9px] font-bold text-neutral-400 uppercase tracking-wider pl-1 shrink-0">
                            {{ $mobDesc }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 2. More Metrics Accordion -->
        <div x-data="{ expanded: false }" class="mt-3">
            <button 
                @click="expanded = !expanded" 
                type="button" 
                class="w-full flex items-center justify-between px-4 py-2.5 bg-white border border-neutral-200 rounded-xl text-xs font-bold text-neutral-700 hover:bg-neutral-50 transition-colors shadow-xs"
            >
                <span class="flex items-center gap-1.5">
                    <x-icons.lucide name="lucide-bar-chart-2" class="w-4 h-4 text-neutral-400" />
                    <span x-text="expanded ? 'Hide Extra Metrics' : 'More Metrics'">More Metrics</span>
                </span>
                <x-icons.lucide name="lucide-chevron-down" class="w-4 h-4 text-neutral-400 transition-transform duration-200" ::class="{ 'rotate-180': expanded }" />
            </button>

            <div 
                x-show="expanded" 
                x-transition
                class="grid grid-cols-2 gap-3 mt-3"
            >
                @foreach($secondaryWidgets as $widget)
                    @php
                        $mobLabel = match($widget->label) {
                            "Advance Payments Pending" => "Advance Due",
                            "Low Stock SKUs" => "Low Stock",
                            "Purchase Orders" => "Purchase",
                            default => $widget->label
                        };

                        $borderClass = match ($widget->variant) {
                            'danger' => 'border-rose-300 ring-1 ring-rose-100/50 bg-rose-50/5',
                            'warning' => 'border-amber-300 ring-1 ring-amber-100/50 bg-amber-50/5',
                            default => 'border-neutral-200',
                        };
                    @endphp
                    <a href="{{ $widget->href }}" class="relative bg-white rounded-xl border {{ $borderClass }} shadow-xs p-3.5 flex flex-col justify-between h-[96px] hover:border-neutral-300 transition-colors">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-[10px] font-bold text-neutral-500 uppercase tracking-wider truncate pr-1">
                                {{ $mobLabel }}
                            </span>
                            <x-icons.lucide name="{{ $widget->icon }}" class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
                        </div>
                        <div class="flex items-baseline justify-between w-full mt-1.5">
                            <span class="text-lg font-extrabold text-neutral-800 tracking-tight tabular-nums truncate">
                                {{ $widget->value }}
                            </span>
                            <span class="text-[9px] font-bold text-neutral-400 uppercase tracking-wider pl-1 shrink-0">
                                Alert
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- 3. Tab Switchable Charts -->
        <div x-data="{ activeTab: 'revenue' }" class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-neutral-100 pb-3 mb-4">
                <div class="flex gap-1.5 p-0.5 bg-neutral-100 rounded-lg shrink-0">
                    <button 
                        type="button"
                        @click="activeTab = 'revenue'" 
                        class="px-3 py-1.5 rounded-md text-[11px] font-bold transition-all"
                        :class="activeTab === 'revenue' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'"
                    >
                        Revenue
                    </button>
                    <button 
                        type="button"
                        @click="activeTab = 'orders'" 
                        class="px-3 py-1.5 rounded-md text-[11px] font-bold transition-all"
                        :class="activeTab === 'orders' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-500'"
                    >
                        Orders
                    </button>
                </div>

                <div class="text-right">
                    <span class="text-[9px] font-bold text-neutral-400 uppercase tracking-wider">Trend</span>
                    <span class="block text-xs font-bold text-emerald-600" x-show="activeTab === 'revenue'">
                        {{ $revenueSeries->changePercent !== null ? abs($revenueSeries->changePercent) . '%' : '' }}
                    </span>
                    <span class="block text-xs font-bold text-emerald-600" x-show="activeTab === 'orders'">
                        {{ $ordersSeries->changePercent !== null ? abs($ordersSeries->changePercent) . '%' : '' }}
                    </span>
                </div>
            </div>

            <!-- Revenue Trend Chart in Tab -->
            <div x-show="activeTab === 'revenue'" x-data="{ activePoint: null }" class="relative">
                @php
                    $revenueLayout = \App\Presenters\ChartGeometryPresenter::present($revenueSeries, 450, 180, 45, 20);
                    $linePath = \App\Support\Dashboard\ChartPathBuilder::toLinePath($revenueLayout->coordinates);
                    $areaPath = \App\Support\Dashboard\ChartPathBuilder::toAreaPath($revenueLayout->coordinates, $revenueLayout->baselineY);
                    $hasRevenueData = $revenueSeries->points->contains(fn($p) => $p->value > 0);
                @endphp

                @if(!$hasRevenueData)
                    <div class="h-44 flex items-center justify-center bg-neutral-50 border border-dashed border-neutral-200 rounded-xl">
                        <p class="text-xs text-neutral-400">No sales revenue logged.</p>
                    </div>
                @else
                    <svg viewBox="0 0 450 180" class="w-full h-44 overflow-visible select-none">
                        <defs>
                            <linearGradient id="areaGradientMob" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" style="stop-color: var(--color-{{ $revenueSeries->color }});" stop-opacity="0.2" />
                                <stop offset="100%" style="stop-color: var(--color-{{ $revenueSeries->color }});" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        @foreach($revenueLayout->ticks as $tick)
                            <line x1="45" y1="{{ $tick['y'] }}" x2="430" y2="{{ $tick['y'] }}" stroke="#f3f4f6" stroke-width="1" />
                        @endforeach
                        <line x1="45" y1="{{ $revenueLayout->baselineY }}" x2="430" y2="{{ $revenueLayout->baselineY }}" stroke="#e5e7eb" stroke-width="1.5" />
                        <path d="{{ $areaPath }}" fill="url(#areaGradientMob)" />
                        <path d="{{ $linePath }}" fill="none" style="stroke: var(--color-{{ $revenueSeries->color }});" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        @foreach($revenueLayout->coordinates as $index => $pt)
                            <circle 
                                cx="{{ $pt['x'] }}" 
                                cy="{{ $pt['y'] }}" 
                                r="5.5" 
                                fill="white" 
                                style="stroke: var(--color-{{ $revenueSeries->color }});" 
                                stroke-width="2.5"
                                @mouseenter="activePoint = @js($pt)"
                                @mouseleave="activePoint = null"
                                @click="activePoint = (activePoint && activePoint.x === @js($pt['x'])) ? null : @js($pt)"
                            />
                        @endforeach
                        @foreach($revenueLayout->coordinates as $pt)
                            <text x="{{ $pt['x'] }}" y="175" text-anchor="middle" class="text-[9px] font-bold fill-neutral-400 uppercase tracking-wider">{{ $pt['label'] }}</text>
                        @endforeach
                    </svg>

                    <div 
                        x-show="activePoint" 
                        x-transition 
                        class="absolute z-50 bg-neutral-900 text-white text-[10px] rounded-lg p-2 shadow-xl border border-neutral-800 pointer-events-none"
                        :style="`left: ${activePoint ? (activePoint.x - 35) : 0}px; top: ${activePoint ? (activePoint.y - 45) : 0}px;`"
                    >
                        <span class="block font-bold text-[8px] text-neutral-400 uppercase tracking-wide" x-text="activePoint ? activePoint.label : ''"></span>
                        <span class="block font-bold text-xs mt-0.5" x-text="activePoint ? activePoint.formatted : ''"></span>
                    </div>
                @endif
            </div>

            <!-- Orders Volume Chart in Tab -->
            <div x-show="activeTab === 'orders'" x-data="{ activeBar: null }" class="relative">
                @php
                    $ordersLayout = \App\Presenters\ChartGeometryPresenter::present($ordersSeries, 450, 180, 45, 20);
                    $hasOrdersData = $ordersSeries->points->contains(fn($p) => $p->value > 0);
                @endphp

                @if(!$hasOrdersData)
                    <div class="h-44 flex items-center justify-center bg-neutral-50 border border-dashed border-neutral-200 rounded-xl">
                        <p class="text-xs text-neutral-400">No monthly orders logged.</p>
                    </div>
                @else
                    <svg viewBox="0 0 450 180" class="w-full h-44 overflow-visible select-none">
                        @foreach($ordersLayout->ticks as $tick)
                            <line x1="45" y1="{{ $tick['y'] }}" x2="430" y2="{{ $tick['y'] }}" stroke="#f3f4f6" stroke-width="1" />
                        @endforeach
                        <line x1="45" y1="{{ $ordersLayout->baselineY }}" x2="430" y2="{{ $ordersLayout->baselineY }}" stroke="#e5e7eb" stroke-width="1.5" />
                        
                        @php
                            $n = count($ordersLayout->coordinates);
                            $bandWidth = (430 - 45) / max(1, $n);
                        @endphp
                        @foreach($ordersLayout->coordinates as $index => $pt)
                            @php
                                $ptX = 45 + ($index * $bandWidth) + ($bandWidth / 2);
                                $barWidth = 20;
                                $barHeight = max(2, $ordersLayout->baselineY - $pt['y']);
                                $barX = $ptX - ($barWidth / 2);
                                $barY = $pt['y'];
                                $ptData = array_merge($pt, ['x' => $ptX]);
                            @endphp
                            <rect 
                                x="{{ $barX }}" 
                                y="{{ $barY }}" 
                                width="{{ $barWidth }}" 
                                height="{{ $barHeight }}" 
                                rx="3.5"
                                style="fill: var(--color-{{ $ordersSeries->color }});" 
                                @mouseenter="activeBar = @js($ptData)"
                                @mouseleave="activeBar = null"
                            />
                        @endforeach
                        @foreach($ordersLayout->coordinates as $index => $pt)
                            @php
                                $ptX = 45 + ($index * $bandWidth) + ($bandWidth / 2);
                            @endphp
                            <text x="{{ $ptX }}" y="175" text-anchor="middle" class="text-[9px] font-bold fill-neutral-400 uppercase tracking-wider">{{ $pt['label'] }}</text>
                        @endforeach
                    </svg>

                    <div 
                        x-show="activeBar" 
                        x-transition 
                        class="absolute z-50 bg-neutral-900 text-white text-[10px] rounded-lg p-2 shadow-xl border border-neutral-800 pointer-events-none"
                        :style="`left: ${activeBar ? (activeBar.x - 35) : 0}px; top: ${activeBar ? (activeBar.y - 45) : 0}px;`"
                    >
                        <span class="block font-bold text-[8px] text-neutral-400 uppercase tracking-wide" x-text="activeBar ? activeBar.label : ''"></span>
                        <span class="block font-bold text-xs mt-0.5" x-text="activeBar ? activeBar.formatted : ''"></span>
                    </div>
                @endif
            </div>
        </div>

        <!-- 4. Recent Activity (Mobile compact layout: emoji list, 60-70px tall) -->
        <div class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-xs">
            <h3 class="text-xs font-bold text-neutral-400 uppercase tracking-wider mb-3">Recent Activity</h3>
            
            <div class="space-y-2">
                @forelse($activities as $activity)
                    @php
                        $emoji = match($activity->icon) {
                            'lucide-shopping-cart' => '🛒',
                            'lucide-credit-card' => '💳',
                            'lucide-alert-circle' => '⚠️',
                            'lucide-user' => '👤',
                            'lucide-truck' => '🚚',
                            default => '📝'
                        };
                        $shortTime = str_replace(
                            [' hours ago', ' minutes ago', ' days ago', ' hour ago', ' minute ago', ' day ago'],
                            ['h ago', 'm ago', 'd ago', 'h ago', 'm ago', 'd ago'],
                            $activity->formatTimeForDashboard()
                        );
                    @endphp
                    <a href="{{ $activity->href }}" class="flex items-center justify-between p-3 bg-neutral-50 border border-neutral-200 rounded-xl text-xs hover:bg-neutral-100 transition-colors">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-base shrink-0">{{ $emoji }}</span>
                            <div class="min-w-0">
                                <span class="font-bold text-neutral-800 truncate block">{{ $activity->title }}</span>
                                <span class="text-[10px] text-neutral-400 truncate block">by {{ $activity->actorName }}</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-neutral-400 font-mono shrink-0 pl-2">{{ $shortTime }}</span>
                    </a>
                @empty
                    <div class="text-center py-4 text-xs text-neutral-400">No activity logged yet.</div>
                @endforelse
            </div>
        </div>

        <!-- 5. Collapsible Help info at the bottom -->
        <div x-data="{ expanded: false }" class="bg-white border border-neutral-200 rounded-2xl p-4 shadow-xs">
            <button 
                @click="expanded = !expanded" 
                type="button" 
                class="w-full flex items-center justify-between text-xs font-bold text-neutral-700 hover:text-neutral-800"
            >
                <span class="flex items-center gap-1.5">
                    <x-icons.lucide name="lucide-help-circle" class="w-4 h-4 text-neutral-400" />
                    Dashboard Help & Info
                </span>
                <x-icons.lucide name="lucide-chevron-down" class="w-4 h-4 text-neutral-400 transition-transform duration-200" ::class="{ 'rotate-180': expanded }" />
            </button>
            <div x-show="expanded" x-transition class="mt-3 text-xs text-neutral-500 leading-relaxed pt-3 border-t border-neutral-100">
                The metrics widgets above represent live system metrics aggregated from your orders, payments, stock levels, and purchase orders. Click on any widget to drill down directly to the corresponding module interface.
            </div>
        </div>
    </div>


    <!-- DESKTOP VIEW (hidden lg:block) - Spacious layout with splits and sidebars -->
    <div class="hidden lg:block">
        <!-- Stat KPI Widgets Grid -->
        <x-stat.grid>
            @foreach($widgets as $widget)
                <x-stat.card :widget="$widget" />
            @endforeach
        </x-stat.grid>

        <!-- Split Columns Grid (16px/gap-4 mobile, 32px/gap-8 desktop gap spacing) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8 items-start">
            <!-- Left 2 Cols: Charts and Alerts -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Charts Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Revenue line chart -->
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
                                <svg viewBox="0 0 450 180" class="w-full h-full overflow-visible select-none">
                                    <defs>
                                        <linearGradient id="areaGradientDesk" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" style="stop-color: var(--color-{{ $revenueSeries->color }});" stop-opacity="0.2" />
                                            <stop offset="100%" style="stop-color: var(--color-{{ $revenueSeries->color }});" stop-opacity="0" />
                                        </linearGradient>
                                    </defs>
                                    @foreach($revenueLayout->ticks as $tick)
                                        <line x1="45" y1="{{ $tick['y'] }}" x2="430" y2="{{ $tick['y'] }}" stroke="#f3f4f6" stroke-width="1" />
                                    @endforeach
                                    <line x1="45" y1="{{ $revenueLayout->baselineY }}" x2="430" y2="{{ $revenueLayout->baselineY }}" stroke="#e5e7eb" stroke-width="1.5" />
                                    <path d="{{ $areaPath }}" fill="url(#areaGradientDesk)" />
                                    <path d="{{ $linePath }}" fill="none" style="stroke: var(--color-{{ $revenueSeries->color }});" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                    @foreach($revenueLayout->coordinates as $index => $pt)
                                        <circle 
                                            cx="{{ $pt['x'] }}" 
                                            cy="{{ $pt['y'] }}" 
                                            r="6" 
                                            fill="white" 
                                            style="stroke: var(--color-{{ $revenueSeries->color }});" 
                                            stroke-width="2.5"
                                            @mouseenter="activePoint = @js($pt)"
                                            @mouseleave="activePoint = null"
                                            @click="activePoint = (activePoint && activePoint.x === @js($pt['x'])) ? null : @js($pt)"
                                        />
                                    @endforeach
                                    @foreach($revenueLayout->coordinates as $pt)
                                        <text x="{{ $pt['x'] }}" y="175" text-anchor="middle" class="text-[9px] font-bold fill-neutral-400 uppercase tracking-wider">{{ $pt['label'] }}</text>
                                    @endforeach
                                </svg>
                                <div 
                                    x-show="activePoint" 
                                    x-transition 
                                    class="absolute z-50 bg-neutral-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl border border-neutral-800 pointer-events-none"
                                    :style="`left: ${activePoint ? (activePoint.x - 35) : 0}px; top: ${activePoint ? (activePoint.y - 50) : 0}px;`"
                                >
                                    <span class="block font-bold text-[9px] text-neutral-400 uppercase tracking-wide" x-text="activePoint ? activePoint.label : ''"></span>
                                    <span class="block font-bold text-xs mt-0.5" x-text="activePoint ? activePoint.formatted : ''"></span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Orders volume bar chart -->
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
                                <svg viewBox="0 0 450 180" class="w-full h-full overflow-visible select-none">
                                    @foreach($ordersLayout->ticks as $tick)
                                        <line x1="45" y1="{{ $tick['y'] }}" x2="430" y2="{{ $tick['y'] }}" stroke="#f3f4f6" stroke-width="1" />
                                    @endforeach
                                    <line x1="45" y1="{{ $ordersLayout->baselineY }}" x2="430" y2="{{ $ordersLayout->baselineY }}" stroke="#e5e7eb" stroke-width="1.5" />
                                    
                                    @php
                                        $n = count($ordersLayout->coordinates);
                                        $bandWidth = (430 - 45) / max(1, $n);
                                    @endphp
                                    @foreach($ordersLayout->coordinates as $index => $pt)
                                        @php
                                            $ptX = 45 + ($index * $bandWidth) + ($bandWidth / 2);
                                            $barWidth = 24;
                                            $barHeight = max(2, $ordersLayout->baselineY - $pt['y']);
                                            $barX = $ptX - ($barWidth / 2);
                                            $barY = $pt['y'];
                                            $ptData = array_merge($pt, ['x' => $ptX]);
                                        @endphp
                                        <rect 
                                            x="{{ $barX }}" 
                                            y="{{ $barY }}" 
                                            width="{{ $barWidth }}" 
                                            height="{{ $barHeight }}" 
                                            rx="4"
                                            style="fill: var(--color-{{ $ordersSeries->color }});" 
                                            @mouseenter="activeBar = @js($ptData)"
                                            @mouseleave="activeBar = null"
                                        />
                                    @endforeach
                                    @foreach($ordersLayout->coordinates as $index => $pt)
                                        @php
                                            $ptX = 45 + ($index * $bandWidth) + ($bandWidth / 2);
                                        @endphp
                                        <text x="{{ $ptX }}" y="175" text-anchor="middle" class="text-[9px] font-bold fill-neutral-400 uppercase tracking-wider">{{ $pt['label'] }}</text>
                                    @endforeach
                                </svg>
                                <div 
                                    x-show="activeBar" 
                                    x-transition 
                                    class="absolute z-50 bg-neutral-900 text-white text-[11px] rounded-xl p-2.5 shadow-xl border border-neutral-800 pointer-events-none"
                                    :style="`left: ${activeBar ? (activeBar.x - 35) : 0}px; top: ${activeBar ? (activeBar.y - 50) : 0}px;`"
                                >
                                    <span class="block font-bold text-[9px] text-neutral-400 uppercase tracking-wide" x-text="activeBar ? activeBar.label : ''"></span>
                                    <span class="block font-bold text-xs mt-0.5" x-text="activeBar ? activeBar.formatted : ''"></span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Guidance & Alerts -->
                @if($isEmptyState)
                    <x-alert type="info" title="Welcome to your new dashboard!" dismissible="false">
                        <p class="text-sm text-blue-900">
                            Your database is currently empty. Start by creating your first sales orders or syncing catalogs via Google Sheets!
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('admin.sales_orders.create') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold bg-[color:var(--color-brand-600)] text-white rounded-lg hover:bg-[color:var(--color-brand-700)] transition-colors">
                                Create Sales Order
                            </a>
                        </div>
                    </x-alert>
                @endif
            </div>

            <!-- Right 1 Col: Recent Activity timeline -->
            <div class="bg-white border border-[color:var(--color-border)] rounded-2xl p-6 shadow-xs flex flex-col">
                <h3 class="text-sm font-bold text-neutral-800 mb-5">Recent Activity</h3>
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
    </div>


    <!-- STICKY KPI SUMMARY NAVBAR (Mobile Only) -->
    <div 
        x-data="{ 
            showSticky: false,
            init() {
                window.addEventListener('scroll', () => {
                    this.showSticky = window.scrollY > 200 && window.innerWidth < 1024;
                });
            }
        }"
        x-show="showSticky"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-full"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur-md border-b border-neutral-200 shadow-sm py-2 px-4 z-40 flex items-center justify-between lg:hidden"
        style="display: none;"
    >
        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Summary</span>
        <div class="flex items-center gap-4 text-xs font-bold text-neutral-700">
            <span>Ord: <span class="text-neutral-900">{{ $widgets[0]->value }}</span></span>
            <span class="w-px h-3.5 bg-neutral-200"></span>
            <span>Pend: <span class="text-neutral-900">{{ $widgets[1]->value }}</span></span>
            <span class="w-px h-3.5 bg-neutral-200"></span>
            <span>Due: <span class="text-neutral-900">{{ $widgets[3]->value }}</span></span>
        </div>
    </div>


    <!-- FLOATING ACTION BUTTON (Mobile Only) -->
    <div 
        x-data="{ open: false }" 
        class="fixed bottom-6 right-6 z-40 lg:hidden"
    >
        <!-- FAB Button -->
        <button 
            @click="open = !open" 
            type="button" 
            class="w-12 h-12 bg-neutral-800 text-white rounded-full flex items-center justify-center shadow-lg focus:outline-none focus:ring-2 focus:ring-[color:var(--focus-ring-color)] transition-transform duration-200"
            :class="{ 'rotate-45 bg-rose-600': open }"
            aria-label="Quick actions menu"
        >
            <x-icons.lucide name="lucide-plus" class="w-6 h-6" />
        </button>

        <!-- FAB Dropdown Menu -->
        <div 
            x-show="open" 
            x-transition
            @click.away="open = false" 
            class="absolute bottom-14 right-0 w-48 bg-white border border-neutral-200 rounded-2xl shadow-xl py-2"
        >
            @if(Route::has('admin.sales_orders.create'))
                <a href="{{ route('admin.sales_orders.create') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50">
                    <x-icons.lucide name="lucide-shopping-cart" class="w-4 h-4 text-neutral-400" />
                    Create Order
                </a>
            @endif
            <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 border-t border-neutral-100">
                <x-icons.lucide name="lucide-credit-card" class="w-4 h-4 text-neutral-400" />
                Record Payment
            </a>
            <a href="{{ route('admin.accounting.customer_ledger') }}" class="flex items-center gap-2 px-4 py-2.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 border-t border-neutral-100">
                <x-icons.lucide name="lucide-user" class="w-4 h-4 text-neutral-400" />
                View Ledger
            </a>
        </div>
    </div>


    <!-- CSS Micro-Animations -->
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
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
    </style>
</x-layouts.admin>
