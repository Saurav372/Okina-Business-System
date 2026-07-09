@props([
    'value' => null,
    'min' => 0,
    'max' => 100,
    'size' => 'md',
    'intent' => 'primary',
    'rounded' => 'full',
    'striped' => false,
    'animated' => false,
    'showLabel' => false,
    'label' => null,
    'labelPosition' => 'top',
    'srOnlyLabel' => null,
    'xBind' => null,
])

@php
    $min = (float)$min;
    $max = (float)$max;
    // Normalize range if max <= min
    if ($max <= $min) {
        $max = $min + 1;
    }

    $isDeterminate = ($value !== null && $value !== '') || $xBind !== null;
    $percent = 0;
    $clampedValue = 0;

    if ($isDeterminate && $value !== null && $value !== '') {
        $clampedValue = max($min, min($max, (float)$value));
        $percent = max(0, min(100, (($clampedValue - $min) / ($max - $min)) * 100));
    }

    // Defensive Float formatting: rounds to max 1 decimal, displays integers cleanly
    $roundedPercent = round($percent, 1);
    $formattedPercent = fmod($roundedPercent, 1.0) === 0.0
        ? number_format($roundedPercent, 0)
        : number_format($roundedPercent, 1);
    $formattedPercent .= '%';

    // Label Precedence: custom label takes absolute precedence over percentage
    $resolvedLabel = filled($label) ? $label : ($isDeterminate ? $formattedPercent : null);

    // Inline threshold check (Visual threshold constant based on available text space)
    $inlineLabelThreshold = 10;
    $showInlineLabel = $isDeterminate && ($xBind !== null || $percent >= $inlineLabelThreshold);

    // Style generation
    $progressStyle = '';
    if ($isDeterminate && $xBind === null) {
        $progressStyle = "width: {$percent}%;";
        if ($percent > 0 && $percent < 2) {
            $progressStyle .= " min-width: 4px;";
        }
    }

    // Size mappings
    $sizes = [
        'sm' => 'h-1.5',
        'md' => 'h-2.5',
        'lg' => 'h-4',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];

    // Rounded mappings
    $roundings = [
        'full' => 'rounded-full',
        'md' => 'rounded-md',
        'none' => 'rounded-none',
    ];
    $roundedClass = $roundings[$rounded] ?? $roundings['full'];

    // Intent Mappings
    $intents = [
        'primary' => ['bar' => 'bg-[color:var(--color-primary-600)]', 'track' => 'bg-[color:var(--color-primary-100)]', 'text' => 'text-white'],
        'secondary' => ['bar' => 'bg-[color:var(--color-secondary-500)]', 'track' => 'bg-[color:var(--color-secondary-100)]', 'text' => 'text-white'],
        'success' => ['bar' => 'bg-emerald-500', 'track' => 'bg-emerald-100', 'text' => 'text-white'],
        'danger' => ['bar' => 'bg-rose-500', 'track' => 'bg-rose-100', 'text' => 'text-white'],
        'warning' => ['bar' => 'bg-amber-500', 'track' => 'bg-amber-100', 'text' => 'text-amber-950'],
        'neutral' => ['bar' => 'bg-[color:var(--color-neutral-600)]', 'track' => 'bg-[color:var(--color-neutral-200)]', 'text' => 'text-white'],
    ];
    $intentConfig = $intents[$intent] ?? $intents['primary'];
    $barColorClass = $intentConfig['bar'];
    $trackColorClass = $intentConfig['track'];
    $textColorClass = $intentConfig['text'];

    // Inline Text Size Mapping
    $textSizes = [
        'sm' => 'text-[9px]',
        'md' => 'text-[11px]',
        'lg' => 'text-[13px]',
    ];
    $textSizeClass = $textSizes[$size] ?? $textSizes['md'];

    // Striped animation logic
    $stripedClass = $striped ? 'bg-progress-striped' : '';
    // Striped animation only applies to determinate mode to avoid visual noise
    $animationClass = ($striped && $animated && $isDeterminate) ? 'animate-progress-stripes' : '';
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <!-- Top Label -->
    @if($showLabel && $labelPosition === 'top' && filled($resolvedLabel))
        <div 
            @if($xBind) x-show="!({{ $xBind }} >= {{ $inlineLabelThreshold }} && '{{ $labelPosition }}' === 'inline')" @endif
            class="flex justify-between items-center mb-1 text-xs font-semibold text-[color:var(--color-text-secondary)]"
        >
            <span>{{ $srOnlyLabel ?? '' }}</span>
            <span @if($xBind && !filled($label)) x-text="Math.round({{ $xBind }}) + '%'" @endif>{{ $resolvedLabel }}</span>
        </div>
    @endif

    <!-- Track Container (clip clippings directly) -->
    <div 
        class="w-full relative overflow-hidden {{ $trackColorClass }} {{ $sizeClass }} {{ $roundedClass }}"
        role="progressbar"
        @if($isDeterminate)
            aria-valuenow="{{ $xBind === null ? round($clampedValue, 1) : 0 }}"
            @if($xBind)
                :aria-valuenow="Math.max({{ $min }}, Math.min({{ $max }}, {{ $xBind }}))"
                :aria-valuetext="{{ filled($label) ? 'true' : 'false' }} ? '{{ $label }}' : (Math.round({{ $xBind }}) + '%')"
            @endif
        @else
            aria-busy="true"
        @endif
        aria-valuemin="{{ $min }}"
        aria-valuemax="{{ $max }}"
        @if($xBind === null && filled($resolvedLabel))
            aria-valuetext="{{ $resolvedLabel }}"
        @endif
        @if(filled($srOnlyLabel))
            aria-label="{{ $srOnlyLabel }}"
        @endif
    >
        <!-- Active Bar Indicator -->
        <div 
            @if($isDeterminate)
                style="{{ $progressStyle }}"
                @if($xBind)
                    :style="'width: max(0%, min(100%, ' + {{ $xBind }} + '%))'"
                @endif
                class="h-full transition-progress overflow-hidden {{ $barColorClass }} {{ $roundedClass }} {{ $stripedClass }} {{ $animationClass }} flex items-center justify-center"
            @else
                class="h-full overflow-hidden {{ $barColorClass }} {{ $roundedClass }} {{ $stripedClass }} animate-progress-indeterminate"
            @endif
        >
            <!-- Inline Label (hidden if percent < 10% space threshold) -->
            @if($showLabel && $labelPosition === 'inline' && $showInlineLabel)
                <span 
                    @if($xBind)
                        x-show="{{ $xBind }} >= {{ $inlineLabelThreshold }}"
                        x-text="{{ filled($label) ? 'true' : 'false' }} ? '{{ $label }}' : (Math.round({{ $xBind }}) + '%')"
                    @endif
                    class="font-bold px-1 select-none leading-none truncate {{ $textColorClass }} {{ $textSizeClass }}"
                >
                    {{ $resolvedLabel }}
                </span>
            @endif
        </div>
    </div>

    <!-- Bottom Label -->
    @if($showLabel && $labelPosition === 'bottom' && filled($resolvedLabel))
        <div 
            @if($xBind) x-show="!({{ $xBind }} >= {{ $inlineLabelThreshold }} && '{{ $labelPosition }}' === 'inline')" @endif
            class="flex justify-between items-center mt-1 text-xs font-semibold text-[color:var(--color-text-secondary)]"
        >
            <span>{{ $srOnlyLabel ?? '' }}</span>
            <span @if($xBind && !filled($label)) x-text="Math.round({{ $xBind }}) + '%'" @endif>{{ $resolvedLabel }}</span>
        </div>
    @endif
</div>
