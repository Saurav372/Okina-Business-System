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
        default => 'border-[color:var(--color-border)]',
    };

    $interactiveClasses = $isInteractive 
        ? 'cursor-pointer hover:-translate-y-0.5 hover:border-[color:var(--color-neutral-300)] hover:shadow-[var(--shadow-md)] transition-all duration-[var(--duration-200)] ease-[var(--ease-out)] focus-visible:outline-none focus-visible:ring-[length:var(--focus-ring-width)] focus-visible:ring-[color:var(--focus-ring-color)] focus-visible:ring-offset-[length:var(--focus-ring-offset)] block' 
        : '';
@endphp

<{{ $wrapperTag }} 
    @if($isInteractive) href="{{ $href }}" @endif 
    @if($accessibilityLabel) aria-label="{{ $accessibilityLabel }}" @endif
    {{ $attributes->class([
        'relative bg-white rounded-[var(--radius-xl)] shadow-[var(--shadow-xs)] p-6 overflow-hidden flex flex-col h-full',
        $variantBorderClasses,
        $interactiveClasses
    ]) }}
>
    <div class="flex items-start justify-between gap-4">
        <div class="flex flex-col flex-1 min-w-0">
            <span class="text-[15px] font-semibold text-[color:var(--color-neutral-700)] line-clamp-2 min-h-[2.75rem] leading-snug pr-2" title="{{ $label }}">
                {{ $label }}
            </span>
            <span class="mt-3 text-[32px] font-bold text-[color:var(--color-text-primary)] leading-none tracking-tight tabular-nums break-words break-all sm:break-normal">
                {{ $value }}
            </span>
        </div>
        
        @if(isset($icon))
            <div class="flex items-center justify-center shrink-0 text-[color:var(--color-neutral-400)] pt-1" aria-hidden="true" focusable="false">
                {{ $icon }}
            </div>
        @elseif($iconName)
            <div class="flex items-center justify-center shrink-0 text-[color:var(--color-neutral-400)] pt-1" aria-hidden="true" focusable="false">
                <x-icons.lucide name="{{ $iconName }}" class="w-5 h-5" />
            </div>
        @endif
    </div>

    @if($trend || $description)
        <div class="mt-5 flex items-center flex-wrap gap-1.5 text-[14px]">
            @if($trend)
                <span class="inline-flex items-center gap-1 font-semibold {{ $currentTrendStyle }}">
                    @if($normalizedDirection === 'up')
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        <span class="sr-only">Trend increased by {{ $trend }} compared to the previous period.</span>
                    @elseif($normalizedDirection === 'down')
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                        <span class="sr-only">Trend decreased by {{ $trend }} compared to the previous period.</span>
                    @else
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" focusable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14"></path></svg>
                        <span class="sr-only">No significant change. Trend {{ $trend }}.</span>
                    @endif
                    <span aria-hidden="true">{{ $trend }}</span>
                </span>
            @endif

            @if($description)
                <span class="text-[color:var(--color-neutral-500)] font-medium">{{ $description }}</span>
            @endif
        </div>
    @endif
</{{ $wrapperTag }}>
