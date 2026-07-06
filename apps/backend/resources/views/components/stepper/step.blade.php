@aware([
    'active' => 1,
    'total' => null,
    'orientation' => 'horizontal',
    'size' => 'md',
    'showNumbers' => true
])

@props([
    'step',
    'title',
    'description' => null,
    'status' => null,
    'disabled' => false,
    'busy' => false,
    'href' => null,
    'url' => null,
])

@php
    $link = $href ?? $url;
    $isDisabled = $disabled || $busy;
    $isInteractive = filled($link) && !$isDisabled;

    // Normalize status based on step vs active
    $status ??= match (true) {
        $step < $active => 'completed',
        $step === $active => 'current',
        default => 'pending',
    };

    $isVertical = $orientation === 'vertical';

    // Circle style matching status
    $circleStyles = match($status) {
        'completed' => 'bg-[color:var(--step-color-active)] border-[color:var(--step-color-active)] text-white',
        'current' => 'bg-white border-2 border-[color:var(--step-color-active)] text-[color:var(--step-color-active)] font-bold',
        'error' => 'bg-[color:var(--step-color-error)] border-[color:var(--step-color-error)] text-white',
        default => 'bg-white border border-[color:var(--color-border,#e5e7eb)] text-[color:var(--color-text-muted,#9ca3af)]'
    };

    // Line style matching status
    $lineStyles = match($status) {
        'completed' => 'bg-[color:var(--step-color-active)]',
        'current' => 'bg-[color:var(--step-color-active)]',
        'error' => 'bg-[color:var(--step-color-error)]',
        default => 'bg-[color:var(--step-color-muted)] opacity-50'
    };

    // Interaction tags
    $tag = $link ? 'a' : 'div';

    // ARIA Label
    $ariaLabel = "Step {$step}";
    if ($total) {
        $ariaLabel .= " of {$total}";
    }
    if (filled($title)) {
        $ariaLabel .= ": " . strip_tags($title);
    }
@endphp

<li 
    class="ui-step-item relative {{ $isVertical ? 'flex flex-row items-start gap-4 w-full' : 'flex-1 flex flex-row items-center gap-4 min-w-0' }}"
    @if($status === 'current') aria-current="step" @endif
>
    <!-- Vertical Connector Line (positioned absolutely behind circle) -->
    @if($isVertical)
        @if(!$total || $step < $total)
            <div 
                class="ui-step-connector absolute {{ $lineStyles }}"
                style="
                    inset-inline-start: calc(var(--step-circle-size) / 2 - var(--step-line-size) / 2);
                    top: calc(var(--step-circle-size) + 0.5rem);
                    bottom: -1.25rem;
                    width: var(--step-line-size);
                "
            ></div>
        @endif
    @endif

    <!-- Main Step Anchor/Wrapper -->
    <{{ $tag }}
        @if($link)
            href="{{ $isDisabled ? '#' : $link }}"
            @if($isDisabled)
                tabindex="-1"
                aria-disabled="true"
            @endif
        @endif
        @if($busy)
            aria-busy="true"
        @endif
        aria-label="{{ $ariaLabel }}"
        {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-lg outline-none transition-all duration-200 min-w-0" . 
            ($isInteractive ? " cursor-pointer focus-visible:ring-2 focus-visible:ring-[color:var(--color-primary-500)] focus-visible:ring-offset-2 hover:opacity-85" : " pointer-events-none") . 
            ($isDisabled ? " opacity-50" : "")
        ]) }}
    >
        <!-- Step Circle -->
        <div 
            class="flex items-center justify-center shrink-0 rounded-full transition-all duration-200 {{ $circleStyles }}"
            style="width: var(--step-circle-size); height: var(--step-circle-size); font-size: var(--step-font-size);"
        >
            @if($busy)
                @if(isset($busyIcon))
                    {{ $busyIcon }}
                @else
                    <svg class="animate-spin h-1/2 w-1/2 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                @endif
            @elseif($status === 'completed')
                @if(isset($completedIcon))
                    {{ $completedIcon }}
                @else
                    <svg class="h-1/2 w-1/2 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                @endif
            @elseif($status === 'error')
                @if(isset($errorIcon))
                    {{ $errorIcon }}
                @else
                    <svg class="h-1/2 w-1/2 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true" focusable="false">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                @endif
            @elseif(isset($icon))
                {{ $icon }}
            @elseif($showNumbers)
                <span>{{ $step }}</span>
            @else
                <!-- Small empty dot indicator -->
                <div class="h-1.5 w-1.5 rounded-full bg-current"></div>
            @endif
        </div>

        <!-- Step Titles & Descriptions -->
        <div class="flex flex-col text-left min-w-0 pt-0.5">
            <span class="text-sm font-semibold text-[color:var(--color-text-primary)] leading-tight whitespace-normal break-words flex items-center gap-1.5">
                {{ $title }}
                @if(isset($badge))
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-[color:var(--color-secondary-500,#e53945)] rounded-full shrink-0">{{ $badge }}</span>
                @endif
            </span>
            @if($description)
                <span class="mt-0.5 text-xs text-[color:var(--color-text-muted)] leading-snug whitespace-normal break-words">
                    {{ $description }}
                </span>
            @endif
        </div>
    </{{ $tag }}>

    <!-- Horizontal Connector Line (flex item that stretches gap) -->
    @if(!$isVertical)
        @if(!$total || $step < $total)
            <div 
                class="ui-step-connector flex-1 h-[var(--step-line-size)] {{ $lineStyles }} rounded-full"
                style="margin-inline-start: 0.5rem;"
            ></div>
        @endif
    @endif
</li>
