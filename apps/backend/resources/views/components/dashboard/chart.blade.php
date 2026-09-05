@props(['series', 'kind' => 'line'])
@php
    $layout = \App\Presenters\ChartGeometryPresenter::present($series, 480, 220, 55, 30);
    $money = $kind === 'line';
    $hasData = $series->points->contains(fn ($point) => $point->value != 0);
    $total = $money ? \App\Support\Dashboard\DashboardNumbers::money($series->currentValue ?? 0) : number_format($series->currentValue ?? 0);
    $path = \App\Support\Dashboard\ChartPathBuilder::toLinePath($layout->coordinates->slice(0, -1));
    $lastPath = \App\Support\Dashboard\ChartPathBuilder::toLinePath($layout->coordinates->slice(-2)->values());
@endphp
<section class="min-w-0 bg-white border border-[color:var(--color-border)] rounded-2xl p-5 shadow-xs flex flex-col justify-between" x-data="{ active: null }" @keydown.escape="active = null">
    <div>
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-neutral-900">{{ $series->title }}</h3>
                <p class="mt-0.5 text-xs text-neutral-500">{{ $series->periodLabel }}</p>
            </div>
            @if($series->partialLabel)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-neutral-100 text-neutral-600 shrink-0" title="Through {{ $series->partialLabel }}">
                    Partial month
                </span>
            @endif
        </div>

        <div class="mt-3 flex items-baseline gap-2.5 flex-wrap">
            <span class="text-2xl font-bold tabular-nums text-neutral-900">{{ $total }}</span>
            @if($series->changePercent !== null)
                <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-semibold {{ $series->changeDirection === 'up' ? 'bg-emerald-50 text-emerald-700' : ($series->changeDirection === 'down' ? 'bg-rose-50 text-rose-700' : 'bg-neutral-100 text-neutral-600') }}" title="Prior period: {{ $series->comparisonLabel }}">
                    {{ $series->changeDirection === 'up' ? '↑' : ($series->changeDirection === 'down' ? '↓' : '') }} {{ abs($series->changePercent) }}%
                </span>
                <span class="text-xs text-neutral-500" title="{{ $series->comparisonLabel }}">vs prior period</span>
            @else
                <span class="text-xs text-neutral-400 font-medium" title="Prior period: {{ $series->comparisonLabel }}">No prior period</span>
            @endif
        </div>
    </div>

    @if($hasData)
        <div class="relative mt-3">
            <svg viewBox="0 0 480 220" class="w-full overflow-visible" role="group" aria-label="{{ $series->title }} by month; focus a month for details">
                @foreach($layout->ticks as $tick)
                    @if($money || $tick['value'] == floor($tick['value']))
                        <line x1="55" x2="425" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" stroke="var(--color-border)" />
                        <text x="47" y="{{ $tick['y'] + 4 }}" text-anchor="end" class="text-[11px] fill-neutral-600">
                            {{ $money ? ($tick['value'] >= 100000 ? '₹'.round($tick['value'] / 100000, 1).'L' : ($tick['value'] >= 1000 ? '₹'.round($tick['value'] / 1000, 1).'K' : '₹'.$tick['value'])) : number_format($tick['value']) }}
                        </text>
                    @endif
                @endforeach
                @if($money)
                    <path d="{{ $path }}" fill="none" stroke="var(--color-{{ $series->color }})" stroke-width="2.5" />
                    <path d="{{ $lastPath }}" fill="none" stroke="var(--color-{{ $series->color }})" stroke-width="2.5" stroke-dasharray="5 4" />
                @endif
                @foreach($layout->coordinates as $index => $pt)
                    @php
                        $point = $series->points[$index];
                        $x = $money ? $pt['x'] : 55 + (370 / $series->points->count()) * ($index + .5);
                        $width = min(26, 240 / $series->points->count());
                        $detail = ['label' => $point->fullLabel, 'formatted' => $point->formattedValue, 'orders' => $point->orderCount, 'partial' => $point->partial, 'x' => $x / 480 * 100];
                        $accessible = $point->fullLabel.': '.$point->formattedValue.($point->partial ? ', '.$series->partialLabel : '');
                    @endphp
                    <g tabindex="0" role="button" aria-label="{{ $accessible }}" class="cursor-pointer focus:outline-2 focus:outline-blue-600"
                        @mouseenter="active = @js($detail)" @mouseleave="active = null"
                        @focus="active = @js($detail)" @blur="active = null"
                        @click="active = @js($detail)" @keydown.enter.prevent="active = @js($detail)" @keydown.space.prevent="active = @js($detail)">
                        <title>{{ $accessible }}</title>
                        @if($money)
                            <circle cx="{{ $x }}" cy="{{ $pt['y'] }}" r="12" fill="transparent" />
                            <circle cx="{{ $x }}" cy="{{ $pt['y'] }}" r="4.5" fill="white" stroke="var(--color-{{ $series->color }})" stroke-width="2" />
                        @else
                            <rect x="{{ $x - $width / 2 }}" y="{{ $pt['y'] }}" width="{{ $width }}" height="{{ max(0, $layout->baselineY - $pt['y']) }}" rx="3" fill="var(--color-{{ $series->color }})" opacity="{{ $point->partial ? '.45' : '1' }}" />
                            <rect x="{{ $x - $width / 2 }}" y="{{ min($pt['y'], $layout->baselineY - 24) }}" width="{{ $width }}" height="{{ max(24, $layout->baselineY - $pt['y']) }}" fill="transparent" />
                            <text x="{{ $x }}" y="{{ $pt['y'] - 7 }}" text-anchor="middle" class="text-[11px] font-semibold fill-neutral-700">{{ $point->value }}</text>
                        @endif
                    </g>
                    @if($series->points->count() <= 6 || $index % 2 === 0 || $loop->last)
                        <text x="{{ $x }}" y="212" text-anchor="middle" class="text-[11px] fill-neutral-600">{{ $pt['label'] }}{{ $point->partial ? '*' : '' }}</text>
                    @endif
                @endforeach
            </svg>
            <div x-cloak x-show="active" role="status" class="absolute top-0 z-10 w-44 -translate-x-1/2 rounded-lg bg-neutral-900 px-3 py-2 text-xs text-white shadow-md pointer-events-none"
                :style="{ left: 'clamp(88px, ' + (active?.x ?? 50) + '%, calc(100% - 88px))' }">
                <span class="block font-semibold" x-text="active?.label"></span>
                <span class="block mt-1" x-text="active?.formatted"></span>
                @if($money)<span class="block" x-text="active ? active.orders + ' orders' : ''"></span>@endif
                <span class="block text-neutral-300" x-show="active?.partial">{{ $series->partialLabel }}</span>
            </div>
        </div>
    @else
        <div class="mt-4 flex flex-col items-center justify-center min-h-44 rounded-xl bg-neutral-50/70 border border-dashed border-neutral-200 text-center p-6">
            <div class="w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center text-neutral-400 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            </div>
            <p class="text-xs text-neutral-500">No {{ $money ? 'order value' : 'orders' }} recorded for this period.</p>
        </div>
    @endif
    <details class="mt-3 border-t border-neutral-100 pt-2.5 text-xs text-neutral-500 group">
        <summary class="cursor-pointer font-medium text-neutral-600 hover:text-neutral-900 list-none flex items-center gap-1.5 transition-colors">
            <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-90 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            <span>Monthly breakdown</span>
        </summary>
        <table class="mt-2 w-full text-left tabular-nums">
            <caption class="sr-only">{{ $series->title }} monthly values</caption>
            <thead><tr class="border-b border-neutral-100"><th scope="col" class="py-1.5 font-medium text-neutral-500">Month</th><th scope="col" class="text-right py-1.5 font-medium text-neutral-500">{{ $money ? 'Order value' : 'Orders' }}</th></tr></thead>
            <tbody class="divide-y divide-neutral-50">@foreach($series->points as $point)<tr><th scope="row" class="py-1 font-normal text-neutral-700">{{ $point->fullLabel }}{{ $point->partial ? '*' : '' }}</th><td class="text-right py-1 text-neutral-800 font-medium">{{ $point->formattedValue }}</td></tr>@endforeach</tbody>
        </table>
    </details>
</section>
