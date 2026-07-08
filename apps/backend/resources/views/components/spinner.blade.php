@props([
    'size' => 'sm',
    'intent' => 'current',
    'thickness' => 4,
    'srOnlyLabel' => null,
])

@php
    $sizes = [
        'xs' => 'h-3 w-3',
        'sm' => 'h-4 w-4',
        'md' => 'h-6 w-6',
        'lg' => 'h-8 w-8',
        'xl' => 'h-12 w-12',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['sm'];

    $intents = [
        'primary' => 'text-[color:var(--color-primary-600)]',
        'secondary' => 'text-[color:var(--color-secondary-500)]',
        'success' => 'text-[color:var(--color-success-500)]',
        'danger' => 'text-[color:var(--color-danger-500)]',
        'warning' => 'text-[color:var(--color-warning-500)]',
        'white' => 'text-white',
        'neutral' => 'text-[color:var(--color-neutral-600)]',
        'current' => 'text-current',
    ];
    $intentClass = $intents[$intent] ?? $intents['current'];

    // Clamp thickness value strictly between 1 and 8 after casting to int
    $clampedThickness = max(1, min(8, (int)$thickness));
@endphp

<svg
    {{ $attributes->merge([
        'class' => "animate-spin-custom shrink-0 {$sizeClass} {$intentClass}"
    ]) }}
    fill="none"
    viewBox="0 0 24 24"
    focusable="false"
    @if(filled($srOnlyLabel))
        role="status"
        aria-live="polite"
    @else
        aria-hidden="true"
    @endif
>
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="{{ $clampedThickness }}"></circle>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>
@if(filled($srOnlyLabel))
    <span class="sr-only">{{ $srOnlyLabel }}</span>
@endif
