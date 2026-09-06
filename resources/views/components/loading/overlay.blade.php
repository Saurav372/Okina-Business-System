{{--
    Okina Loading Overlay (U1.6.2)
    
    The wrapper container (when slot is occupied) becomes the positioning context for the overlay 
    and automatically applies "relative overflow-hidden". Consumers should not wrap the component 
    in an additional positioning container.
--}}
@props([
    'show' => 'false',
    'label' => null,
    'spinnerSize' => 'md',
    'spinnerIntent' => 'primary',
    'blur' => 'xs',
    'fullscreen' => false,
    'tone' => 'surface',
])

@php
    $tones = [
        'surface' => 'bg-white/80 dark:bg-neutral-900/80',
        'glass' => 'bg-white/40 dark:bg-black/40',
        'dark' => 'bg-neutral-950/70',
        'transparent' => 'bg-transparent',
    ];
    $toneClass = $tones[$tone] ?? $tones['surface'];

    $blurs = [
        'none' => '',
        'xs' => 'backdrop-blur-xs',
        'sm' => 'backdrop-blur-sm',
        'md' => 'backdrop-blur-md',
    ];
    $blurClass = $blurs[$blur] ?? $blurs['xs'];

    $positionClass = $fullscreen ? 'fixed inset-0 z-[var(--z-overlay,100)]' : 'absolute inset-0 z-30';
    $classes = "{$positionClass} flex flex-col items-center justify-center pointer-events-auto {$toneClass} {$blurClass}";
@endphp

@if($slot->isNotEmpty())
    <div class="{{ $fullscreen ? '' : 'relative overflow-hidden' }} w-full h-full">
        <div :inert="{{ $show }}" class="w-full h-full">
            {{ $slot }}
        </div>

        <x-motion
            type="fade"
            show="{{ $show }}"
            x-cloak
            ::aria-busy="{{ $show }}"
            :attributes="$attributes"
            class="{{ $classes }}"
        >
            <x-spinner :size="$spinnerSize" :intent="$spinnerIntent" />
            @if(filled($label))
                <p class="mt-2 text-sm font-medium text-[color:var(--color-text-secondary)]">{{ $label }}</p>
            @endif
        </x-motion>
    </div>
@else
    <x-motion
        type="fade"
        show="{{ $show }}"
        x-cloak
        ::aria-busy="{{ $show }}"
        :attributes="$attributes"
        class="{{ $classes }}"
    >
        <x-spinner :size="$spinnerSize" :intent="$spinnerIntent" />
        @if(filled($label))
            <p class="mt-2 text-sm font-medium text-[color:var(--color-text-secondary)]">{{ $label }}</p>
        @endif
    </x-motion>
@endif
