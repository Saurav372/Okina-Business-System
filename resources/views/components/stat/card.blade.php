@props([
    'widget' => null,
    'label' => null,
    'value' => null,
    'trend' => null,
    'trendDirection' => 'neutral',
    'description' => null,
    'href' => null,
    'variant' => 'neutral',
    'accessibilityLabel' => null,
    'action' => null,
    'primary' => false,
    'detail' => null,
])

@php
    // If a DTO widget object is provided, extract its attributes
    if ($widget) {
        $label = $widget->label;
        $value = $widget->value;
        $trend = $widget->trend;
        $trendDirection = $widget->trendDirection;
        $description = $widget->description;
        $href = $widget->href;
        $variant = $widget->variant;
        $accessibilityLabel = $widget->accessibilityLabel;
        $iconName = $widget->icon;
        $action = $widget->action;
        $primary = $widget->primary;
        $detail = $widget->detail;
    } else {
        $iconName = null;
    }

    $isInteractive = filled($href);
    $wrapperTag = $isInteractive ? 'a' : 'div';
    
    // Normalize trend direction to prevent invalid classes
    $normalizedDirection = in_array($trendDirection, ['up', 'down', 'neutral']) ? $trendDirection : 'neutral';

    $trendStyles = [
        'up' => 'text-[color:var(--color-success)]',
        'down' => 'text-[color:var(--color-danger)]',
        'neutral' => 'text-[color:var(--color-neutral-500)]',
    ];

    $currentTrendStyle = $trendStyles[$normalizedDirection];

    // Priority variant states border styling
    $variantBorderClasses = match ($variant) {
        'danger' => 'border-rose-300 ring-2 ring-rose-100/50 bg-rose-50/5',
        'warning' => 'border-amber-300 ring-2 ring-amber-100/50 bg-amber-50/5',
        default => 'border-neutral-200/80',
    };

    $interactiveClasses = $isInteractive 
        ? 'cursor-pointer hover:-translate-y-0.5 hover:border-neutral-300 hover:shadow-md active:scale-[0.99] transition-all duration-150 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-2 block' 
        : '';
@endphp

<{{ $wrapperTag }} 
    @if($isInteractive) href="{{ $href }}" @endif 
    @if($accessibilityLabel) aria-label="{{ $accessibilityLabel }}" @endif
    {{ $attributes->class([
        'relative bg-white rounded-xl shadow-[0_1px_3px_rgba(0,0,0,0.03)] border p-4 sm:p-5 overflow-hidden flex flex-col justify-between h-full',
        $variantBorderClasses,
        'border-t-2 border-t-[color:var(--color-brand-600)]' => $primary,
        $interactiveClasses
    ]) }}
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex flex-col flex-1 min-w-0">
            <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-500 line-clamp-1 pr-2" title="{{ $label }}">
                {{ $label }}
            </span>
            <span class="mt-2 text-2xl sm:text-[28px] font-bold text-neutral-900 leading-none tracking-tight tabular-nums break-words break-all sm:break-normal">
                {{ $value }}
            </span>
        </div>
        
        @if(isset($icon))
            <div class="flex items-center justify-center shrink-0 w-8 h-8 rounded-lg bg-neutral-100/80 border border-neutral-200/60 text-neutral-600" aria-hidden="true" focusable="false">
                {{ $icon }}
            </div>
        @elseif($iconName)
            <div class="flex items-center justify-center shrink-0 w-8 h-8 rounded-lg bg-neutral-100/80 border border-neutral-200/60 text-neutral-600" aria-hidden="true" focusable="false">
                <x-icons.lucide name="{{ $iconName }}" class="w-4 h-4" />
            </div>
        @endif
    </div>

    @php
        $hasSignificantValue = !in_array((string) $value, ['0', '₹0', '0.00', '₹0.00', ''], true);
    @endphp

    @if($variant === 'warning' || $variant === 'danger')
        <div class="mt-3 flex items-center">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $variant === 'danger' ? 'bg-rose-50 text-rose-700 border border-rose-200/50' : 'bg-amber-50 text-amber-800 border border-amber-200/50' }}">
                {{ $description }}
            </span>
        </div>
    @elseif($trend)
        <div class="mt-3 flex items-center flex-wrap gap-1.5 text-xs">
            <span class="inline-flex items-center gap-1 font-semibold {{ $currentTrendStyle }}">
                @if($normalizedDirection === 'up')
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span class="sr-only">Trend increased by {{ $trend }} compared to the previous period.</span>
                @elseif($normalizedDirection === 'down')
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                    <span class="sr-only">Trend decreased by {{ $trend }} compared to the previous period.</span>
                @else
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14"></path></svg>
                    <span class="sr-only">No significant change. Trend {{ $trend }}.</span>
                @endif
                <span aria-hidden="true">{{ $trend }}</span>
            </span>
            @if($hasSignificantValue && $description)
                <span class="text-neutral-500 font-normal">{{ $description }}</span>
            @endif
        </div>
    @elseif($hasSignificantValue && $description)
        <div class="mt-3 text-xs text-neutral-500 font-normal line-clamp-1" title="{{ $description }}">
            {{ $description }}
        </div>
    @endif

    @if($hasSignificantValue && $detail)
        <p class="mt-1 text-xs leading-relaxed text-neutral-400 line-clamp-1" title="{{ $detail }}">{{ $detail }}</p>
    @endif

    @if($action && $href)
        <span class="mt-auto pt-3 text-xs font-medium text-neutral-500 hover:text-neutral-900 transition-colors inline-flex items-center gap-1">
            {{ $action }} <span aria-hidden="true">→</span>
        </span>
    @endif
</{{ $wrapperTag }}>
